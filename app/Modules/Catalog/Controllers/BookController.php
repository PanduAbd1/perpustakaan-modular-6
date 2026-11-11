<?php

namespace Modules\Catalog\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Catalog\Models\Author;
use Modules\Catalog\Models\Book;
use Modules\Catalog\Models\Category;
use Modules\Catalog\Models\Publisher;
use Modules\Catalog\Requests\StoreBookRequest;
use Modules\Catalog\Requests\UpdateBookRequest;

class BookController extends Controller
{
    public function index(Request $request)
    {
        $books = Book::query()
            ->with(['publisher', 'authors', 'categories'])
            ->when($request->filled('q'), fn ($query) => $query->search($request->string('q')->toString()))
            ->when($request->filled('category'), fn ($query) => $query->inCategory($request->integer('category')))
            ->when($request->boolean('available'), fn ($query) => $query->available())
            ->orderBy('title')
            ->paginate(10)
            ->withQueryString();

        $categories = Category::query()->with('parent')->orderBy('name')->get();

        return view('books.index', compact('books', 'categories'));
    }

    public function create()
    {
        return view('books.create', $this->formData());
    }

    public function store(StoreBookRequest $request)
    {
        DB::transaction(function () use ($request) {
            $book = Book::create($request->safe()->except(['author_ids', 'category_ids']));

            $book->authors()->sync($request->input('author_ids', []));
            $book->categories()->attach($request->input('category_ids', []));
        });

        return redirect()
            ->route('books.index')
            ->with('success', 'Buku berhasil ditambahkan.');
    }

    public function edit(Book $book)
    {
        $book->load(['authors', 'categories']);

        return view('books.edit', ['book' => $book] + $this->formData());
    }

    public function update(UpdateBookRequest $request, Book $book)
    {
        DB::transaction(function () use ($request, $book) {
            $book->update($request->safe()->except(['author_ids', 'category_ids']));

            $book->authors()->sync($request->input('author_ids', []));
            $book->categories()->sync($request->input('category_ids', []));
        });

        return redirect()
            ->route('books.index')
            ->with('success', 'Buku berhasil diperbarui.');
    }

    public function destroy(Book $book)
    {
        $book->delete();

        return redirect()
            ->route('books.index')
            ->with('success', 'Buku dipindahkan ke arsip (soft delete).');
    }

    public function trashed()
    {
        $books = Book::onlyTrashed()
            ->with(['publisher', 'authors'])
            ->latest('deleted_at')
            ->paginate(10);

        return view('books.trashed', compact('books'));
    }

    public function restore(Book $book)
    {
        $book->restore();

        return redirect()
            ->route('books.trashed')
            ->with('success', 'Buku berhasil dipulihkan.');
    }

    private function formData(): array
    {
        return [
            'publishers' => Publisher::query()->orderBy('name')->get(),
            'authors' => Author::query()->orderBy('name')->get(),
            'categories' => Category::query()->with('parent')->orderBy('name')->get(),
        ];
    }
}

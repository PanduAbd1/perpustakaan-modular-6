<?php

use Modules\Catalog\Models\Author;
use Modules\Catalog\Models\Book;
use Modules\Catalog\Models\Category;
use Modules\Circulation\Models\Loan;
use Modules\Circulation\Models\LoanItem;
use Modules\Shared\ValueObjects\Isbn;

it('menyimpan pivot added_at pada relasi many-to-many buku dan kategori', function () {
    $book = makeBook();
    $categories = Category::factory()->count(2)->create();

    $book->categories()->attach($categories->pluck('id')->all());

    $loaded = Book::with('categories')->find($book->id);

    expect($loaded->categories)->toHaveCount(2)
        ->and($loaded->categories->first()->pivot->added_at)->not->toBeNull();
});

it('mendukung relasi many-to-many buku dan penulis dari dua sisi', function () {
    $book = makeBook();
    $author = Author::factory()->create();
    $book->authors()->attach($author);

    expect($book->authors->first()->is($author))->toBeTrue()
        ->and($author->books->first()->is($book))->toBeTrue();
});

it('mendukung kategori self-referencing parent dan children', function () {
    $parent = Category::factory()->create(['name' => 'Teknologi']);
    $child = Category::factory()->create(['name' => 'Pemrograman', 'parent_id' => $parent->id]);
    $grandChild = Category::factory()->create(['name' => 'PHP', 'parent_id' => $child->id]);

    expect($child->parent->is($parent))->toBeTrue()
        ->and($parent->children)->toHaveCount(1)
        ->and(Category::descendantIdsOf($parent->id))->toEqualCanonicalizing([$parent->id, $child->id, $grandChild->id]);
});

it('mendukung relasi HasMany peminjaman ke detail peminjaman', function () {
    $loan = Loan::factory()->has(LoanItem::factory()->count(2), 'items')->create();

    expect($loan->items)->toHaveCount(2)
        ->and($loan->items->first()->loan->is($loan))->toBeTrue()
        ->and($loan->totalQuantity())->toBe(2);
});

it('mengubah ISBN menjadi value object melalui cast dan menyimpannya ternormalisasi', function () {
    $book = makeBook(1, ['isbn' => '978-0-306-40615-7']);

    expect($book->fresh()->isbn)->toBeInstanceOf(Isbn::class)
        ->and($book->fresh()->isbn->getValue())->toBe('9780306406157')
        ->and($book->getRawOriginal('isbn'))->toBe('9780306406157');
});

it('menolak ISBN tidak valid saat disimpan lewat model', function () {
    makeBook(1, ['isbn' => '1234567890123']);
})->throws(InvalidArgumentException::class);

it('menyaring buku dengan query scope', function () {
    $parent = Category::factory()->create();
    $child = Category::factory()->create(['parent_id' => $parent->id]);
    $author = Author::factory()->create(['name' => 'Andrea Hirata']);

    $available = makeBook(3, ['title' => 'Laskar Pelangi']);
    $available->categories()->attach($child);
    $available->authors()->attach($author);

    makeBook(0, ['title' => 'Habis Terpinjam']);
    makeBook(4, ['title' => 'Buku Nonaktif', 'is_active' => false]);
    makeBook(1, ['title' => 'Stok Menipis', 'low_stock_threshold' => 2]);

    expect(Book::available()->pluck('title')->all())->toContain('Laskar Pelangi', 'Stok Menipis')
        ->not->toContain('Habis Terpinjam', 'Buku Nonaktif')
        ->and(Book::search('Hirata')->pluck('title')->all())->toBe(['Laskar Pelangi'])
        ->and(Book::search('Pelangi')->count())->toBe(1)
        ->and(Book::inCategory($parent->id)->pluck('title')->all())->toBe(['Laskar Pelangi'])
        ->and(Book::lowStock()->pluck('title')->all())->toContain('Habis Terpinjam', 'Stok Menipis');
});

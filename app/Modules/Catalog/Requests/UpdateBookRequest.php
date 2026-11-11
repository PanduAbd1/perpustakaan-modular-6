<?php

namespace Modules\Catalog\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Catalog\Rules\ValidIsbn;
use Modules\Shared\ValueObjects\Isbn;

class UpdateBookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->isbn) && Isbn::isValid($this->isbn)) {
            $this->merge(['isbn' => (new Isbn($this->isbn))->getValue()]);
        }
    }

    public function rules(): array
    {
        $book = $this->route('book');

        return [
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'alpha_dash', 'max:255', Rule::unique('books', 'slug')->ignore($book)],
            'isbn' => ['required', new ValidIsbn, Rule::unique('books', 'isbn')->ignore($book)],
            'publisher_id' => ['nullable', 'integer', 'exists:publishers,id'],
            'published_year' => ['nullable', 'integer', 'between:1000,'.(now()->year + 1)],
            'stock' => ['required', 'integer', 'min:0'],
            'description' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
            'author_ids' => ['nullable', 'array'],
            'author_ids.*' => ['integer', 'exists:authors,id'],
            'category_ids' => ['nullable', 'array'],
            'category_ids.*' => ['integer', 'exists:book_categories,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Judul buku wajib diisi.',
            'isbn.required' => 'ISBN wajib diisi.',
            'isbn.unique' => 'ISBN sudah terdaftar.',
            'stock.min' => 'Stok tidak boleh negatif.',
        ];
    }
}

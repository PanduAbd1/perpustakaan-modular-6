<?php

namespace Modules\Circulation\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreLoanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'member_id' => ['required', 'integer'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.book_id' => ['required', 'integer', 'distinct'],
            'items.*.quantity' => ['required', 'integer', 'between:1,5'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'member_id.required' => 'Pilih anggota peminjam.',
            'items.required' => 'Pilih minimal 1 buku.',
            'items.min' => 'Pilih minimal 1 buku.',
            'items.*.book_id.distinct' => 'Buku yang sama tidak boleh dipilih dua kali.',
            'items.*.quantity.between' => 'Jumlah per buku harus antara 1 sampai 5.',
        ];
    }
}

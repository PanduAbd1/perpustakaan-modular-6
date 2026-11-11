<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Catalog\Models\Book;
use Modules\Circulation\Models\Loan;
use Modules\Circulation\Models\LoanItem;

class LoanItemFactory extends Factory
{
    protected $model = LoanItem::class;

    public function definition(): array
    {
        return [
            'loan_id' => Loan::factory(),
            'book_id' => Book::factory(),
            'book_title' => fn (array $attributes) => Book::query()->find($attributes['book_id'])->title,
            'book_isbn' => fn (array $attributes) => Book::query()->find($attributes['book_id'])->isbn->getValue(),
            'quantity' => 1,
            'fine_amount' => 0,
        ];
    }
}

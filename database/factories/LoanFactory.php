<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Circulation\Models\Loan;
use Modules\Membership\Models\Member;

class LoanFactory extends Factory
{
    protected $model = Loan::class;

    public function definition(): array
    {
        return [
            'member_id' => Member::factory(),
            'loan_number' => 'PJM-'.now()->format('Ymd').'-'.Str::upper(Str::random(6)),
            'member_name' => fn (array $attributes) => Member::query()->find($attributes['member_id'])->name,
            'status' => Loan::STATUS_BORROWED,
            'borrowed_at' => now()->subDays(3),
            'due_at' => now()->addDays(4),
            'total_fine' => 0,
        ];
    }

    public function returned(): static
    {
        return $this->state(fn () => [
            'status' => Loan::STATUS_RETURNED,
            'returned_at' => now()->subDay(),
        ]);
    }

    public function overdue(): static
    {
        return $this->state(fn () => [
            'borrowed_at' => now()->subDays(12),
            'due_at' => now()->subDays(5),
        ]);
    }
}

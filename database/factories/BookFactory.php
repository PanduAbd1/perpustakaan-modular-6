<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Catalog\Models\Book;
use Modules\Catalog\Models\Publisher;

class BookFactory extends Factory
{
    protected $model = Book::class;

    public function definition(): array
    {
        $title = Str::title(rtrim(fake()->unique()->sentence(3), '.'));

        return [
            'publisher_id' => Publisher::factory(),
            'title' => $title,
            'slug' => Str::slug($title).'-'.fake()->unique()->numberBetween(1, 99999),
            'isbn' => $this->fakeIsbn13(),
            'description' => fake()->paragraph(),
            'published_year' => fake()->numberBetween(1995, 2026),
            'stock' => fake()->numberBetween(1, 8),
            'low_stock_threshold' => 2,
            'is_active' => true,
        ];
    }

    public function outOfStock(): static
    {
        return $this->state(fn () => ['stock' => 0]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }

    private function fakeIsbn13(): string
    {
        $body = '978'.fake()->unique()->numerify('#########');
        $sum = 0;

        foreach (str_split($body) as $index => $digit) {
            $sum += (int) $digit * ($index % 2 === 0 ? 1 : 3);
        }

        return $body.((10 - ($sum % 10)) % 10);
    }
}

<?php

namespace Modules\Catalog\Services;

use Modules\Catalog\Models\Book;
use Modules\Shared\Contracts\BookServiceInterface;

class BookService implements BookServiceInterface
{
    public function getBookForLoan(int $bookId): ?array
    {
        $book = Book::query()->find($bookId);

        if (! $book) {
            return null;
        }

        return $this->toArray($book);
    }

    public function isStockAvailable(int $bookId, int $quantity): bool
    {
        $book = Book::query()->find($bookId);

        return $book !== null && $book->is_active && $book->isInStock($quantity);
    }

    public function reduceStock(int $bookId, int $quantity): bool
    {
        $affected = Book::query()
            ->whereKey($bookId)
            ->where('stock', '>=', $quantity)
            ->decrement('stock', $quantity);

        return $affected > 0;
    }

    public function restoreStock(int $bookId, int $quantity): void
    {
        Book::withTrashed()->whereKey($bookId)->increment('stock', $quantity);
    }

    public function listAvailableBooks(): array
    {
        return Book::query()
            ->available()
            ->orderBy('title')
            ->get(['id', 'title', 'isbn', 'stock'])
            ->map(fn (Book $book) => $this->toArray($book))
            ->all();
    }

    private function toArray(Book $book): array
    {
        return [
            'id' => $book->id,
            'title' => $book->title,
            'isbn' => $book->isbn->getValue(),
            'stock' => $book->stock,
        ];
    }
}

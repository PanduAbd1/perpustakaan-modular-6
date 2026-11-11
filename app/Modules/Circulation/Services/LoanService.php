<?php

namespace Modules\Circulation\Services;

use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Modules\Circulation\Events\BookBorrowed;
use Modules\Circulation\Models\Loan;
use Modules\Circulation\Models\LoanItem;
use Modules\Shared\Contracts\BookServiceInterface;
use Modules\Shared\Contracts\MemberServiceInterface;
use Modules\Shared\Exceptions\EntityNotFoundException;
use Modules\Shared\Exceptions\InsufficientStockException;
use Modules\Shared\Exceptions\LoanAlreadyReturnedException;
use Modules\Shared\Exceptions\MemberNotEligibleException;
use Modules\Shared\ValueObjects\Denda;

class LoanService
{
    public function __construct(
        private BookServiceInterface $books,
        private MemberServiceInterface $members,
    ) {}

    public function borrow(
        int $memberId,
        array $items,
        ?CarbonInterface $borrowedAt = null,
        ?string $notes = null,
    ): Loan {
        $lines = $this->normalizeItems($items);

        if ($lines->isEmpty()) {
            throw new InvalidArgumentException('Peminjaman minimal berisi 1 buku.');
        }

        $borrowedAt ??= now();
        $totalQuantity = (int) $lines->sum();

        [$loan, $memberEmail] = DB::transaction(function () use ($memberId, $lines, $borrowedAt, $notes, $totalQuantity) {
            $member = $this->members->getMemberForLoan($memberId)
                ?? throw new EntityNotFoundException('Anggota', $memberId);

            $reason = $this->members->checkEligibility($memberId, $totalQuantity);

            if ($reason !== null) {
                throw new MemberNotEligibleException($reason);
            }

            $loan = Loan::create([
                'member_id' => $memberId,
                'loan_number' => 'PJM-'.now()->format('Ymd').'-'.Str::upper(Str::random(6)),
                'member_name' => $member['name'],
                'status' => Loan::STATUS_BORROWED,
                'borrowed_at' => $borrowedAt,
                'due_at' => $borrowedAt->copy()->addDays($member['loan_days']),
                'total_fine' => 0,
                'notes' => $notes,
            ]);

            foreach ($lines as $bookId => $quantity) {
                $book = $this->books->getBookForLoan($bookId)
                    ?? throw new EntityNotFoundException('Buku', $bookId);

                if (! $this->books->isStockAvailable($bookId, $quantity)) {
                    throw new InsufficientStockException($book['title'], $book['stock'], $quantity);
                }

                LoanItem::create([
                    'loan_id' => $loan->id,
                    'book_id' => $book['id'],
                    'book_title' => $book['title'],
                    'book_isbn' => $book['isbn'],
                    'quantity' => $quantity,
                ]);

                if (! $this->books->reduceStock($bookId, $quantity)) {
                    throw new InsufficientStockException($book['title'], $book['stock'], $quantity);
                }
            }

            $this->members->markBorrowing($memberId, $totalQuantity);

            return [$loan->load('items'), $member['email']];
        });

        event(new BookBorrowed($loan, $memberEmail));

        return $loan;
    }

    public function returnLoan(Loan $loan, ?CarbonInterface $returnedAt = null): Loan
    {
        if ($loan->isReturned()) {
            throw new LoanAlreadyReturnedException($loan->loan_number);
        }

        $returnedAt ??= now();

        DB::transaction(function () use ($loan, $returnedAt) {
            $loan->loadMissing('items');

            $totalFine = 0.0;
            $returnedQuantity = 0;

            foreach ($loan->items->whereNull('returned_at') as $item) {
                $denda = Denda::hitung($loan->due_at, $returnedAt, $item->quantity);

                $item->update([
                    'returned_at' => $returnedAt,
                    'fine_amount' => $denda->getAmount()->getAmount(),
                ]);

                $this->books->restoreStock($item->book_id, $item->quantity);

                $totalFine += $denda->getAmount()->getAmount();
                $returnedQuantity += $item->quantity;
            }

            $loan->update([
                'status' => Loan::STATUS_RETURNED,
                'returned_at' => $returnedAt,
                'total_fine' => $totalFine,
            ]);

            $this->members->markReturned($loan->member_id, $returnedQuantity);
        });

        return $loan->fresh('items');
    }

    private function normalizeItems(array $items): Collection
    {
        return collect($items)
            ->groupBy(fn (array $item) => (int) $item['book_id'])
            ->map(fn (Collection $group) => (int) $group->sum(fn (array $item) => (int) ($item['quantity'] ?? 1)))
            ->filter(fn (int $quantity) => $quantity > 0);
    }
}

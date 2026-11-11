<?php

namespace Modules\Circulation\Listeners;

use Illuminate\Support\Facades\Log;
use Modules\Circulation\Events\BookBorrowed;

class RecordBorrowLog
{
    public function handle(BookBorrowed $event): void
    {
        $loan = $event->loan;

        Log::channel('circulation')->info('Buku dipinjam', [
            'loan_number' => $loan->loan_number,
            'member_id' => $loan->member_id,
            'member_name' => $loan->member_name,
            'books' => $loan->items->pluck('book_title')->all(),
            'total_quantity' => $loan->totalQuantity(),
            'due_at' => $loan->due_at->toDateString(),
        ]);
    }
}

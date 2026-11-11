<?php

use Illuminate\Support\Facades\Event;
use Modules\Catalog\Models\Book;
use Modules\Circulation\Events\BookBorrowed;
use Modules\Circulation\Models\Loan;
use Modules\Circulation\Models\LoanItem;
use Modules\Membership\Models\Member;
use Modules\Shared\Contracts\BookServiceInterface;
use Modules\Shared\Exceptions\EntityNotFoundException;
use Modules\Shared\Exceptions\InsufficientStockException;
use Modules\Shared\Exceptions\LoanAlreadyReturnedException;
use Modules\Shared\Exceptions\MemberNotEligibleException;

it('meminjam buku: mengurangi stok, membuat peminjaman, dan memperbarui status anggota', function () {
    Event::fake([BookBorrowed::class]);

    $member = makeEligibleMember(3, 7);
    $bookA = makeBook(5);
    $bookB = makeBook(2);

    $loan = loanService()->borrow($member->id, [
        ['book_id' => $bookA->id, 'quantity' => 1],
        ['book_id' => $bookB->id, 'quantity' => 2],
    ]);

    expect($loan->items)->toHaveCount(2)
        ->and($loan->status)->toBe(Loan::STATUS_BORROWED)
        ->and($loan->member_name)->toBe($member->name)
        ->and($loan->due_at->toDateString())->toBe(now()->addDays(7)->toDateString())
        ->and($bookA->fresh()->stock)->toBe(4)
        ->and($bookB->fresh()->stock)->toBe(0)
        ->and($member->fresh()->active_loans)->toBe(3)
        ->and($member->fresh()->status)->toBe(Member::STATUS_BORROWING);

    $item = $loan->items->firstWhere('book_id', $bookA->id);

    expect($item->book_title)->toBe($bookA->title)
        ->and($item->book_isbn)->toBe($bookA->isbn->getValue());

    Event::assertDispatched(BookBorrowed::class, fn (BookBorrowed $event) => $event->loan->is($loan) && $event->memberEmail === $member->email);
});

it('rollback seluruh transaksi ketika stok salah satu buku tidak mencukupi', function () {
    Event::fake([BookBorrowed::class]);

    $member = makeEligibleMember();
    $bookA = makeBook(5);
    $bookB = makeBook(1);

    expect(fn () => loanService()->borrow($member->id, [
        ['book_id' => $bookA->id, 'quantity' => 1],
        ['book_id' => $bookB->id, 'quantity' => 2],
    ]))->toThrow(InsufficientStockException::class);

    expect($bookA->fresh()->stock)->toBe(5)
        ->and($bookB->fresh()->stock)->toBe(1)
        ->and(Loan::count())->toBe(0)
        ->and(LoanItem::count())->toBe(0)
        ->and($member->fresh()->active_loans)->toBe(0)
        ->and($member->fresh()->status)->toBe(Member::STATUS_ACTIVE);

    Event::assertNotDispatched(BookBorrowed::class);
});

it('rollback ketika kuota peminjaman anggota terlampaui', function () {
    Event::fake([BookBorrowed::class]);

    $member = makeEligibleMember(2);
    $book = makeBook(10);

    expect(fn () => loanService()->borrow($member->id, [['book_id' => $book->id, 'quantity' => 3]]))
        ->toThrow(MemberNotEligibleException::class, 'Kuota peminjaman terlampaui');

    expect($book->fresh()->stock)->toBe(10)
        ->and(Loan::count())->toBe(0)
        ->and($member->fresh()->active_loans)->toBe(0);

    Event::assertNotDispatched(BookBorrowed::class);
});

it('menolak anggota dengan keanggotaan kedaluwarsa atau ditangguhkan', function () {
    $book = makeBook(5);
    $expired = Member::factory()->withExpiredMembership()->create();
    $suspended = Member::factory()->eligible()->suspended()->create();

    expect(fn () => loanService()->borrow($expired->id, [['book_id' => $book->id]]))
        ->toThrow(MemberNotEligibleException::class, 'berakhir')
        ->and(fn () => loanService()->borrow($suspended->id, [['book_id' => $book->id]]))
        ->toThrow(MemberNotEligibleException::class, 'ditangguhkan');

    expect($book->fresh()->stock)->toBe(5)->and(Loan::count())->toBe(0);
});

it('rollback ketika buku tidak ditemukan setelah peminjaman dibuat', function () {
    $member = makeEligibleMember();
    $book = makeBook(5);

    expect(fn () => loanService()->borrow($member->id, [
        ['book_id' => $book->id, 'quantity' => 1],
        ['book_id' => 999999, 'quantity' => 1],
    ]))->toThrow(EntityNotFoundException::class);

    expect(Loan::count())->toBe(0)->and($book->fresh()->stock)->toBe(5);
});

it('menolak anggota yang tidak ada dan peminjaman tanpa item', function () {
    $book = makeBook(5);

    expect(fn () => loanService()->borrow(999999, [['book_id' => $book->id]]))
        ->toThrow(EntityNotFoundException::class)
        ->and(fn () => loanService()->borrow(1, []))
        ->toThrow(InvalidArgumentException::class, 'minimal');
});

it('menggabungkan baris buku yang sama menjadi satu detail peminjaman', function () {
    Event::fake([BookBorrowed::class]);

    $member = makeEligibleMember(5);
    $book = makeBook(5);

    $loan = loanService()->borrow($member->id, [
        ['book_id' => $book->id, 'quantity' => 1],
        ['book_id' => $book->id, 'quantity' => 2],
    ]);

    expect($loan->items)->toHaveCount(1)
        ->and($loan->items->first()->quantity)->toBe(3)
        ->and($book->fresh()->stock)->toBe(2);
});

it('mengembalikan buku tepat waktu tanpa denda dan memulihkan stok serta status anggota', function () {
    Event::fake([BookBorrowed::class]);

    $member = makeEligibleMember();
    $book = makeBook(3);
    $loan = loanService()->borrow($member->id, [['book_id' => $book->id, 'quantity' => 2]]);

    $returned = loanService()->returnLoan($loan, now()->addDays(3));

    expect($returned->status)->toBe(Loan::STATUS_RETURNED)
        ->and((float) $returned->total_fine)->toBe(0.0)
        ->and($returned->items->first()->returned_at)->not->toBeNull()
        ->and($book->fresh()->stock)->toBe(3)
        ->and($member->fresh()->active_loans)->toBe(0)
        ->and($member->fresh()->status)->toBe(Member::STATUS_ACTIVE);
});

it('menghitung denda otomatis saat pengembalian terlambat', function () {
    Event::fake([BookBorrowed::class]);

    $member = makeEligibleMember(5, 7);
    $bookA = makeBook(3);
    $bookB = makeBook(3);
    $loan = loanService()->borrow($member->id, [
        ['book_id' => $bookA->id, 'quantity' => 1],
        ['book_id' => $bookB->id, 'quantity' => 2],
    ]);

    $returned = loanService()->returnLoan($loan, now()->addDays(7 + 4));

    expect((float) $returned->total_fine)->toBe(12000.0)
        ->and((float) $returned->items->firstWhere('book_id', $bookA->id)->fine_amount)->toBe(4000.0)
        ->and((float) $returned->items->firstWhere('book_id', $bookB->id)->fine_amount)->toBe(8000.0);
});

it('menolak pengembalian ganda', function () {
    Event::fake([BookBorrowed::class]);

    $member = makeEligibleMember();
    $book = makeBook(3);
    $loan = loanService()->borrow($member->id, [['book_id' => $book->id]]);

    loanService()->returnLoan($loan);

    expect(fn () => loanService()->returnLoan($loan->fresh()))->toThrow(LoanAlreadyReturnedException::class);
    expect($book->fresh()->stock)->toBe(3);
});

it('tidak membuat stok buku menjadi negatif', function () {
    $book = makeBook(1);

    expect(app(BookServiceInterface::class)->reduceStock($book->id, 2))->toBeFalse()
        ->and($book->fresh()->stock)->toBe(1);
});

it('tidak bisa meminjam buku yang di-soft delete atau nonaktif', function () {
    $member = makeEligibleMember();
    $deleted = makeBook(3);
    $deleted->delete();
    $inactive = makeBook(3, ['is_active' => false]);

    expect(fn () => loanService()->borrow($member->id, [['book_id' => $deleted->id]]))
        ->toThrow(EntityNotFoundException::class)
        ->and(fn () => loanService()->borrow($member->id, [['book_id' => $inactive->id]]))
        ->toThrow(InsufficientStockException::class);

    expect(Book::withTrashed()->find($deleted->id)->stock)->toBe(3);
});

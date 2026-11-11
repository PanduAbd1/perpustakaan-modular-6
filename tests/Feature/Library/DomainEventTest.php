<?php

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Modules\Circulation\Events\BookBorrowed;
use Modules\Circulation\Notifications\BookBorrowedNotification;
use Modules\Shared\Exceptions\InsufficientStockException;

beforeEach(function () {
    $this->logPath = storage_path('framework/testing/circulation-'.uniqid().'.log');
    config(['logging.channels.circulation.path' => $this->logPath]);
});

afterEach(function () {
    @unlink($this->logPath);
});

it('mendaftarkan listener untuk event BookBorrowed', function () {
    expect(Event::hasListeners(BookBorrowed::class))->toBeTrue();
});

it('menembakkan BookBorrowed setelah peminjaman berhasil lalu mencatat log dan mengirim notifikasi', function () {
    Notification::fake();

    $member = makeEligibleMember();
    $book = makeBook(3, ['title' => 'Laskar Pelangi']);

    $loan = loanService()->borrow($member->id, [['book_id' => $book->id]]);

    expect(file_exists($this->logPath))->toBeTrue();

    $log = file_get_contents($this->logPath);

    expect($log)->toContain('Buku dipinjam')
        ->toContain($loan->loan_number)
        ->toContain('Laskar Pelangi');

    Notification::assertSentOnDemand(
        BookBorrowedNotification::class,
        fn ($notification, $channels, $notifiable) => $notifiable->routes['mail'] === $member->email
    );
});

it('tidak menembakkan event, log, maupun notifikasi ketika transaksi di-rollback', function () {
    Notification::fake();

    $member = makeEligibleMember();
    $book = makeBook(1);

    expect(fn () => loanService()->borrow($member->id, [['book_id' => $book->id, 'quantity' => 2]]))
        ->toThrow(InsufficientStockException::class);

    expect(file_exists($this->logPath))->toBeFalse();

    Notification::assertNothingSent();
});

it('menyusun isi email notifikasi peminjaman', function () {
    Notification::fake();

    $member = makeEligibleMember();
    $book = makeBook(3, ['title' => 'Bumi Manusia']);
    $loan = loanService()->borrow($member->id, [['book_id' => $book->id]]);

    $mail = (new BookBorrowedNotification($loan))->toMail(new stdClass);

    expect($mail->subject)->toContain($loan->loan_number)
        ->and(implode(' ', $mail->introLines))->toContain('Bumi Manusia')->toContain('Denda');
});

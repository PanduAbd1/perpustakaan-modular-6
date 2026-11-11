<?php

namespace Modules\Circulation\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\Circulation\Models\Loan;
use Modules\Shared\ValueObjects\Denda;

class BookBorrowedNotification extends Notification
{
    public function __construct(private Loan $loan) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject("Peminjaman {$this->loan->loan_number} berhasil")
            ->greeting("Halo, {$this->loan->member_name}")
            ->line('Buku berikut berhasil dipinjam:');

        foreach ($this->loan->items as $item) {
            $message->line("{$item->book_title} ({$item->quantity} buku)");
        }

        return $message
            ->line('Jatuh tempo pengembalian: '.$this->loan->due_at->format('d-m-Y'))
            ->line('Denda keterlambatan Rp'.number_format(Denda::TARIF_PER_HARI, 0, ',', '.').' per hari per buku.');
    }
}

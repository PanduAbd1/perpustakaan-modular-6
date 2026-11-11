<?php

namespace Modules\Circulation\Listeners;

use Illuminate\Support\Facades\Notification;
use Modules\Circulation\Events\BookBorrowed;
use Modules\Circulation\Notifications\BookBorrowedNotification;

class SendBorrowNotification
{
    public function handle(BookBorrowed $event): void
    {
        Notification::route('mail', $event->memberEmail)
            ->notify(new BookBorrowedNotification($event->loan));
    }
}

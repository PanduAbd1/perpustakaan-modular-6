<?php

namespace Modules\Circulation\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Modules\Circulation\Events\BookBorrowed;
use Modules\Circulation\Listeners\RecordBorrowLog;
use Modules\Circulation\Listeners\SendBorrowNotification;

class CirculationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Migrations');
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');

        Event::listen(BookBorrowed::class, RecordBorrowLog::class);
        Event::listen(BookBorrowed::class, SendBorrowNotification::class);
    }
}

<?php

use Illuminate\Support\Facades\Route;
use Modules\Catalog\Controllers\BookController;

Route::middleware(['web', 'auth'])->group(function () {
    Route::get('books', [BookController::class, 'index'])->name('books.index');

    Route::middleware('role:admin')->group(function () {
        Route::get('books/trashed', [BookController::class, 'trashed'])->name('books.trashed');
        Route::patch('books/{book}/restore', [BookController::class, 'restore'])
            ->withTrashed()
            ->name('books.restore');
        Route::resource('books', BookController::class)->only(['create', 'store', 'edit', 'update', 'destroy']);
    });
});

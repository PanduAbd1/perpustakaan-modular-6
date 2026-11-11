<?php

use Illuminate\Support\Facades\Route;
use Modules\Circulation\Controllers\LoanController;

Route::middleware(['web', 'auth', 'role:admin'])->group(function () {
    Route::get('loans/trashed', [LoanController::class, 'trashed'])->name('loans.trashed');
    Route::patch('loans/{loan}/restore', [LoanController::class, 'restore'])
        ->withTrashed()
        ->name('loans.restore');
    Route::patch('loans/{loan}/return', [LoanController::class, 'returnLoan'])->name('loans.return');
    Route::resource('loans', LoanController::class)->only(['index', 'create', 'store', 'show', 'destroy']);
});

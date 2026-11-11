<?php

use Illuminate\Support\Facades\Route;
use Modules\Membership\Controllers\MemberController;

Route::middleware(['web', 'auth', 'role:admin'])->group(function () {
    Route::get('members/trashed', [MemberController::class, 'trashed'])->name('members.trashed');
    Route::patch('members/{member}/restore', [MemberController::class, 'restore'])
        ->withTrashed()
        ->name('members.restore');
    Route::resource('members', MemberController::class)->only(['index', 'create', 'store', 'show', 'destroy']);
});

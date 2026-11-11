<?php

use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Illuminate\Support\Facades\Hash;
use Modules\Auth\Models\Role;

it('menyimpan kolom role saat user dibuat lewat mass assignment', function () {
    $admin = User::create([
        'name' => 'Admin Baru',
        'email' => 'admin.baru@example.com',
        'password' => Hash::make('password123'),
        'role' => 'admin',
    ]);

    expect($admin->fresh()->role)->toBe('admin')
        ->and($admin->isAdmin())->toBeTrue()
        ->and($admin->isUser())->toBeFalse();
});

it('mendukung relasi many-to-many user dan role', function () {
    $user = User::factory()->create();
    $roles = collect([
        Role::create(['name' => 'Administrator', 'slug' => 'admin']),
        Role::create(['name' => 'Editor', 'slug' => 'editor']),
    ]);

    $user->roles()->attach($roles->pluck('id')->all());

    expect($user->roles)->toHaveCount(2)
        ->and($roles->first()->users->first()->is($user))->toBeTrue();
});

it('seeder admin membuat user dengan role yang benar dan menempelkan role', function () {
    $this->seed(AdminUserSeeder::class);

    $admin = User::where('email', 'admin@example.com')->firstOrFail();
    $user = User::where('email', 'user@example.com')->firstOrFail();

    expect($admin->role)->toBe('admin')
        ->and($admin->roles->pluck('slug')->all())->toBe(['admin'])
        ->and($user->role)->toBe('user')
        ->and($user->roles->pluck('slug')->all())->toBe(['user']);
});

it('membatasi dashboard admin hanya untuk role admin', function () {
    $this->get('/admin/dashboard')->assertRedirect(route('login'));

    $this->actingAs(makeRegularUser())->get('/admin/dashboard')->assertForbidden();

    $this->actingAs(makeAdmin())->get('/admin/dashboard')->assertOk();
});

it('mengizinkan user login membuka dashboard user', function () {
    $this->actingAs(makeRegularUser())->get('/user/dashboard')->assertOk();
});

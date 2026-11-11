<?php

use Modules\Catalog\Models\Author;
use Modules\Catalog\Models\Book;
use Modules\Catalog\Models\Category;
use Modules\Circulation\Models\Loan;
use Modules\Circulation\Models\LoanItem;
use Modules\Membership\Models\Member;
use Modules\Membership\Models\Role;

it('menghilangkan N+1 pada peminjaman dengan eager loading', function () {
    Loan::factory()->count(10)->has(LoanItem::factory()->count(2), 'items')->create();

    $lazy = countQueries(fn () => Loan::all()->each(fn (Loan $loan) => $loan->items->count()));
    $eager = countQueries(fn () => Loan::with('items')->get()->each(fn (Loan $loan) => $loan->items->count()));

    expect($lazy)->toBe(11)->and($eager)->toBe(2);
});

it('menghilangkan N+1 pada buku dengan eager loading penerbit, penulis, dan kategori', function () {
    $authors = Author::factory()->count(3)->create();
    $categories = Category::factory()->count(3)->create();

    Book::factory()->count(12)->create()->each(function (Book $book) use ($authors, $categories) {
        $book->authors()->attach($authors->random(2)->pluck('id')->all());
        $book->categories()->attach($categories->random(2)->pluck('id')->all());
    });

    $render = fn (Book $book) => [$book->publisher?->name, $book->authors->count(), $book->categories->count()];

    $lazy = countQueries(fn () => Book::all()->each($render));
    $eager = countQueries(fn () => Book::with(['publisher', 'authors', 'categories'])->get()->each($render));

    expect($lazy)->toBe(37)->and($eager)->toBe(4);
});

it('menghilangkan N+1 pada anggota dengan eager loading role dan keanggotaan', function () {
    Member::factory()->count(8)->eligible()->create();

    $render = fn (Member $member) => [$member->roles->count(), $member->currentMembership?->expires_at];

    $lazy = countQueries(fn () => Member::all()->each($render));
    $eager = countQueries(fn () => Member::with(['roles', 'currentMembership'])->get()->each($render));

    expect($lazy)->toBe(17)->and($eager)->toBe(3);
});

it('menjaga jumlah query halaman katalog tetap konstan walau datanya bertambah', function () {
    $this->actingAs(makeAdmin());

    Book::factory()->count(3)->create();
    $few = countQueries(fn () => $this->get(route('books.index'))->assertOk());

    Book::factory()->count(7)->create();
    $many = countQueries(fn () => $this->get(route('books.index'))->assertOk());

    expect($many)->toBe($few);
});

it('menjaga jumlah query halaman peminjaman tetap konstan walau datanya bertambah', function () {
    $this->actingAs(makeAdmin());

    Loan::factory()->count(2)->has(LoanItem::factory()->count(2), 'items')->create();
    $few = countQueries(fn () => $this->get(route('loans.index'))->assertOk());

    Loan::factory()->count(6)->has(LoanItem::factory()->count(2), 'items')->create();
    $many = countQueries(fn () => $this->get(route('loans.index'))->assertOk());

    expect($many)->toBe($few);
});

it('menjaga jumlah query halaman anggota tetap konstan walau datanya bertambah', function () {
    $this->actingAs(makeAdmin());
    Role::factory()->create();

    Member::factory()->count(2)->eligible()->create();
    $few = countQueries(fn () => $this->get(route('members.index'))->assertOk());

    Member::factory()->count(6)->eligible()->create();
    $many = countQueries(fn () => $this->get(route('members.index'))->assertOk());

    expect($many)->toBe($few);
});

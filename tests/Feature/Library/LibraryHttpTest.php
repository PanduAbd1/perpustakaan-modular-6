<?php

use Database\Seeders\LibrarySeeder;
use Illuminate\Support\Facades\Event;
use Modules\Catalog\Models\Author;
use Modules\Catalog\Models\Book;
use Modules\Catalog\Models\Category;
use Modules\Catalog\Models\Publisher;
use Modules\Circulation\Events\BookBorrowed;
use Modules\Circulation\Models\Loan;
use Modules\Membership\Models\Member;
use Modules\Membership\Models\Role;

it('mengarahkan tamu ke halaman login', function () {
    $this->get(route('books.index'))->assertRedirect(route('login'));
    $this->get(route('loans.index'))->assertRedirect(route('login'));
});

it('mengizinkan user biasa melihat katalog tetapi bukan area admin', function () {
    $this->actingAs(makeRegularUser());

    $this->get(route('books.index'))->assertOk();
    $this->get(route('books.create'))->assertForbidden();
    $this->get(route('members.index'))->assertForbidden();
    $this->get(route('loans.index'))->assertForbidden();
});

it('merender semua halaman utama untuk admin', function () {
    $this->actingAs(makeAdmin());

    $book = makeBook();
    $member = makeEligibleMember();
    $loan = Loan::factory()->create();

    foreach ([
        route('books.index'),
        route('books.create'),
        route('books.edit', $book),
        route('books.trashed'),
        route('members.index'),
        route('members.create'),
        route('members.show', $member),
        route('members.trashed'),
        route('loans.index'),
        route('loans.create'),
        route('loans.show', $loan),
        route('loans.trashed'),
    ] as $url) {
        $this->get($url)->assertOk();
    }
});

it('memfilter katalog berdasarkan kata kunci, kategori, dan ketersediaan', function () {
    $this->actingAs(makeAdmin());

    $category = Category::factory()->create();
    $match = makeBook(2, ['title' => 'Pemrograman Laravel']);
    $match->categories()->attach($category);
    makeBook(2, ['title' => 'Memasak Rendang']);
    $empty = makeBook(0, ['title' => 'Pemrograman Kosong']);
    $empty->categories()->attach($category);

    $this->get(route('books.index', ['q' => 'Pemrograman', 'category' => $category->id, 'available' => 1]))
        ->assertOk()
        ->assertSee('Pemrograman Laravel')
        ->assertDontSee('Memasak Rendang')
        ->assertDontSee('Pemrograman Kosong');
});

it('menyimpan buku dengan ISBN-10 yang dinormalkan menjadi ISBN-13 beserta relasinya', function () {
    $this->actingAs(makeAdmin());

    $publisher = Publisher::factory()->create();
    $author = Author::factory()->create();
    $category = Category::factory()->create();

    $this->post(route('books.store'), [
        'title' => 'Struktur Data',
        'isbn' => '0-306-40615-2',
        'publisher_id' => $publisher->id,
        'published_year' => 2020,
        'stock' => 4,
        'author_ids' => [$author->id],
        'category_ids' => [$category->id],
    ])->assertRedirect(route('books.index'));

    $book = Book::with(['authors', 'categories'])->firstOrFail();

    expect($book->isbn->getValue())->toBe('9780306406157')
        ->and($book->authors)->toHaveCount(1)
        ->and($book->categories->first()->pivot->added_at)->not->toBeNull();
});

it('menolak ISBN tidak valid, stok negatif, dan ISBN ganda', function () {
    $this->actingAs(makeAdmin());
    makeBook(1, ['isbn' => '9780306406157']);

    $this->post(route('books.store'), ['title' => 'A', 'isbn' => '978-0-306-40615-8', 'stock' => 1])
        ->assertSessionHasErrors('isbn');

    $this->post(route('books.store'), ['title' => 'B', 'isbn' => '0-306-40615-2', 'stock' => 1])
        ->assertSessionHasErrors('isbn');

    $this->post(route('books.store'), ['title' => 'C', 'isbn' => '0-8044-2957-X', 'stock' => -1])
        ->assertSessionHasErrors('stock');

    expect(Book::count())->toBe(1);
});

it('memperbarui buku tanpa bentrok pada ISBN miliknya sendiri', function () {
    $this->actingAs(makeAdmin());
    $book = makeBook(2);

    $this->put(route('books.update', $book), [
        'title' => 'Judul Baru',
        'slug' => $book->slug,
        'isbn' => $book->isbn->getValue(),
        'stock' => 9,
    ])->assertRedirect(route('books.index'));

    expect($book->fresh()->title)->toBe('Judul Baru')->and($book->fresh()->stock)->toBe(9);
});

it('mendaftarkan anggota beserta role dan masa keanggotaan', function () {
    $this->actingAs(makeAdmin());
    $role = Role::factory()->create(['max_loans' => 4, 'loan_days' => 10]);

    $this->post(route('members.store'), [
        'name' => 'Budi Santoso',
        'email' => 'Budi@Contoh.test',
        'role_ids' => [$role->id],
        'months' => 6,
    ])->assertRedirect(route('members.index'));

    $member = Member::with(['roles', 'currentMembership'])->firstOrFail();

    expect($member->email)->toBe('budi@contoh.test')
        ->and($member->roles)->toHaveCount(1)
        ->and($member->hasValidMembership())->toBeTrue()
        ->and($member->loanQuota())->toBe(4)
        ->and($member->loanDays())->toBe(10);
});

it('menjalankan alur peminjaman dan pengembalian lewat HTTP', function () {
    Event::fake([BookBorrowed::class]);
    $this->actingAs(makeAdmin());

    $member = makeEligibleMember();
    $book = makeBook(3);

    $this->post(route('loans.store'), [
        'member_id' => $member->id,
        'items' => [['book_id' => $book->id, 'quantity' => 2]],
    ])->assertRedirect();

    $loan = Loan::firstOrFail();

    $this->get(route('loans.show', $loan))->assertOk()->assertSee($book->title)->assertSee('Kembalikan Buku');
    expect($book->fresh()->stock)->toBe(1);

    $this->patch(route('loans.return', $loan))->assertRedirect(route('loans.show', $loan));

    expect($loan->fresh()->isReturned())->toBeTrue()
        ->and($book->fresh()->stock)->toBe(3);
});

it('menampilkan error dan rollback ketika stok tidak cukup lewat HTTP', function () {
    Event::fake([BookBorrowed::class]);
    $this->actingAs(makeAdmin());

    $member = makeEligibleMember();
    $book = makeBook(1);

    $this->post(route('loans.store'), [
        'member_id' => $member->id,
        'items' => [['book_id' => $book->id, 'quantity' => 2]],
    ])->assertSessionHasErrors('stock');

    expect(Loan::count())->toBe(0)->and($book->fresh()->stock)->toBe(1);
});

it('memvalidasi form peminjaman', function () {
    $this->actingAs(makeAdmin());

    $this->post(route('loans.store'), [])->assertSessionHasErrors(['member_id', 'items']);
});

it('mengembalikan JSON 422 untuk pelanggaran aturan domain pada permintaan JSON', function () {
    $this->actingAs(makeAdmin());

    $member = makeEligibleMember(1);
    $book = makeBook(5);

    $this->postJson(route('loans.store'), [
        'member_id' => $member->id,
        'items' => [['book_id' => $book->id, 'quantity' => 2]],
    ])->assertStatus(422)->assertJsonPath('error', 'member_not_eligible');
});

it('seeder perpustakaan menghasilkan data yang konsisten', function () {
    $this->seed(LibrarySeeder::class);

    expect(Book::count())->toBe(30)
        ->and(Category::count())->toBe(10)
        ->and(Role::count())->toBe(3)
        ->and(Member::count())->toBe(8)
        ->and(Loan::count())->toBe(3)
        ->and(Loan::overdue()->count())->toBe(1)
        ->and(Book::where('stock', '<', 0)->count())->toBe(0)
        ->and(Book::inCategory(Category::where('name', 'Teknologi')->value('id'))->count())->toBe(14);
});

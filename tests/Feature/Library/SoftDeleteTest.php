<?php

use Illuminate\Support\Facades\Event;
use Modules\Catalog\Models\Book;
use Modules\Circulation\Events\BookBorrowed;
use Modules\Circulation\Models\Loan;
use Modules\Membership\Models\Member;

it('soft delete dan restore pada buku', function () {
    $book = makeBook();
    $book->delete();

    expect(Book::count())->toBe(0)
        ->and(Book::withTrashed()->count())->toBe(1)
        ->and(Book::onlyTrashed()->count())->toBe(1);

    $book->restore();

    expect(Book::count())->toBe(1)->and(Book::onlyTrashed()->count())->toBe(0);
});

it('soft delete dan restore pada anggota', function () {
    $member = Member::factory()->create();
    $member->delete();

    expect(Member::count())->toBe(0)->and(Member::withTrashed()->count())->toBe(1);

    $member->restore();

    expect(Member::count())->toBe(1);
});

it('soft delete dan restore pada peminjaman', function () {
    $loan = Loan::factory()->create();
    $loan->delete();

    expect(Loan::count())->toBe(0)->and(Loan::withTrashed()->count())->toBe(1);

    $loan->restore();

    expect(Loan::count())->toBe(1);
});

it('admin dapat menghapus lalu memulihkan buku lewat HTTP', function () {
    $this->actingAs(makeAdmin());
    $book = makeBook();

    $this->delete(route('books.destroy', $book))->assertRedirect(route('books.index'));
    expect(Book::count())->toBe(0);

    $this->get(route('books.trashed'))->assertOk()->assertSee($book->title);

    $this->patch(route('books.restore', $book->id))->assertRedirect(route('books.trashed'));
    expect(Book::count())->toBe(1);
});

it('admin dapat menghapus lalu memulihkan anggota lewat HTTP', function () {
    $this->actingAs(makeAdmin());
    $member = Member::factory()->create();

    $this->delete(route('members.destroy', $member))->assertRedirect(route('members.index'));
    expect(Member::count())->toBe(0);

    $this->get(route('members.trashed'))->assertOk()->assertSee($member->name);

    $this->patch(route('members.restore', $member->id))->assertRedirect(route('members.trashed'));
    expect(Member::count())->toBe(1);
});

it('user biasa tidak boleh menghapus maupun memulihkan', function () {
    $this->actingAs(makeRegularUser());
    $book = makeBook();
    $trashed = makeBook();
    $trashed->delete();

    $this->delete(route('books.destroy', $book))->assertForbidden();
    $this->patch(route('books.restore', $trashed->id))->assertForbidden();
    $this->get(route('books.trashed'))->assertForbidden();

    expect(Book::count())->toBe(1)->and(Book::onlyTrashed()->count())->toBe(1);
});

it('menolak penghapusan anggota yang masih memiliki pinjaman aktif', function () {
    Event::fake([BookBorrowed::class]);
    $this->actingAs(makeAdmin());

    $member = makeEligibleMember();
    loanService()->borrow($member->id, [['book_id' => makeBook()->id]]);

    $this->delete(route('members.destroy', $member))->assertSessionHasErrors('member');

    expect(Member::count())->toBe(1);
});

it('hanya mengizinkan penghapusan peminjaman yang sudah dikembalikan', function () {
    Event::fake([BookBorrowed::class]);
    $this->actingAs(makeAdmin());

    $member = makeEligibleMember();
    $loan = loanService()->borrow($member->id, [['book_id' => makeBook()->id]]);

    $this->delete(route('loans.destroy', $loan))->assertSessionHasErrors('loan');
    expect(Loan::count())->toBe(1);

    loanService()->returnLoan($loan);

    $this->delete(route('loans.destroy', $loan))->assertRedirect(route('loans.index'));
    expect(Loan::count())->toBe(0)->and(Loan::onlyTrashed()->count())->toBe(1);

    $this->patch(route('loans.restore', $loan->id))->assertRedirect(route('loans.trashed'));
    expect(Loan::count())->toBe(1);
});

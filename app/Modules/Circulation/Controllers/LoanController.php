<?php

namespace Modules\Circulation\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Circulation\Models\Loan;
use Modules\Circulation\Requests\StoreLoanRequest;
use Modules\Circulation\Services\LoanService;
use Modules\Shared\Contracts\BookServiceInterface;
use Modules\Shared\Contracts\MemberServiceInterface;

class LoanController extends Controller
{
    public function __construct(
        private LoanService $loans,
        private BookServiceInterface $books,
        private MemberServiceInterface $members,
    ) {}

    public function index(Request $request)
    {
        $loans = Loan::query()
            ->with('items')
            ->when($request->filled('status'), fn ($query) => $query->byStatus($request->string('status')->toString()))
            ->when($request->boolean('overdue'), fn ($query) => $query->overdue())
            ->latest('borrowed_at')
            ->paginate(10)
            ->withQueryString();

        return view('loans.index', compact('loans'));
    }

    public function create()
    {
        return view('loans.create', [
            'members' => $this->members->listBorrowers(),
            'books' => $this->books->listAvailableBooks(),
        ]);
    }

    public function store(StoreLoanRequest $request)
    {
        $loan = $this->loans->borrow(
            $request->integer('member_id'),
            $request->input('items'),
            notes: $request->input('notes'),
        );

        return redirect()
            ->route('loans.show', $loan)
            ->with('success', "Peminjaman {$loan->loan_number} berhasil dibuat.");
    }

    public function show(Loan $loan)
    {
        $loan->load('items');

        return view('loans.show', compact('loan'));
    }

    public function returnLoan(Loan $loan)
    {
        $loan = $this->loans->returnLoan($loan);

        $message = 'Buku berhasil dikembalikan.';

        if ((float) $loan->total_fine > 0) {
            $message .= ' Denda keterlambatan: Rp '.number_format((float) $loan->total_fine, 0, ',', '.').'.';
        }

        return redirect()->route('loans.show', $loan)->with('success', $message);
    }

    public function destroy(Loan $loan)
    {
        if (! $loan->isReturned()) {
            return back()->withErrors(['loan' => 'Peminjaman yang masih berjalan tidak boleh dihapus.']);
        }

        $loan->delete();

        return redirect()
            ->route('loans.index')
            ->with('success', 'Peminjaman dipindahkan ke arsip (soft delete).');
    }

    public function trashed()
    {
        $loans = Loan::onlyTrashed()
            ->with('items')
            ->latest('deleted_at')
            ->paginate(10);

        return view('loans.trashed', compact('loans'));
    }

    public function restore(Loan $loan)
    {
        $loan->restore();

        return redirect()
            ->route('loans.trashed')
            ->with('success', 'Peminjaman berhasil dipulihkan.');
    }
}

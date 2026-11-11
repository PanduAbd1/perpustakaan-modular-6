<?php

namespace Modules\Membership\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Membership\Models\Member;
use Modules\Membership\Models\Role;
use Modules\Membership\Requests\StoreMemberRequest;
use Modules\Membership\Services\MemberService;

class MemberController extends Controller
{
    public function __construct(private MemberService $members) {}

    public function index(Request $request)
    {
        $members = Member::query()
            ->with(['roles', 'currentMembership'])
            ->when($request->filled('q'), fn ($query) => $query->search($request->string('q')->toString()))
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return view('members.index', compact('members'));
    }

    public function create()
    {
        $roles = Role::query()->orderBy('name')->get();

        return view('members.create', compact('roles'));
    }

    public function store(StoreMemberRequest $request)
    {
        $this->members->register(
            $request->safe()->except(['role_ids', 'months']),
            $request->input('role_ids'),
            $request->integer('months', 12),
        );

        return redirect()
            ->route('members.index')
            ->with('success', 'Anggota berhasil didaftarkan.');
    }

    public function show(Member $member)
    {
        $member->load(['roles', 'memberships']);

        return view('members.show', compact('member'));
    }

    public function destroy(Member $member)
    {
        if ($member->active_loans > 0) {
            return back()->withErrors(['member' => 'Anggota masih memiliki pinjaman aktif dan tidak bisa dihapus.']);
        }

        $member->delete();

        return redirect()
            ->route('members.index')
            ->with('success', 'Anggota dipindahkan ke arsip (soft delete).');
    }

    public function trashed()
    {
        $members = Member::onlyTrashed()
            ->with('roles')
            ->latest('deleted_at')
            ->paginate(10);

        return view('members.trashed', compact('members'));
    }

    public function restore(Member $member)
    {
        $member->restore();

        return redirect()
            ->route('members.trashed')
            ->with('success', 'Anggota berhasil dipulihkan.');
    }
}

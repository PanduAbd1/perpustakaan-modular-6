<?php

namespace Modules\Membership\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Membership\Models\Member;
use Modules\Membership\Models\Membership;
use Modules\Shared\Contracts\MemberServiceInterface;
use Modules\Shared\ValueObjects\Email;

class MemberService implements MemberServiceInterface
{
    public function register(array $data, array $roleIds, int $months = 12): Member
    {
        $email = new Email($data['email']);

        return DB::transaction(function () use ($data, $roleIds, $months, $email) {
            $member = Member::create([
                'member_number' => 'MBR-'.now()->format('ym').'-'.Str::upper(Str::random(5)),
                'name' => $data['name'],
                'email' => $email->getValue(),
                'phone' => $data['phone'] ?? null,
                'address' => $data['address'] ?? null,
                'status' => Member::STATUS_ACTIVE,
                'joined_at' => now(),
            ]);

            $member->roles()->attach($roleIds);

            $member->memberships()->create([
                'starts_at' => today()->toDateString(),
                'expires_at' => today()->addMonths($months)->toDateString(),
                'status' => Membership::STATUS_ACTIVE,
            ]);

            return $member->load(['roles', 'currentMembership']);
        });
    }

    public function getMemberForLoan(int $memberId): ?array
    {
        $member = Member::query()->with(['roles', 'currentMembership'])->find($memberId);

        return $member ? $this->toArray($member) : null;
    }

    public function checkEligibility(int $memberId, int $quantity): ?string
    {
        $member = Member::query()->with(['roles', 'currentMembership'])->find($memberId);

        if (! $member) {
            return 'Anggota tidak ditemukan.';
        }

        if ($member->status === Member::STATUS_SUSPENDED) {
            return 'Keanggotaan sedang ditangguhkan.';
        }

        if (! $member->hasValidMembership()) {
            return 'Masa keanggotaan sudah berakhir atau belum aktif.';
        }

        $quota = $member->loanQuota();

        if ($member->active_loans + $quantity > $quota) {
            return "Kuota peminjaman terlampaui. Kuota {$quota} buku, sedang dipinjam {$member->active_loans}, diminta {$quantity}.";
        }

        return null;
    }

    public function markBorrowing(int $memberId, int $quantity): void
    {
        Member::query()
            ->whereKey($memberId)
            ->increment('active_loans', $quantity, ['status' => Member::STATUS_BORROWING]);
    }

    public function markReturned(int $memberId, int $quantity): void
    {
        $member = Member::withTrashed()->find($memberId);

        if (! $member) {
            return;
        }

        $remaining = max(0, $member->active_loans - $quantity);
        $status = $member->status;

        if ($remaining === 0 && $status === Member::STATUS_BORROWING) {
            $status = Member::STATUS_ACTIVE;
        }

        $member->update(['active_loans' => $remaining, 'status' => $status]);
    }

    public function listBorrowers(): array
    {
        return Member::query()
            ->borrowers()
            ->with(['roles', 'currentMembership'])
            ->orderBy('name')
            ->get()
            ->filter(fn (Member $member) => $member->hasValidMembership())
            ->map(fn (Member $member) => $this->toArray($member))
            ->values()
            ->all();
    }

    private function toArray(Member $member): array
    {
        return [
            'id' => $member->id,
            'member_number' => $member->member_number,
            'name' => $member->name,
            'email' => $member->email,
            'status' => $member->status,
            'active_loans' => $member->active_loans,
            'quota' => $member->loanQuota(),
            'loan_days' => $member->loanDays(),
            'membership_valid' => $member->hasValidMembership(),
        ];
    }
}

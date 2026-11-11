<?php

namespace Modules\Shared\Contracts;

interface MemberServiceInterface
{
    public function getMemberForLoan(int $memberId): ?array;

    public function checkEligibility(int $memberId, int $quantity): ?string;

    public function markBorrowing(int $memberId, int $quantity): void;

    public function markReturned(int $memberId, int $quantity): void;

    public function listBorrowers(): array;
}

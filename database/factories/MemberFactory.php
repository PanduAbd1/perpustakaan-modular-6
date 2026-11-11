<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Membership\Models\Member;
use Modules\Membership\Models\Membership;
use Modules\Membership\Models\Role;

class MemberFactory extends Factory
{
    protected $model = Member::class;

    public function definition(): array
    {
        return [
            'member_number' => 'MBR-'.now()->format('ym').'-'.Str::upper(Str::random(5)),
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->numerify('08##########'),
            'address' => fake()->address(),
            'status' => Member::STATUS_ACTIVE,
            'active_loans' => 0,
            'joined_at' => now(),
        ];
    }

    public function eligible(int $quota = 3, int $loanDays = 7): static
    {
        return $this->afterCreating(function (Member $member) use ($quota, $loanDays) {
            $member->roles()->attach(Role::factory()->create(['max_loans' => $quota, 'loan_days' => $loanDays]));

            Membership::factory()->for($member)->create();
        });
    }

    public function withExpiredMembership(): static
    {
        return $this->afterCreating(function (Member $member) {
            $member->roles()->attach(Role::factory()->create());

            Membership::factory()->for($member)->expired()->create();
        });
    }

    public function suspended(): static
    {
        return $this->state(fn () => ['status' => Member::STATUS_SUSPENDED]);
    }
}

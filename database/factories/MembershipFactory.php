<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Membership\Models\Member;
use Modules\Membership\Models\Membership;

class MembershipFactory extends Factory
{
    protected $model = Membership::class;

    public function definition(): array
    {
        return [
            'member_id' => Member::factory(),
            'starts_at' => today()->subMonth()->toDateString(),
            'expires_at' => today()->addMonths(11)->toDateString(),
            'status' => Membership::STATUS_ACTIVE,
        ];
    }

    public function expired(): static
    {
        return $this->state(fn () => [
            'starts_at' => today()->subYear()->toDateString(),
            'expires_at' => today()->subDay()->toDateString(),
        ]);
    }
}

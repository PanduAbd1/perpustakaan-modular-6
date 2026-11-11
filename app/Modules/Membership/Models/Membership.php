<?php

namespace Modules\Membership\Models;

use Database\Factories\MembershipFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[UseFactory(MembershipFactory::class)]
class Membership extends Model
{
    use HasFactory;

    public const STATUS_ACTIVE = 'aktif';

    public const STATUS_CANCELLED = 'dibatalkan';

    protected $fillable = ['member_id', 'starts_at', 'expires_at', 'status'];

    protected $casts = [
        'starts_at' => 'date:Y-m-d',
        'expires_at' => 'date:Y-m-d',
    ];

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE
            && $this->starts_at->lte(today())
            && $this->expires_at->gte(today());
    }
}

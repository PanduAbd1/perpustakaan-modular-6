<?php

namespace Modules\Membership\Models;

use Database\Factories\MemberFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

#[UseFactory(MemberFactory::class)]
class Member extends Model
{
    use HasFactory, SoftDeletes;

    public const STATUS_ACTIVE = 'aktif';

    public const STATUS_BORROWING = 'meminjam';

    public const STATUS_SUSPENDED = 'ditangguhkan';

    protected $fillable = [
        'user_id',
        'member_number',
        'name',
        'email',
        'phone',
        'address',
        'status',
        'active_loans',
        'joined_at',
    ];

    protected $casts = [
        'active_loans' => 'integer',
        'joined_at' => 'datetime',
    ];

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'member_role');
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(Membership::class);
    }

    public function currentMembership(): HasOne
    {
        return $this->hasOne(Membership::class)->ofMany('expires_at', 'max');
    }

    public function scopeSearch(Builder $query, string $keyword): Builder
    {
        return $query->where(function (Builder $q) use ($keyword) {
            $q->where('name', 'like', "%{$keyword}%")
                ->orWhere('email', 'like', "%{$keyword}%")
                ->orWhere('member_number', 'like', "%{$keyword}%");
        });
    }

    public function scopeBorrowers(Builder $query): Builder
    {
        return $query->whereIn('status', [self::STATUS_ACTIVE, self::STATUS_BORROWING]);
    }

    public function hasValidMembership(): bool
    {
        return $this->currentMembership !== null && $this->currentMembership->isActive();
    }

    public function loanQuota(): int
    {
        return (int) $this->roles->max('max_loans');
    }

    public function loanDays(): int
    {
        return (int) ($this->roles->max('loan_days') ?: 7);
    }
}

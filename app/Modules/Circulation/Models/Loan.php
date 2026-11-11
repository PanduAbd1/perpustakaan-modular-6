<?php

namespace Modules\Circulation\Models;

use Carbon\CarbonInterface;
use Database\Factories\LoanFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Shared\ValueObjects\Denda;

#[UseFactory(LoanFactory::class)]
class Loan extends Model
{
    use HasFactory, SoftDeletes;

    public const STATUS_BORROWED = 'dipinjam';

    public const STATUS_RETURNED = 'dikembalikan';

    protected $fillable = [
        'member_id',
        'loan_number',
        'member_name',
        'status',
        'borrowed_at',
        'due_at',
        'returned_at',
        'total_fine',
        'notes',
    ];

    protected $casts = [
        'borrowed_at' => 'datetime',
        'due_at' => 'datetime',
        'returned_at' => 'datetime',
        'total_fine' => 'decimal:2',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(LoanItem::class);
    }

    public function scopeByStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_BORROWED);
    }

    public function scopeOverdue(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_BORROWED)->where('due_at', '<', now());
    }

    public function scopeRecent(Builder $query, int $days = 30): Builder
    {
        return $query->where('borrowed_at', '>=', now()->subDays($days));
    }

    public function scopeForMember(Builder $query, int $memberId): Builder
    {
        return $query->where('member_id', $memberId);
    }

    public function isReturned(): bool
    {
        return $this->status === self::STATUS_RETURNED;
    }

    public function isOverdue(): bool
    {
        return ! $this->isReturned() && $this->due_at->isPast();
    }

    public function totalQuantity(): int
    {
        return (int) $this->items->sum('quantity');
    }

    public function estimatedFine(?CarbonInterface $at = null): Denda
    {
        $outstanding = (int) $this->items->whereNull('returned_at')->sum('quantity');

        return Denda::hitung($this->due_at, $at ?? now(), $outstanding);
    }
}

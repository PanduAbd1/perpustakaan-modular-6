<?php

namespace Modules\Circulation\Models;

use Database\Factories\LoanItemFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[UseFactory(LoanItemFactory::class)]
class LoanItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'loan_id',
        'book_id',
        'book_title',
        'book_isbn',
        'quantity',
        'returned_at',
        'fine_amount',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'returned_at' => 'datetime',
        'fine_amount' => 'decimal:2',
    ];

    public function loan(): BelongsTo
    {
        return $this->belongsTo(Loan::class);
    }
}

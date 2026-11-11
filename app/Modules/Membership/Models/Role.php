<?php

namespace Modules\Membership\Models;

use Database\Factories\RoleFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[UseFactory(RoleFactory::class)]
class Role extends Model
{
    use HasFactory;

    protected $table = 'member_roles';

    protected $fillable = ['name', 'slug', 'description', 'max_loans', 'loan_days'];

    protected $casts = [
        'max_loans' => 'integer',
        'loan_days' => 'integer',
    ];

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(Member::class, 'member_role');
    }
}

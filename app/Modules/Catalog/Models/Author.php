<?php

namespace Modules\Catalog\Models;

use Database\Factories\AuthorFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[UseFactory(AuthorFactory::class)]
class Author extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'bio'];

    public function books(): BelongsToMany
    {
        return $this->belongsToMany(Book::class, 'author_book');
    }
}

<?php

namespace Modules\Catalog\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Modules\Shared\ValueObjects\Isbn;

class IsbnCast implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): ?Isbn
    {
        return $value === null ? null : new Isbn((string) $value);
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }

        $isbn = $value instanceof Isbn ? $value : new Isbn((string) $value);

        return $isbn->getValue();
    }
}

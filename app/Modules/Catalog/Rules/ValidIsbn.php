<?php

namespace Modules\Catalog\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Modules\Shared\ValueObjects\Isbn;

class ValidIsbn implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! Isbn::isValid($value)) {
            $fail('Format ISBN tidak valid. Gunakan ISBN-10 atau ISBN-13 dengan checksum yang benar.');
        }
    }
}

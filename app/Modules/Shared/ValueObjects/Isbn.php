<?php

namespace Modules\Shared\ValueObjects;

use InvalidArgumentException;
use Stringable;

final class Isbn implements Stringable
{
    private readonly string $value;

    public function __construct(string $value)
    {
        $normalized = self::normalize($value);

        if (preg_match('/^\d{9}[\dX]$/', $normalized) === 1) {
            if (! self::hasValidIsbn10Checksum($normalized)) {
                throw new InvalidArgumentException("Checksum ISBN-10 tidak valid: {$value}");
            }

            $normalized = self::convertToIsbn13($normalized);
        } elseif (preg_match('/^97[89]\d{10}$/', $normalized) === 1) {
            if (! self::hasValidIsbn13Checksum($normalized)) {
                throw new InvalidArgumentException("Checksum ISBN-13 tidak valid: {$value}");
            }
        } else {
            throw new InvalidArgumentException("Format ISBN harus 10 digit atau 13 digit berawalan 978/979: {$value}");
        }

        $this->value = $normalized;
    }

    public static function isValid(string $value): bool
    {
        try {
            new self($value);

            return true;
        } catch (InvalidArgumentException) {
            return false;
        }
    }

    public static function tryFrom(?string $value): ?self
    {
        if ($value === null || ! self::isValid($value)) {
            return null;
        }

        return new self($value);
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function toIsbn10(): ?string
    {
        if (! str_starts_with($this->value, '978')) {
            return null;
        }

        $body = substr($this->value, 3, 9);
        $sum = 0;

        foreach (str_split($body) as $index => $digit) {
            $sum += (10 - $index) * (int) $digit;
        }

        $check = (11 - ($sum % 11)) % 11;

        return $body.($check === 10 ? 'X' : (string) $check);
    }

    public function equals(Isbn $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }

    private static function normalize(string $value): string
    {
        return strtoupper((string) preg_replace('/[\s\-]/', '', $value));
    }

    private static function hasValidIsbn10Checksum(string $isbn): bool
    {
        $sum = 0;

        foreach (str_split($isbn) as $index => $char) {
            $digit = $char === 'X' ? 10 : (int) $char;
            $sum += (10 - $index) * $digit;
        }

        return $sum % 11 === 0;
    }

    private static function hasValidIsbn13Checksum(string $isbn): bool
    {
        return self::checkDigit13(substr($isbn, 0, 12)) === (int) $isbn[12];
    }

    private static function convertToIsbn13(string $isbn10): string
    {
        $body = '978'.substr($isbn10, 0, 9);

        return $body.self::checkDigit13($body);
    }

    private static function checkDigit13(string $first12): int
    {
        $sum = 0;

        foreach (str_split($first12) as $index => $digit) {
            $sum += (int) $digit * ($index % 2 === 0 ? 1 : 3);
        }

        return (10 - ($sum % 10)) % 10;
    }
}

<?php

use Modules\Shared\ValueObjects\Isbn;

it('menerima ISBN-13 valid dengan tanda hubung dan menormalkannya', function () {
    $isbn = new Isbn('978-0-306-40615-7');

    expect($isbn->getValue())->toBe('9780306406157')
        ->and((string) $isbn)->toBe('9780306406157');
});

it('mengonversi ISBN-10 valid menjadi ISBN-13', function () {
    expect((new Isbn('0-306-40615-2'))->getValue())->toBe('9780306406157');
});

it('menerima ISBN-10 dengan digit cek X', function () {
    $isbn = new Isbn('0-8044-2957-X');

    expect($isbn->getValue())->toHaveLength(13)->toStartWith('978');
});

it('mengonversi balik ISBN-13 menjadi ISBN-10', function () {
    expect((new Isbn('9780306406157'))->toIsbn10())->toBe('0306406152');
});

it('menganggap ISBN-10 dan ISBN-13 yang setara sebagai sama', function () {
    expect((new Isbn('0-306-40615-2'))->equals(new Isbn('978-0-306-40615-7')))->toBeTrue();
});

it('menolak checksum yang salah', function (string $value) {
    new Isbn($value);
})->with([
    '978-0-306-40615-8',
    '0-306-40615-3',
])->throws(InvalidArgumentException::class);

it('menolak format yang salah', function (string $value) {
    new Isbn($value);
})->with([
    'bukan isbn',
    '97803064061',
    '9770306406157',
    '',
])->throws(InvalidArgumentException::class);

it('menyediakan isValid dan tryFrom tanpa melempar exception', function () {
    expect(Isbn::isValid('978-0-306-40615-7'))->toBeTrue()
        ->and(Isbn::isValid('123'))->toBeFalse()
        ->and(Isbn::tryFrom('123'))->toBeNull()
        ->and(Isbn::tryFrom(null))->toBeNull()
        ->and(Isbn::tryFrom('0306406152'))->toBeInstanceOf(Isbn::class);
});

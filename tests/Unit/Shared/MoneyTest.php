<?php

use Modules\Shared\ValueObjects\Email;
use Modules\Shared\ValueObjects\Money;

it('menolak nilai negatif', function () {
    new Money(-1);
})->throws(InvalidArgumentException::class);

it('menambah dan mengalikan Money', function () {
    $money = (new Money(1000))->add(new Money(500))->multiply(2);

    expect($money->getAmount())->toBe(3000.0)
        ->and($money->getCurrency())->toBe('IDR');
});

it('menolak penjumlahan beda mata uang', function () {
    (new Money(1000, 'IDR'))->add(new Money(1, 'USD'));
})->throws(InvalidArgumentException::class);

it('memvalidasi dan menormalkan Email', function () {
    expect((new Email('Budi@Contoh.TEST'))->getValue())->toBe('budi@contoh.test');

    new Email('bukan-email');
})->throws(InvalidArgumentException::class);

<?php

use Carbon\Carbon;
use Modules\Shared\ValueObjects\Denda;

it('tidak mengenakan denda jika dikembalikan sebelum atau tepat pada jatuh tempo', function () {
    $due = Carbon::parse('2026-11-10 08:00:00');

    expect(Denda::hitung($due, Carbon::parse('2026-11-08 09:00:00'))->getAmount()->getAmount())->toBe(0.0)
        ->and(Denda::hitung($due, Carbon::parse('2026-11-10 20:00:00'))->isLate())->toBeFalse();
});

it('menghitung denda otomatis per hari per buku', function () {
    $denda = Denda::hitung(Carbon::parse('2026-11-10'), Carbon::parse('2026-11-13'), 2);

    expect($denda->getLateDays())->toBe(3)
        ->and($denda->getQuantity())->toBe(2)
        ->and($denda->getAmount()->getAmount())->toBe(6000.0)
        ->and($denda->isLate())->toBeTrue();
});

it('mendukung tarif per hari kustom', function () {
    $denda = Denda::hitung(Carbon::parse('2026-11-10'), Carbon::parse('2026-11-12'), 1, 500);

    expect($denda->getAmount()->getAmount())->toBe(1000.0)
        ->and($denda->getTarifPerHari()->getAmount())->toBe(500.0);
});

it('menolak jumlah buku negatif', function () {
    Denda::hitung(Carbon::parse('2026-11-10'), Carbon::parse('2026-11-12'), -1);
})->throws(InvalidArgumentException::class);

it('menyediakan denda nol dan perbandingan kesetaraan', function () {
    $due = Carbon::parse('2026-11-10');
    $return = Carbon::parse('2026-11-12');

    expect(Denda::nol()->getAmount()->getAmount())->toBe(0.0)
        ->and(Denda::hitung($due, $return, 2)->equals(Denda::hitung($due, $return, 2)))->toBeTrue()
        ->and(Denda::hitung($due, $return, 2)->equals(Denda::hitung($due, $return, 1)))->toBeFalse();
});

<?php

namespace Modules\Shared\ValueObjects;

use Carbon\CarbonInterface;
use InvalidArgumentException;
use Stringable;

final class Denda implements Stringable
{
    public const TARIF_PER_HARI = 1000.0;

    private function __construct(
        private readonly int $lateDays,
        private readonly int $quantity,
        private readonly Money $tarifPerHari,
        private readonly Money $amount,
    ) {}

    public static function hitung(
        CarbonInterface $jatuhTempo,
        CarbonInterface $tanggalKembali,
        int $jumlah = 1,
        float $tarifPerHari = self::TARIF_PER_HARI,
    ): self {
        if ($jumlah < 0) {
            throw new InvalidArgumentException('Jumlah buku tidak boleh negatif');
        }

        $due = $jatuhTempo->copy()->startOfDay();
        $returned = $tanggalKembali->copy()->startOfDay();
        $lateDays = $returned->greaterThan($due) ? (int) round($due->diffInDays($returned)) : 0;

        $tarif = new Money($tarifPerHari);

        return new self($lateDays, $jumlah, $tarif, $tarif->multiply($lateDays * $jumlah));
    }

    public static function nol(): self
    {
        $tarif = new Money(self::TARIF_PER_HARI);

        return new self(0, 0, $tarif, new Money(0));
    }

    public function getLateDays(): int
    {
        return $this->lateDays;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function getTarifPerHari(): Money
    {
        return $this->tarifPerHari;
    }

    public function getAmount(): Money
    {
        return $this->amount;
    }

    public function isLate(): bool
    {
        return $this->lateDays > 0;
    }

    public function equals(Denda $other): bool
    {
        return $this->lateDays === $other->lateDays
            && $this->quantity === $other->quantity
            && $this->amount->equals($other->amount);
    }

    public function __toString(): string
    {
        return sprintf('%d hari x %d buku = %s', $this->lateDays, $this->quantity, $this->amount);
    }
}

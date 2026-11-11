<?php

namespace Modules\Shared\Exceptions;

use Exception;

class InsufficientStockException extends Exception
{
    public function __construct(
        private readonly string $itemName,
        private readonly int $available,
        private readonly int $requested,
    ) {
        parent::__construct(
            "Stok '{$itemName}' tidak mencukupi. Tersedia: {$available}, diminta: {$requested}."
        );
    }

    public function getItemName(): string
    {
        return $this->itemName;
    }

    public function getAvailable(): int
    {
        return $this->available;
    }

    public function getRequested(): int
    {
        return $this->requested;
    }

    public function render($request)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => $this->getMessage(),
                'error' => 'insufficient_stock',
                'details' => [
                    'item' => $this->itemName,
                    'available' => $this->available,
                    'requested' => $this->requested,
                ],
            ], 422);
        }

        return back()->withInput()->withErrors(['stock' => $this->getMessage()]);
    }
}

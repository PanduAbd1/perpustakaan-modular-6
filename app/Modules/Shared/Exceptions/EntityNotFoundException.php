<?php

namespace Modules\Shared\Exceptions;

use Exception;

class EntityNotFoundException extends Exception
{
    public function __construct(private readonly string $entity, private readonly int $id)
    {
        parent::__construct("{$entity} dengan ID {$id} tidak ditemukan.");
    }

    public function getEntity(): string
    {
        return $this->entity;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function render($request)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => $this->getMessage(),
                'error' => 'entity_not_found',
            ], 404);
        }

        return back()->withInput()->withErrors(['entity' => $this->getMessage()]);
    }
}

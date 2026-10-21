<?php

declare(strict_types=1);

namespace Karagiannidis\Ej2Grid\DTOs;

use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\JsonResponse;
use JsonSerializable;

final readonly class GridResult implements JsonSerializable, Responsable
{
    /**
     * @param  list<array<string, mixed>>  $rows
     */
    public function __construct(
        public array $rows,
        public ?int $count,
    ) {}

    /**
     * @return list<array<string, mixed>>|array{result: list<array<string, mixed>>, count: int}
     */
    public function jsonSerialize(): array
    {
        if ($this->count === null) {
            return $this->rows;
        }

        return ['result' => $this->rows, 'count' => $this->count];
    }

    public function toResponse(mixed $request): JsonResponse
    {
        return new JsonResponse($this);
    }
}

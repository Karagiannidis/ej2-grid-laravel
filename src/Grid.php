<?php

declare(strict_types=1);

namespace Karagiannidis\Ej2Grid;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Karagiannidis\Ej2Grid\DTOs\GridRequest;

abstract class Grid
{
    /**
     * @return Builder<Model>
     */
    abstract protected function query(GridRequest $request): Builder;

    /**
     * @return list<Field>
     */
    abstract protected function fields(): array;

    /**
     * @return Builder<Model>
     */
    final public function buildQuery(GridRequest $request): Builder
    {
        return $this->query($request);
    }

    final public function pageSizeLimit(): ?int
    {
        return $this->maxPageSize();
    }

    /**
     * The largest page this grid serves. Null serves every row at once,
     * for a grid with paging turned off.
     */
    protected function maxPageSize(): ?int
    {
        return 500;
    }
}

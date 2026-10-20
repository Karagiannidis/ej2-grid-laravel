<?php

declare(strict_types=1);

namespace Karagiannidis\Ej2Grid;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Karagiannidis\Ej2Grid\DTOs\GridRequest;

/**
 * Applies what an EJ2 request asks for to a grid's base query.
 */
final class GridQuery
{
    /**
     * @return Builder<Model>
     */
    public function build(Grid $grid, GridRequest $request): Builder
    {
        $query = $grid->buildQuery($request);

        $this->orderByKey($query);
        $this->paginate($query, $request);

        return $query;
    }

    /**
     * Goes last and breaks ties left by any other ordering.
     *
     * @param  Builder<Model>  $query
     */
    private function orderByKey(Builder $query): void
    {
        $query->orderBy($query->getModel()->getQualifiedKeyName());
    }

    /**
     * @param  Builder<Model>  $query
     */
    private function paginate(Builder $query, GridRequest $request): void
    {
        if ($request->take === null) {
            return;
        }

        $query->skip($request->skip)->take($request->take);
    }
}

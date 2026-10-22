<?php

declare(strict_types=1);

namespace Karagiannidis\Ej2Grid;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Karagiannidis\Ej2Grid\DTOs\GridRequest;
use Karagiannidis\Ej2Grid\DTOs\GridResult;

/**
 * Runs a grid's query for an EJ2 request and shapes the rows EJ2 expects.
 */
final class GridFetcher
{
    public function fetch(Grid $grid, GridRequest $request): GridResult
    {
        $query = new GridQuery()->build($grid, $request);
        $fields = $this->fields($grid, $request);

        $rows = [];

        foreach ($query->get() as $model) {
            $rows[] = $this->row($model, $fields);
        }

        $count = $request->requiresCounts ? $this->count($query, $request, count($rows)) : null;

        return new GridResult($rows, $count);
    }

    /**
     * @param  Builder<Model>  $query
     */
    private function count(Builder $query, GridRequest $request, int $rows): int
    {
        if ($request->take === null) {
            return $rows;
        }

        if ($rows > 0 && $rows < $request->take) {
            return $request->skip + $rows;
        }

        return $query->toBase()->getCountForPagination();
    }

    /**
     * A filter dropdown gets only the field it lists the values of.
     *
     * @return array<string, Field>
     */
    private function fields(Grid $grid, GridRequest $request): array
    {
        $fields = $grid->fieldMap();

        if ($request->distinct === null) {
            return $fields;
        }

        return [$request->distinct => $fields[$request->distinct]];
    }

    /**
     * @param  array<string, Field>  $fields
     * @return array<string, mixed>
     */
    private function row(Model $model, array $fields): array
    {
        $row = [];

        foreach ($fields as $name => $field) {
            $row[$name] = $field->valueFrom($model);
        }

        return $row;
    }
}

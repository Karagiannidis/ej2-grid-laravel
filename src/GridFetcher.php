<?php

declare(strict_types=1);

namespace Karagiannidis\Ej2Grid;

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
        $fields = $grid->fieldMap();

        $count = $request->requiresCounts ? $query->toBase()->getCountForPagination() : null;

        $rows = [];

        foreach ($query->get() as $model) {
            $rows[] = $this->row($model, $fields);
        }

        return new GridResult($rows, $count);
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

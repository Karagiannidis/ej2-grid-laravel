<?php

declare(strict_types=1);

namespace Karagiannidis\Ej2Grid;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\LazyCollection;
use Karagiannidis\Ej2Grid\DTOs\GridRequest;
use Karagiannidis\Ej2Grid\DTOs\GridResult;
use Karagiannidis\Ej2Grid\Exceptions\InvalidGridException;
use Karagiannidis\Ej2Grid\Http\GridRequestParser;

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

    final public function handle(Request $request): GridResult
    {
        $gridRequest = new GridRequestParser()->parse($request->all());

        return new GridFetcher()->fetch($this, $gridRequest);
    }

    /**
     * Every row the request's filters, search and sorting leave, shaped like
     * the rows handle() answers with, for exporting on the server. The page
     * the request asks for and the page size limit are ignored.
     *
     * @return LazyCollection<int, array<string, mixed>>
     */
    final public function rows(Request $request): LazyCollection
    {
        $gridRequest = new GridRequestParser()->parse($request->all());

        return new GridFetcher()->rows($this, $gridRequest);
    }

    /**
     * @return Builder<Model>
     */
    final public function buildQuery(GridRequest $request): Builder
    {
        return $this->query($request);
    }

    /**
     * The grid's fields keyed by the name EJ2 uses for them.
     *
     * @return array<string, Field>
     */
    final public function fieldMap(): array
    {
        $map = [];

        foreach ($this->fields() as $field) {
            if (isset($map[$field->name])) {
                throw InvalidGridException::duplicateField(static::class, $field->name);
            }

            $map[$field->name] = $field;
        }

        return $map;
    }

    final public function pageSizeLimit(): ?int
    {
        return $this->maxPageSize();
    }

    /**
     * The largest page this grid serves. Null serves every row at once,
     * for a grid with paging turned off. EJ2's checkbox
     * filter asks for 1000 rows (filterChoiceCount) by default.
     */
    protected function maxPageSize(): ?int
    {
        return 1000;
    }
}

# EJ2 Grid for Laravel

A server-side data source for the [Syncfusion EJ2 Grid](https://ej2.syncfusion.com/documentation/grid/getting-started)
in Laravel. The grid's `UrlAdaptor` posts what the user did (paging, sorting,
filtering, searching, grouping), and the package answers with the matching rows
from an Eloquent query.

- Paging with a total count, and a page size limit
- Sorting on several columns
- Filtering with every EJ2 operator, the checkbox and Excel filters, and empty cells handled the way EJ2 shows them
- Search across text columns
- Grouping
- Filter dropdown values
- Columns from related models (`team.name`, `author.team.name`)
- Every matching row for exporting on the server
- Works on SQLite, MySQL and Postgres

## Requirements

- PHP 8.5+
- Laravel 13+

## Installation

```bash
composer require karagiannidis/ej2-grid-laravel
```

## Usage

### 1. Define a grid

A grid has a base query and the fields EJ2 may ask for. Field names are the
`field` names of the EJ2 columns.

```php
use App\Models\Person;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Karagiannidis\Ej2Grid\DTOs\GridRequest;
use Karagiannidis\Ej2Grid\Fields\BooleanField;
use Karagiannidis\Ej2Grid\Fields\CustomField;
use Karagiannidis\Ej2Grid\Fields\DateField;
use Karagiannidis\Ej2Grid\Fields\DateTimeField;
use Karagiannidis\Ej2Grid\Fields\NumericField;
use Karagiannidis\Ej2Grid\Fields\TextField;
use Karagiannidis\Ej2Grid\Grid;

final class PersonGrid extends Grid
{
    protected function query(GridRequest $request): Builder
    {
        return Person::query()->where('company_id', auth()->user()->company_id);
    }

    protected function fields(): array
    {
        return [
            new NumericField('id')->nonInteractive(),
            new TextField('name'),
            new TextField('email'),
            new NumericField('age'),
            new BooleanField('active'),
            new DateField('birthDate', 'birth_date'),
            new DateTimeField('createdAt', 'created_at'),
            new TextField('team.name'),
            new CustomField('initials', fn (Model $person): string => mb_substr($person->name, 0, 1)),
        ];
    }
}
```

The grid only ever reads the columns its fields name. A request for any other
field is rejected, so EJ2 can't sort, filter or search on columns you didn't
expose.

### 2. Add a route

```php
use Illuminate\Http\Request;

Route::post('people/grid', fn (Request $request) => new PersonGrid()->handle($request));
```

`handle()` returns a response EJ2 reads: `{ "result": [...], "count": 42 }` when
the grid asks for a count, a plain list of rows otherwise.

### 3. Point the EJ2 Grid at it

```js
import { DataManager, UrlAdaptor } from '@syncfusion/ej2-data';

const grid = new Grid({
    dataSource: new DataManager({
        url: '/people/grid',
        adaptor: new UrlAdaptor(),
        headers: [{ 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }],
    }),
    allowPaging: true,
    allowSorting: true,
    allowFiltering: true,
    filterSettings: { type: 'Excel' },
    toolbar: ['Search'],
    columns: [
        { field: 'name', headerText: 'Name' },
        { field: 'age', headerText: 'Age', type: 'number' },
        { field: 'active', headerText: 'Active', type: 'boolean' },
        { field: 'birthDate', headerText: 'Born', type: 'dateonly', format: 'yMd' },
        { field: 'createdAt', headerText: 'Created', type: 'datetime', format: 'yMd HH:mm' },
        { field: 'team.name', headerText: 'Team' },
    ],
});
```

## Fields

Each field takes the name EJ2 uses and, optionally, the database column when it
differs: `new DateField('birthDate', 'birth_date')`.

| Field | For | Searchable by default |
|---|---|---|
| `TextField` | Strings | Yes |
| `NumericField` | Integers and decimals | No |
| `BooleanField` | Booleans. 1/0 from MySQL and SQLite come back as `true`/`false` | No |
| `DateField` | Calendar dates (a `DATE` column) | No |
| `DateTimeField` | Points in time (a `DATETIME`/`TIMESTAMP` column) | No |
| `CustomField` | A value computed in PHP from the row's model. Display only | No |

Every column field is sortable, filterable and groupable. Change that per field:

```php
new TextField('notes')->searchable(false)->sortable(false);
new NumericField('id')->nonInteractive(); // shown, but no search, sort, filter or group
```

### Dates

- **`DateField`** sends a date as midnight with no timezone, so every consumer sees
  the same day. Give the EJ2 column `type: 'dateonly'`: with `type: 'date'`, EJ2
  filters with the viewer's local midnight in UTC, and the day can't be told
  without the viewer's timezone. Such filters are rejected.
- **`DateTimeField`** sends the instant in UTC (`2024-03-15T09:30:00Z`), which EJ2
  shows in the viewer's timezone. Stored values are read in the application
  timezone (`app.timezone`), as Laravel stores them.

### Custom fields

A `CustomField` runs its closure for each row, after the query. If it reads a
relation, eager load it in `query()` to avoid one query per row:

```php
protected function query(GridRequest $request): Builder
{
    return Person::query()->with('tags');
}
```

A custom field has no column, so EJ2 can't search, sort, filter or group by it.

### Values and casts

Column values are read through the model, so your model's casts and accessors
for those columns apply. Values from related models are read from a join and
skip the related model's casts. The fields format them for EJ2 themselves.

## Related models

A field name with dots reads a column of a related model:

```php
new TextField('team.name'),            // Person belongsTo Team
new TextField('passport.number'),      // Person hasOne Passport
new TextField('author.team.name'),     // as deep as you like
```

- Only `BelongsTo` and `HasOne` relations are supported, since a grid cell holds
  one value. Any other relation throws `InvalidGridException`.
- Each relation is left joined once, so related fields sort, filter, search and
  list dropdown values like any other field.
- A row without the related model gets `null` values, which count as empty cells
  (the "Blanks" checkbox).
- A `HasOne` expects at most one related row. Extra rows would repeat the grid row.
- EJ2 reads `team.name` from nested JSON. The rows come back nested:
  `{ "name": "Alice", "team": { "name": "Red" } }`.

## Paging and the page size limit

A grid serves at most 1000 rows per request by default, which is also what EJ2's
filter dropdown asks for. Override `maxPageSize()` to change it, or return
`null` to serve every row at once to a grid without paging:

```php
protected function maxPageSize(): ?int
{
    return null;
}
```

## Searching

The search box searches the fields that are searchable: only `TextField`s by
default. EJ2 sends every column, and the package skips the ones that aren't.

## Grouping

With the `UrlAdaptor`, EJ2 groups the rows of the current page in the browser.
The package returns them sorted by the grouped columns first, so each group
stays together.

## Filter dropdown

The checkbox and Excel filters list a column's values by asking for 1000 rows
and removing duplicates in the browser. A column with more than 1000 rows may
miss values that only appear later.

To list every value, have the grid ask for them directly. The package then
returns each value once, the empty one included, with how many there are:

```js
const grid = new Grid({
    // ...
    actionBegin(args) {
        if (args.requestType === 'filterchoicerequest' || args.requestType === 'filterSearchBegin') {
            args.query.addParams('distinct', args.filterModel.options.field);
        }
    },
});
```

## Exporting on the server

`rows()` takes the same request as `handle()` and returns every row its
filters, search and sorting match, ignoring the page and the page size limit.
It returns a `LazyCollection` that reads a thousand rows at a time, so large
exports stay out of memory. Each row has the same shape `handle()` returns.

```php
Route::post('people/export', function (Request $request) {
    return response()->streamDownload(function () use ($request) {
        $out = fopen('php://output', 'w');

        foreach (new PersonGrid()->rows($request) as $row) {
            fputcsv($out, [$row['name'], $row['email'], $row['team']['name']]);
        }
    }, 'people.csv');
});
```

The browser can send the grid's current filters, search and sorting with what
EJ2 would post for them:

```js
const query = grid.getDataModule().generateQuery(true); // true leaves out paging
const body = grid.dataSource.adaptor.processQuery(grid.dataSource, query).data;
```

EJ2's own Excel and PDF export ("All pages") asks for every row in one request,
so a grid with more rows than its page size limit answers it with a 422.

## Errors

- **`InvalidRequestException`**: the request asks for something the grid doesn't
  allow or EJ2 never sends, like an unknown field, a sort on a non-sortable
  field, or a page over the limit.
- **`InvalidGridException`**: a mistake in the grid's definition, like a field
  defined twice or a relation with many rows per grid row.

Both implement `Karagiannidis\Ej2Grid\Exceptions\Ej2GridException`.

## Testing

```bash
composer check          # Rector, Pint, PHPStan, tests with 100% code and type coverage
composer test:databases # tests on SQLite, MySQL and Postgres, via Docker
```

## License

MIT. See [LICENSE](LICENSE).

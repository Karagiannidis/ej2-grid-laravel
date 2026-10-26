# TODO

What's planned after 0.1.0, roughly in order. Done work is in the [changelog](CHANGELOG.md).

## Next

- [ ] **Search with no searchable field.** A search where none of the listed fields is
  searchable currently matches every row. It should match none.
- [ ] **Search on number and date columns.** Only text fields are searchable by default.
  Decide what searching a number or a date should mean (exact value? text match?).

### Filtering
- [ ] **Case for in/notin and `ignoreCase: false`.** Both follow the database's default
  today, so MySQL stays case-insensitive.
- [ ] **Accent-insensitive matching** (ά→α, ς→σ, …) for Greek and other accented text.

### Relations
- [ ] **Reject a one-of-many HasOne** (`latestOfMany()`): the plain join repeats grid rows.
- [ ] **Soft-deleted related rows** count as missing.

### Export
- [ ] **EJ2's own "All pages" export.** It asks for every row in one request
  (`take` = total count), so a grid over its page size limit answers 422. Let it
  through, e.g. with an opt-in param added in `actionBegin`.

### Grouping
- [ ] **Aggregates and lazy-load grouping** (`aggregates`, `onDemandGroupInfo`): totals over
  every matching row, not just the page.

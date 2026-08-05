# Release Notes for Astuteo Search Transform

## 6.0.0 - 2026-08-05

> {warning} Any index built by these transformers needs a full reindex. Element query class
> names were written into stored records and will persist until the records are rebuilt.

### Fixed

- Fixed relation, Matrix, and nested-entry field values being indexed as the literal string
  `craft\elements\db\ElementQuery`. `ElementQuery::__toString()` returns its own class name
  instead of throwing, so every one of these leaks was silent.
- Fixed related-entry traversal never running. In `extractStringValue()` the generic
  `__toString` arm preceded the relation arm, so it always won; the relation arm also tested
  for `Entry` rather than an entry *query*, which a relation field never returns.
- Fixed `parseRelatedEntries()` returning nothing for every element. It gated on
  `property_exists($item, 'fieldValues')`, but `fieldValues` is a magic getter on
  `craft\base\Element` and is never a declared property, so every element was skipped.
- Fixed array field values stringifying to `Array` with a PHP warning.

### Changed

- Related content now actually appears in extracted text. Indexed records will be **longer**
  than on 5.x/6.0.0-RC — check them against `chunkText()`/`splitLongText()` (3500 default) and
  Algolia's 10KB per-record limit.
- Relation traversal covers all element types, not just entries. Assets, categories, and users
  contribute any `text`/`heading` fields they define and are otherwise skipped rather than
  crashing or emitting a class name.
- Traversal still stops after one hop, unchanged from before.

# Translatable labels

A field definition carries two kinds of user-visible text, and both are
translatable: the field's own display `label`, and the `label` on each option of
a select-style field.

This page holds what the class docblocks deliberately do not — the key shapes,
the read path, and the reasoning behind the one design decision a reader is
likely to want to overturn.

## Where the label lives

The display label is stored in `options['label']` rather than in a column of its
own, and reached through the `$label` property hook, which falls back to
`ucfirst($name)` when nothing is set. So a definition always has a label to
render, and only an author-supplied one occupies storage.

## Catalogue keys

Two shapes, derived from the definition's identifier:

```
definition.{uuid}.label                       the field's own display label
definition.{uuid}.option.{value}.label        one select option's label
```

The short class name derives the `definition` segment and the module name
derives the domain. **The option `value` is the key**, not its position — which
is why the value has to be stable for the life of the option. Reordering options
must not rewrite their values, or every translation attached to them is orphaned.

## Reading

Label reads go through the platform's label resolver rather than reading
`options` directly. When no override exists for the requested locale, the raw
stored value is served verbatim, so a definition with no translations behaves
exactly as it did before any of this existed. That property is the reason the
seam could be introduced without a migration.

## Why options are not entities

An option is an inline `{value, label}` array inside the definition's `options`
column, and the translation seam reaches it through a *child* declaration on the
`#[Translatable]` attribute:

```php
#[Translatable(fields: ['label'], children: [self::TRANSLATABLE_OPTION_CHILD => ['label']])]
```

The alternative — promoting each option to its own entity so it could carry its
own translations — was rejected. Options are ordered, small, and only ever read
with their parent; a table for them would add a join to every field render and a
lifecycle to every option edit, in exchange for translation machinery the child
seam already provides.

The cost of that choice is the stability requirement on `value` described above.
It is a real constraint and it is the one thing to remember when editing option
handling.

## The token

`Definition::TRANSLATABLE_OPTION_CHILD` is the childKind token naming that seam.
It is declared once and used both by the attribute and by the read path, so the
two cannot disagree about what an option child is called.

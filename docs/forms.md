# Filter forms

`AbstractForm` is an abstract `Laminas\Form\Form` that builds its elements
and input filter from a [FilterSet](filters.md#filterset). `Form` is the
final, ready-to-use concrete class; extend `AbstractForm` for your own form
classes.

## Building a form

```php
use Contenir\Db\QueryFilter\FilterSet;
use Contenir\Db\QueryFilter\Form;

$form = new Form('product-filter');
$form->setFilterSet(new FilterSet([new SearchFilter(), new CategoryFilter()]));
$form->build();
$form->setAttribute('method', 'GET');
```

Or package it as a class:

```php
use Contenir\Db\QueryFilter\AbstractForm;
use Contenir\Db\QueryFilter\FilterSet;

final class ProductFilterForm extends AbstractForm
{
    public function __construct()
    {
        parent::__construct('product-filter');

        $this->setFilterSet(new FilterSet([
            new SearchFilter(),
            new CategoryFilter(),
            new StatusFilter(),
        ]));
        $this->build();

        $this->setAttribute('method', 'GET');
    }
}
```

## API

| Method | Description |
| --- | --- |
| `setFilterSet(FilterSet $filterSet): self` | Sets the filters the form is built from |
| `getFilterSet(): FilterSet` | The filter set. Throws `RuntimeException` if none is set |
| `build(): void` | Adds each filter's element, and records each filter's input specification under its parameter name |
| `getInputFilterSpecification(): array` | The recorded specifications (`InputFilterProviderInterface`) |

`build()` skips filters whose element or specification is `null` or empty,
so hidden and immutable filters add nothing. Call it once, after the filter
set is complete. It throws `RuntimeException` when no filter set is set or a
filter has no parameter name, and lets Laminas Form exceptions through for an
invalid element specification.

Laminas Form creates the input filter from `getInputFilterSpecification()` the
first time it is needed, so add every filter before validating.

## Rendering

The form renders like any Laminas form. With laminas-view:

```php
<?= $this->form()->openTag($form) ?>
<?php foreach ($form as $element): ?>
    <?= $this->formLabel($element) ?>
    <?= $this->formElement($element) ?>
    <?= $this->formElementErrors($element) ?>
<?php endforeach ?>
<button type="submit">Filter</button>
<?= $this->form()->closeTag() ?>
```

After `QueryFilter::setQueryParams()` the elements hold the submitted values,
and validation messages are available for invalid ones.

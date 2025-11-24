<?php

/**
 * @see       https://github.com/contenir/contenir-db-queryfilter for the canonical source repository
 */

declare(strict_types=1);

namespace Contenir\Db\QueryFilter;

use Laminas\Form\Form;
use Laminas\InputFilter\InputFilterProviderInterface;

/**
 * Abstract form class for query filters.
 *
 * Extends Laminas Form to automatically generate form elements and input
 * filter specifications from a FilterSet.
 */
class AbstractForm extends Form implements InputFilterProviderInterface
{
    protected FilterSet $filterSet;

    /** @var array<string, array<string, mixed>> Input filter specifications */
    protected array $spec = [];

    /**
     * Set the filter set.
     *
     * @param FilterSet $filterSet Collection of filter definitions
     */
    public function setFilterSet(FilterSet $filterSet): self
    {
        $this->filterSet = $filterSet;
        return $this;
    }

    /**
     * Get the filter set.
     */
    public function getFilterSet(): FilterSet
    {
        return $this->filterSet;
    }

    /**
     * Build form elements from filter definitions.
     *
     * Iterates through all filters in the FilterSet and adds their
     * form elements and input filter specifications to this form.
     */
    public function build(): void
    {
        foreach ($this->filterSet->getFilters() as $filter) {
            $name    = $filter->getFilterParam();
            $element = $filter->getElement();
            if ($element) {
                $this->add($element);
            }

            $spec = $filter->getInputFilterSpecification();
            if ($spec) {
                $this->spec[$name] = $spec;
            }
        }
    }

    /**
     * Get input filter specification.
     *
     * @return array<string, array<string, mixed>>
     */
    public function getInputFilterSpecification(): array
    {
        return $this->spec;
    }
}

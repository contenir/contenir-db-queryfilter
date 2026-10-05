<?php

/**
 * @see       https://github.com/contenir/contenir-db-queryfilter for the canonical source repository
 */

declare(strict_types=1);

namespace Contenir\Db\QueryFilter;

use Laminas\Form\Exception\ExceptionInterface as FormException;
use Laminas\Form\Form;
use Laminas\InputFilter\InputFilterProviderInterface;
use Override;
use RuntimeException;

/**
 * Abstract form class for query filters.
 *
 * Extends Laminas Form to automatically generate form elements and input
 * filter specifications from a FilterSet.
 *
 * @api
 *
 * @extends Form<array<string, mixed>>
 */
abstract class AbstractForm extends Form implements InputFilterProviderInterface
{
    protected ?FilterSet $filterSet = null;

    /** @var array<string, array<array-key, mixed>> Input filter specifications */
    protected array $spec = [];

    /**
     * Build form elements from filter definitions.
     *
     * Iterates through all filters in the FilterSet and adds their
     * form elements and input filter specifications to this form.
     *
     * @throws RuntimeException If no FilterSet is set, or a filter has no query parameter name.
     * @throws FormException If Laminas Form rejects an element specification.
     */
    public function build(): void
    {
        foreach ($this->getFilterSet()->getFilters() as $filter) {
            $name    = $filter->getFilterParam();
            $element = $filter->getElement();
            if (null !== $element && [] !== $element) {
                $this->add($element);
            }

            $spec = $filter->getInputFilterSpecification();
            if (null !== $name && null !== $spec && [] !== $spec) {
                $this->spec[$name] = $spec;
            }
        }
    }

    /**
     * Get the filter set.
     *
     * @throws RuntimeException If no FilterSet has been set.
     */
    public function getFilterSet(): FilterSet
    {
        if (null === $this->filterSet) {
            throw new RuntimeException('FilterSet must be set before calling this method. Use setFilterSet() first.');
        }

        return $this->filterSet;
    }

    /**
     * Get input filter specification.
     *
     * @return array<string, array<array-key, mixed>>
     *
     * @mago-expect analysis:incompatible-return-type Each entry is an InputSpecification; the filters build them as plain arrays.
     */
    #[Override]
    public function getInputFilterSpecification(): array
    {
        return $this->spec;
    }

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
}

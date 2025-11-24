<?php

/**
 * @see       https://github.com/contenir/contenir-db-queryfilter for the canonical source repository
 */

declare(strict_types=1);

namespace Contenir\Db\QueryFilter\Filter;

use Laminas\Filter;
use Laminas\Form\Element;

/**
 * Abstract filter for text input fields.
 *
 * Generates a text input form element. Subclasses must implement the
 * filter() method to define query modification behavior.
 */
abstract class AbstractFilterText extends AbstractFilter
{
    /**
     * Get text input element specification.
     *
     * @return array<string, mixed>
     */
    public function getElement(): array
    {
        return [
            'type'       => Element\Text::class,
            'name'       => $this->getFilterParam(),
            'options'    => [
                'label' => $this->getFilterLabel(),
            ],
            'attributes' => $this->filterAttributes,
        ];
    }

    /**
     * Get input filter specification for text field.
     *
     * Applies ToNull filter to convert empty strings to null.
     *
     * @return array<string, mixed>
     */
    public function getInputFilterSpecification(): array
    {
        return [
            'required' => false,
            'filters'  => [
                ['name' => Filter\ToNull::class],
            ],
        ];
    }
}

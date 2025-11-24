<?php

/**
 * @see       https://github.com/contenir/contenir-db-queryfilter for the canonical source repository
 * @copyright https://github.com/contenir/contenir-db-queryfilter/blob/master/COPYRIGHT.md
 * @license   https://github.com/contenir/contenir-db-queryfilter/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Contenir\Db\QueryFilter\Filter;

use Laminas\Filter;
use Laminas\Form\Element;

/**
 * Abstract filter for select dropdown fields.
 *
 * Generates a select dropdown form element. Subclasses must implement
 * getValueOptions() to provide dropdown choices and filter() for query behavior.
 */
abstract class AbstractFilterSelect extends AbstractFilter
{
    /**
     * Get select element specification.
     *
     * @return array<string, mixed>
     */
    public function getElement(): array
    {
        return [
            'type'       => Element\Select::class,
            'name'       => $this->getFilterParam(),
            'options'    => [
                'label'         => $this->getFilterLabel(),
                'value_options' => $this->getValueOptions(),
            ],
            'attributes' => $this->filterAttributes,
        ];
    }

    /**
     * Get input filter specification for select field.
     *
     * @return array<string, mixed>
     */
    public function getInputFilterSpecification(): array
    {
        return [
            'required' => $this->getFilterRequired(),
            'filters'  => [
                ['name' => Filter\ToNull::class],
            ],
        ];
    }

    /**
     * Get available options for the select dropdown.
     *
     * @return array<string, string>
     */
    abstract public function getValueOptions();
}

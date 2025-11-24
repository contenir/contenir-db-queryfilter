<?php

/**
 * @see       https://github.com/contenir/contenir-db-queryfilter for the canonical source repository
 */

declare(strict_types=1);

namespace Contenir\Db\QueryFilter\Filter;

use Laminas\Form\Element;

/**
 * Abstract filter for radio button fields.
 *
 * Generates radio button form elements. Extends AbstractFilterSelect
 * to inherit value options handling.
 */
abstract class AbstractFilterRadio extends AbstractFilterSelect
{
    /**
     * Get radio button element specification.
     *
     * @return array<string, mixed>
     */
    public function getElement(): array
    {
        return [
            'type'       => Element\Radio::class,
            'name'       => $this->getFilterParam(),
            'options'    => [
                'label'         => $this->getFilterLabel(),
                'value_options' => $this->getValueOptions(),
            ],
            'attributes' => $this->filterAttributes,
        ];
    }
}

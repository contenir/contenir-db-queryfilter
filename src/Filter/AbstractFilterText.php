<?php

declare(strict_types=1);

namespace Contenir\Db\QueryFilter\Filter;

use Laminas\Filter;
use Laminas\Form\Element;

abstract class AbstractFilterText extends AbstractFilter
{
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

<?php

declare(strict_types=1);

namespace Contenir\Db\QueryFilter\Filter;

use Laminas\Form\Element;
use Laminas\Filter;

abstract class FilterSelect extends FilterAbstract
{
    public function getElement(): array
    {
        return [
            'type'  => Element\Select::class,
            'name' => $this->getFilterParam(),
            'options' => [
                'label' => $this->getFilterLabel(),
                'value_options' => $this->getValueOptions()
            ],
            'attributes' => $this->filterAttributes
        ];
    }

    public function getInputFilterSpecification(): array
    {
        return [
            'required' => $this->getFilterRequired(),
            'filters' => [
                ['name' => Filter\ToNull::class]
            ]
        ];
    }

    abstract public function getValueOptions();
}

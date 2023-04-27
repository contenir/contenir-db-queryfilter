<?php

declare(strict_types=1);

namespace Contenir\Db\QueryFilter\Filter;

use Laminas\Form\Element;
use Laminas\Filter;

abstract class FilterText extends FilterAbstract
{
    public function getElement()
    {
        return [
            'type'  => Element\Text::class,
            'name' => $this->getFilterParam(),
            'options' => [
                'label' => $this->getFilterLabel()
            ],
            'attributes' => $this->filterAttributes
        ];
    }

    public function getInputFilterSpecification()
    {
        return [
            'required' => false,
            'filters' => [
                ['name' => Filter\ToNull::class]
            ]
        ];
    }
}

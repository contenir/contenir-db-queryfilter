<?php

declare(strict_types=1);

namespace Contenir\Db\QueryFilter\Filter;

use Laminas\Form\Element;

abstract class FilterRadio extends FilterSelect
{
    public function getElement()
    {
        return [
            'type'  => Element\Radio::class,
            'name' => $this->getFilterParam(),
            'options' => [
                'label' => $this->getFilterLabel(),
                'value_options' => $this->getValueOptions()
            ],
            'attributes' => $this->filterAttributes
        ];
    }
}

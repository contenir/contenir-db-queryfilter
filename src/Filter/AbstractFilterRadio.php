<?php

declare(strict_types=1);

namespace Contenir\Db\QueryFilter\Filter;

use Laminas\Form\Element;

abstract class AbstractFilterRadio extends AbstractFilterSelect
{
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

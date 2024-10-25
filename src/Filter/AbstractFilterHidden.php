<?php

declare(strict_types=1);

namespace Contenir\Db\QueryFilter\Filter;

abstract class AbstractFilterHidden extends AbstractFilter
{
    public function getElement(): ?array
    {
        return null;
    }

    public function getInputFilterSpecification(): ?array
    {
        return null;
    }
}

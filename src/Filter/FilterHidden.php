<?php

declare(strict_types=1);

namespace Contenir\Db\QueryFilter\Filter;

abstract class FilterHidden extends FilterAbstract
{
    public function getElement()
    {
        return null;
    }

    public function getInputFilterSpecification()
    {
        return null;
    }
}

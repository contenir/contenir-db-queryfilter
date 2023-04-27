<?php

declare(strict_types=1);

namespace Contenir\Db\QueryFilter\Filter;

abstract class FilterImmutable extends FilterHidden
{
    public function getFilterParam()
    {
        return null;
    }
}

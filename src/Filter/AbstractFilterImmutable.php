<?php

declare(strict_types=1);

namespace Contenir\Db\QueryFilter\Filter;

abstract class AbstractFilterImmutable extends AbstractFilterHidden
{
    public function getFilterParam(): ?string
    {
        return null;
    }
}

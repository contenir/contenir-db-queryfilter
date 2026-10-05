<?php

declare(strict_types=1);

namespace ContenirTest\Db\QueryFilter\TestAsset\Filter;

use Contenir\Db\QueryFilter\Filter\AbstractFilterImmutable;
use Override;
use PhpDb\Sql\Select;

/**
 * Immutable filter: always active = 1.
 */
final class ActiveOnlyFilter extends AbstractFilterImmutable
{
    protected string|iterable|null $filterDefault = 'fixed';

    #[Override]
    public function filter(Select $query): void
    {
        $query->where->equalTo('active', 1);
    }
}

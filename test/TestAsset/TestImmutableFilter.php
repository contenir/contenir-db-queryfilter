<?php

/**
 * @see       https://github.com/contenir/contenir-db-queryfilter for the canonical source repository
 */

declare(strict_types=1);

namespace ContenirTest\Db\QueryFilter\TestAsset;

use Contenir\Db\QueryFilter\Filter\AbstractFilterImmutable;
use Laminas\Db\Sql\Select;

/**
 * Immutable filter for testing.
 *
 * Always applies 'active = 1' condition regardless of user input.
 */
class TestImmutableFilter extends AbstractFilterImmutable
{
    /**
     * Apply filter to query.
     */
    public function filter(Select $query): void
    {
        $query->where->equalTo('active', 1);
    }
}

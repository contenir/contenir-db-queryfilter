<?php

/**
 * @see       https://github.com/contenir/contenir-db-queryfilter for the canonical source repository
 */

declare(strict_types=1);

namespace ContenirTest\Db\QueryFilter\TestAsset;

use Contenir\Db\QueryFilter\Filter\AbstractFilter;
use Laminas\Db\Sql\Select;

/**
 * Another stub filter for testing multiple filters.
 */
class AnotherTestFilterStub extends AbstractFilter
{
    protected ?string $filterParam = 'another_param';

    /**
     * Apply filter to query (no-op for testing).
     */
    public function filter(Select $query): void
    {
        // Do nothing - just a stub for testing
    }
}

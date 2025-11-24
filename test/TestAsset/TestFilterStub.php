<?php

/**
 * @see       https://github.com/contenir/contenir-db-queryfilter for the canonical source repository
 */

declare(strict_types=1);

namespace ContenirTest\Db\QueryFilter\TestAsset;

use Contenir\Db\QueryFilter\Filter\AbstractFilter;
use Laminas\Db\Sql\Select;

/**
 * Stub filter for testing with a specific parameter name.
 */
class TestFilterStub extends AbstractFilter
{
    protected ?string $filterParam = 'test_param';

    /**
     * Apply filter to query (no-op for testing).
     */
    public function filter(Select $query): void
    {
        // Do nothing - just a stub for testing
    }
}

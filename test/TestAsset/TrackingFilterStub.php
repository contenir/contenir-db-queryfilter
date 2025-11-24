<?php

/**
 * @see       https://github.com/contenir/contenir-db-queryfilter for the canonical source repository
 */

declare(strict_types=1);

namespace ContenirTest\Db\QueryFilter\TestAsset;

use Contenir\Db\QueryFilter\Filter\AbstractFilter;
use Laminas\Db\Sql\Select;

/**
 * Stub filter that tracks if filter() was called.
 */
class TrackingFilterStub extends AbstractFilter
{
    protected ?string $filterParam = 'tracking_param';

    public bool $filterCalled = false;

    /**
     * Apply filter to query and track that it was called.
     */
    public function filter(Select $query): void
    {
        $this->filterCalled = true;
    }

    /**
     * Set the filter parameter name.
     */
    public function setFilterParamName(string $param): void
    {
        $this->filterParam = $param;
    }
}

<?php

/**
 * @see       https://github.com/contenir/contenir-db-queryfilter for the canonical source repository
 */

declare(strict_types=1);

namespace ContenirTest\Db\QueryFilter\TestAsset;

use Contenir\Db\QueryFilter\Filter\AbstractFilterText;
use Laminas\Db\Sql\Select;

/**
 * Concrete text filter for testing.
 *
 * Provides a simple LIKE filter on the 'name' column.
 */
class TestTextFilter extends AbstractFilterText
{
    protected ?string $filterParam = 'search';

    protected string|iterable|null $filterDefault = '';

    protected ?string $filterLabel = 'Search';

    /**
     * Apply filter to query.
     */
    public function filter(Select $query): void
    {
        $value = $this->getFilterValue();
        if (! empty($value)) {
            $query->where->like('name', '%' . $value . '%');
        }
    }
}

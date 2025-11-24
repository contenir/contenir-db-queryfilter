<?php

/**
 * @see       https://github.com/contenir/contenir-db-queryfilter for the canonical source repository
 */

declare(strict_types=1);

namespace ContenirTest\Db\QueryFilter\TestAsset;

use Contenir\Db\QueryFilter\Filter\AbstractFilterText;
use Laminas\Db\Sql\Select;

/**
 * Concrete category filter for testing.
 *
 * Provides a simple equality filter on the 'category' column.
 */
class TestCategoryFilter extends AbstractFilterText
{
    protected ?string $filterParam = 'category';

    protected string|iterable|null $filterDefault = null;

    protected ?string $filterLabel = 'Category';

    /**
     * Apply filter to query.
     */
    public function filter(Select $query): void
    {
        $value = $this->getFilterValue();
        if ($value !== null) {
            $query->where->equalTo('category', $value);
        }
    }
}

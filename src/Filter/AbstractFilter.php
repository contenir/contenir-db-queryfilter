<?php

/**
 * @see       https://github.com/contenir/contenir-db-queryfilter for the canonical source repository
 * @copyright https://github.com/contenir/contenir-db-queryfilter/blob/master/COPYRIGHT.md
 * @license   https://github.com/contenir/contenir-db-queryfilter/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Contenir\Db\QueryFilter\Filter;

use Contenir\Db\QueryFilter\FilterSet;
use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Sql\Select;
use Laminas\Db\Sql\Sql;
use Laminas\Db\Sql\Where;

/**
 * Abstract base class for all filters.
 *
 * Provides common functionality for filter implementations including
 * database adapter access, FilterSet integration, and SQL helpers.
 */
abstract class AbstractFilter
{
    use FilterTrait;

    /** @var Adapter */
    protected Adapter $adapter;

    /** @var FilterSet */
    protected FilterSet $filterSet;

    /** @var array<string, mixed> */
    protected array $input = [];

    /**
     * Set the database adapter.
     *
     * @param Adapter $adapter Database adapter instance
     * @return self
     */
    final public function setAdapter(Adapter $adapter): self
    {
        $this->adapter = $adapter;
        return $this;
    }

    /**
     * Set the parent FilterSet.
     *
     * @param FilterSet $filterSet Parent filter set
     * @return self
     */
    final public function setFilterSet(FilterSet $filterSet): self
    {
        $this->filterSet = $filterSet;
        return $this;
    }

    /**
     * Apply this filter to a SELECT query.
     *
     * Implementations should modify the query based on the current filter value.
     *
     * @param Select $query SQL SELECT statement to modify
     */
    abstract public function filter(Select $query): void;

    /**
     * Get SQL builder instance.
     *
     * @return Sql
     */
    protected function getSql(): Sql
    {
        return new Sql($this->adapter);
    }

    /**
     * Get or create WHERE clause from SELECT.
     *
     * @param Select $select SQL SELECT statement
     * @return Where WHERE clause instance
     */
    protected function getWhere(Select $select): Where
    {
        $where = $select->where;
        if ($where === null) {
            $where = new Where();
        }

        return $where;
    }

    /**
     * Check if SELECT already has a specific JOIN.
     *
     * Useful to prevent duplicate JOINs when multiple filters need the same table.
     *
     * @param Select $select   SQL SELECT statement
     * @param string $joinName Table name to check for
     * @return bool True if JOIN exists
     */
    protected function hasJoin(Select $select, string $joinName): bool
    {
        $joins = $select->joins->getJoins();

        foreach ($joins as $join) {
            if ($join['name'] === $joinName) {
                return true;
            }
        }

        return false;
    }
}

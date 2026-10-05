<?php

/**
 * @see       https://github.com/contenir/contenir-db-queryfilter for the canonical source repository
 */

declare(strict_types=1);

namespace Contenir\Db\QueryFilter\Filter;

use Contenir\Db\QueryFilter\FilterSet;
use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Sql\Select;
use Laminas\Db\Sql\Sql;
use Laminas\Db\Sql\Where;

use function array_column;
use function in_array;

/**
 * Abstract base class for all filters.
 *
 * Provides common functionality for filter implementations including
 * database adapter access, FilterSet integration, and SQL helpers.
 *
 * @api
 */
abstract class AbstractFilter
{
    use FilterTrait;

    protected Adapter $adapter;

    protected FilterSet $filterSet;

    /** @var array<string, mixed> */
    protected array $input = [];

    /**
     * Apply this filter to a SELECT query.
     *
     * Implementations should modify the query based on the current filter value.
     *
     * @param Select $query SQL SELECT statement to modify
     */
    abstract public function filter(Select $query): void;

    /**
     * Set the database adapter.
     *
     * @param Adapter $adapter Database adapter instance
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
     */
    final public function setFilterSet(FilterSet $filterSet): self
    {
        $this->filterSet = $filterSet;
        return $this;
    }

    /**
     * Get SQL builder instance.
     */
    protected function getSql(): Sql
    {
        return new Sql($this->adapter);
    }

    /**
     * Get the WHERE clause of a SELECT.
     *
     * @param Select $select SQL SELECT statement
     * @return Where WHERE clause instance
     */
    protected function getWhere(Select $select): Where
    {
        return $select->where;
    }

    /**
     * Check if SELECT already has a specific JOIN.
     *
     * Useful to prevent duplicate JOINs when multiple filters need the same table.
     *
     * @param Select $select   SQL SELECT statement
     * @param string $joinName Table name to check for
     * @return bool True if JOIN exists
     *
     * @mago-expect analysis:less-specific-nested-argument-type laminas-db's Join::getJoins() is untyped; each join has a 'name' key.
     */
    protected function hasJoin(Select $select, string $joinName): bool
    {
        return in_array($joinName, array_column($select->joins->getJoins(), 'name'), strict: true);
    }
}

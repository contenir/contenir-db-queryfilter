<?php

/**
 * @see       https://github.com/contenir/contenir-db-queryfilter for the canonical source repository
 */

declare(strict_types=1);

namespace Contenir\Db\QueryFilter;

use Laminas\Db\Adapter\Adapter;
use Laminas\Db\ResultSet\ResultSetInterface;
use Laminas\Db\Sql\Select;

/**
 * Interface for table/repository classes used with QueryFilter.
 *
 * Implement this interface on your repository or table gateway class
 * to enable integration with QueryFilter for filtered, paginated queries.
 */
interface QueryFilterTableInterface
{
    /**
     * Get the database adapter.
     */
    public function getAdapter(): Adapter;

    /**
     * Create a new SELECT query for this table.
     */
    public function select(): Select;

    /**
     * Get the table name.
     */
    public function getTable(): string;

    /**
     * Prepare/modify the SELECT query before execution.
     *
     * Use this to apply default ordering, joins, or other query modifications.
     */
    public function prepareSelect(Select $select): void;

    /**
     * Get the result set prototype for hydrating results.
     */
    public function getResultSet(): ResultSetInterface;
}
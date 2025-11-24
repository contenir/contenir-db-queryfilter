<?php

/**
 * @see       https://github.com/contenir/contenir-db-queryfilter for the canonical source repository
 */

declare(strict_types=1);

namespace ContenirTest\Db\QueryFilter\TestAsset;

use Contenir\Db\QueryFilter\QueryFilterTableInterface;
use Laminas\Db\Adapter\Adapter;
use Laminas\Db\ResultSet\ResultSet;
use Laminas\Db\ResultSet\ResultSetInterface;
use Laminas\Db\Sql\Select;
use RuntimeException;

/**
 * Mock implementation of QueryFilterTableInterface for testing.
 *
 * Provides a minimal implementation that doesn't require a real database connection.
 */
class MockQueryFilterTable implements QueryFilterTableInterface
{
    private string $tableName;

    /**
     * @param string $tableName The table name to use
     */
    public function __construct(string $tableName = 'test_table')
    {
        $this->tableName = $tableName;
    }

    /**
     * Get the database adapter.
     *
     * @throws RuntimeException Always throws as not implemented in mock.
     */
    public function getAdapter(): Adapter
    {
        throw new RuntimeException('getAdapter() not implemented in mock');
    }

    /**
     * Create a new SELECT query for this table.
     */
    public function select(): Select
    {
        return new Select($this->tableName);
    }

    /**
     * Get the table name.
     */
    public function getTable(): string
    {
        return $this->tableName;
    }

    /**
     * Prepare/modify the SELECT query before execution.
     */
    public function prepareSelect(Select $select): void
    {
        // No-op for testing
    }

    /**
     * Get the result set prototype for hydrating results.
     */
    public function getResultSet(): ResultSetInterface
    {
        return new ResultSet();
    }
}

<?php

/**
 * @see       https://github.com/contenir/contenir-db-queryfilter for the canonical source repository
 */

declare(strict_types=1);

namespace Contenir\Db\QueryFilter;

use Contenir\Db\QueryFilter\Paginator\SelectAdapter;
use PhpDb\Adapter\AdapterInterface;
use RuntimeException;

/**
 * Interface for query filter implementations.
 *
 * Defines the contract for coordinating form handling, table interaction,
 * and filter application to build filtered, paginated database queries.
 *
 * @api
 */
interface QueryFilterInterface
{
    /**
     * Get the database adapter used for counting and position queries.
     *
     * @throws RuntimeException If no adapter has been set.
     */
    public function getAdapter(): AdapterInterface;

    /**
     * Get the filter form.
     *
     * @throws RuntimeException If no form has been set.
     */
    public function getForm(): AbstractForm;

    /**
     * Get paginated result set with filters applied.
     *
     * @throws RuntimeException If the form, its FilterSet, the table or the adapter is not set.
     */
    public function getPagingResultSet(): SelectAdapter;

    /**
     * Get previous/next position within filtered results.
     *
     * A contenir-db-model entity's key is read through the property its
     * mapping gives the $primaryKey column; any other object's key is read
     * from the property named like the column.
     *
     * @param object $entity     Current entity
     * @param string $identifier Identifier column, used for URL slugs
     * @param string $primaryKey Primary key column name
     * @param string $title      Title column name
     * @return array<array-key, array<string, mixed>> Array with 'prev' and/or 'next' keys
     *
     * @throws RuntimeException If the form, its FilterSet, the table, the adapter or the table name is not set.
     */
    public function getPosition(
        object $entity,
        string $identifier = 'slug',
        string $primaryKey = 'resource_id',
        string $title = 'title',
    ): array;

    /**
     * Get the query filter table.
     *
     * @throws RuntimeException If no table has been set.
     */
    public function getQueryFilterTable(): QueryFilterTableInterface;

    /**
     * Get the table name.
     *
     * @throws RuntimeException If no table name has been set.
     */
    public function getTableName(): string;

    /**
     * Check if query parameters were submitted.
     */
    public function isSubmitted(): bool;

    /**
     * Check if form has been validated.
     */
    public function isValidated(): bool;

    /**
     * Set the database adapter used for counting and position queries.
     */
    public function setAdapter(AdapterInterface $adapter): self;

    /**
     * Set the filter form.
     */
    public function setForm(AbstractForm $form): self;

    /**
     * Set the query filter table for database operations; also sets the
     * table name from the FROM of its createSelect().
     */
    public function setQueryFilterTable(QueryFilterTableInterface $queryFilterTable): self;

    /**
     * Set query parameters and populate filter values.
     *
     * @param array<string, mixed> $params Query parameters from the request
     *
     * @throws RuntimeException If the form, its FilterSet or the table is not set.
     */
    public function setQueryParams(array $params): void;

    /**
     * Set the table name for queries.
     */
    public function setTableName(string $tableName): self;
}

<?php

/**
 * @see       https://github.com/contenir/contenir-db-queryfilter for the canonical source repository
 */

declare(strict_types=1);

namespace Contenir\Db\QueryFilter;

use Laminas\Paginator\Adapter\LaminasDb\DbSelect;

/**
 * Interface for query filter implementations.
 *
 * Defines the contract for coordinating form handling, table interaction,
 * and filter application to build filtered, paginated database queries.
 */
interface QueryFilterInterface
{
    /**
     * Set the filter form.
     */
    public function setForm(AbstractForm $form): QueryFilterInterface;

    /**
     * Get the filter form.
     */
    public function getForm(): AbstractForm;

    /**
     * Set the query filter table for database operations.
     */
    public function setQueryFilterTable(QueryFilterTableInterface $queryFilterTable): QueryFilterInterface;

    /**
     * Get the query filter table.
     */
    public function getQueryFilterTable(): QueryFilterTableInterface;

    /**
     * Set the table name for queries.
     */
    public function setTableName(string $tableName): QueryFilterInterface;

    /**
     * Get the table name.
     */
    public function getTableName(): string;

    /**
     * Set query parameters and populate filter values.
     *
     * @param array<string, mixed> $params Query parameters from the request
     */
    public function setQueryParams(array $params): void;

    /**
     * Get paginated result set with filters applied.
     */
    public function getPagingResultSet(): DbSelect;

    /**
     * Get previous/next position within filtered results.
     *
     * @param object           $entity     Current entity
     * @param string           $identifier Field used for URL slugs
     * @param string|iterable  $primaryKey Primary key field name
     * @param string           $title      Title field name
     * @return array<string, array<string, mixed>> Array with 'prev' and 'next' keys
     */
    public function getPosition(
        object $entity,
        string $identifier = 'slug',
        string|iterable $primaryKey = 'resource_id',
        string $title = 'title'
    ): array;

    /**
     * Check if form has been validated.
     */
    public function isValidated(): bool;

    /**
     * Check if query parameters were submitted.
     */
    public function isSubmitted(): bool;
}
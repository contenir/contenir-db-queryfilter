<?php

/**
 * @see       https://github.com/contenir/contenir-db-queryfilter for the canonical source repository
 */

declare(strict_types=1);

namespace Contenir\Db\QueryFilter;

use ArrayObject;
use Laminas\Db\Adapter\Adapter;
use Laminas\Db\ResultSet\ResultSet;
use Laminas\Db\Sql;
use Laminas\Paginator\Adapter\LaminasDb\DbSelect;
use RuntimeException;

use function count;

/**
 * Abstract base class for query filters.
 *
 * Coordinates form handling, table interaction, and filter application
 * to build filtered, paginated database queries from HTTP request parameters.
 */
abstract class AbstractQueryFilter implements QueryFilterInterface
{
    protected AbstractForm $form;

    protected QueryFilterTableInterface $queryFilterTable;

    protected string $tableName;

    /** @var bool Whether the form has been validated */
    protected bool $validated = false;

    /** @var bool Whether query parameters were submitted */
    protected bool $submitted = false;

    /**
     * @param AbstractForm|null $form Optional form instance
     */
    public function __construct(
        ?AbstractForm $form = null
    ) {
        if ($form !== null) {
            $this->setForm($form);
        }
    }

    /**
     * Set query parameters and populate filter values.
     *
     * Extracts query parameters matching filter names, validates the form,
     * and stores validated input in the FilterSet.
     *
     * Works with both Mezzio (PSR-7) and Laminas MVC:
     * - Mezzio: $queryFilter->setQueryParams($request->getQueryParams())
     * - MVC: $queryFilter->setQueryParams($this->getRequest()->getQuery()->toArray())
     *
     * @param array<string, mixed> $params Query parameters from the request
     */
    public function setQueryParams(array $params): void
    {
        $this->validateState();

        $data = [];

        foreach ($this->getForm()->getFilterSet()->getFilters() as $filter) {
            $name = $filter->getFilterParam();
            if ($name === null) {
                continue; // Skip immutable filters (no param name)
            }
            $default     = $filter->getFilterDefault();
            $data[$name] = $params[$name] ?? $default;
        }

        $this->form->bind(new ArrayObject($data));
        $this->form->isValid();

        $this->validated = true;
        $this->submitted = count($params) > 0;

        $filteredData = $this->form->getData();
        $this->getForm()->getFilterSet()->setInput(
            is_array($filteredData) ? $filteredData : $filteredData->getArrayCopy()
        );
    }

    /**
     * Set the filter form.
     *
     * @param AbstractForm $form Form instance with FilterSet attached
     */
    public function setForm(AbstractForm $form): QueryFilterInterface
    {
        $this->form = $form;

        return $this;
    }

    /**
     * Get the filter form.
     */
    public function getForm(): AbstractForm
    {
        return $this->form;
    }

    /**
     * Set the query filter table for database operations.
     *
     * @param QueryFilterTableInterface $queryFilterTable Table instance
     */
    public function setQueryFilterTable(QueryFilterTableInterface $queryFilterTable): QueryFilterInterface
    {
        $this->queryFilterTable = $queryFilterTable;
        $this->setTableName($queryFilterTable->getTable());

        return $this;
    }

    /**
     * Get the query filter table.
     */
    public function getQueryFilterTable(): QueryFilterTableInterface
    {
        return $this->queryFilterTable;
    }

    /**
     * Set the table name for queries.
     *
     * @param string $tableName Database table name
     */
    public function setTableName(string $tableName): QueryFilterInterface
    {
        $this->tableName = $tableName;

        return $this;
    }

    /**
     * Get the table name.
     */
    public function getTableName(): string
    {
        return $this->tableName;
    }

    /**
     * Get paginated result set with filters applied.
     *
     * Returns a DbSelect adapter suitable for use with Laminas Paginator.
     *
     * @return DbSelect Paginator adapter for filtered results
     */
    public function getPagingResultSet(): DbSelect
    {
        $this->validateState();

        $adapter = $this->queryFilterTable->getAdapter();
        $select  = $this->queryFilterTable->select();

        $this->onBeforeFilter($select);
        $this->form->getFilterSet()->applyFilters($select);
        $this->onAfterFilter($select);

        $this->queryFilterTable->prepareSelect($select);

        $countSelect = new Sql\Select();
        $countSelect
            ->from(['total_count' => $select])
            ->columns(['C' => new Sql\Expression('COUNT(*)')]);

        return new DbSelect(
            $select,
            $adapter,
            $this->queryFilterTable->getResultSet(),
            $countSelect
        );
    }

    /**
     * Get previous/next position within filtered results.
     *
     * Returns navigation data for the given entity within the current
     * filtered result set, useful for prev/next navigation.
     *
     * @param object           $entity     Current entity (must have $primaryKey as accessible property)
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
    ): array {
        $this->validateState();

        $adapter  = $this->queryFilterTable->getAdapter();
        $platform = $adapter->getPlatform();
        $sql      = new Sql\Sql($adapter);

        $qfBasePk         = $platform->quoteIdentifierInFragment("{$this->getTableName()}.$primaryKey");
        $qfBaseIdentifier = $platform->quoteIdentifierInFragment("{$this->getTableName()}.$identifier");
        $qfBaseTitle      = $platform->quoteIdentifierInFragment("{$this->getTableName()}.$title");

        $basequery = $sql->select();
        $basequery
            ->from($this->getQueryFilterTable()->getTable())
            ->columns([
                'qf_base_pk'       => new Sql\Expression($qfBasePk),
                'qfBaseIdentifier' => new Sql\Expression($qfBaseIdentifier),
                'qfBaseTitle'      => new Sql\Expression($qfBaseTitle),
            ]);

        $this->onBeforeFilter($basequery);
        $this->form->getFilterSet()->applyFilters($basequery);
        $this->onAfterFilter($basequery);

        $this->queryFilterTable->prepareSelect($basequery);

        $subquery = $sql->select();
        $subquery
            ->from(['base' => $basequery])
            ->columns([
                'position'         => new Sql\Expression('@num := @num + 1'),
                'qf_base_pk'       => 'qf_base_pk',
                'qfBaseIdentifier' => 'qfBaseIdentifier',
                'qfBaseTitle'      => 'qfBaseTitle',
            ])
            ->group('qf_base_pk');

        $select = $sql->select()
                      ->from(['current' => $subquery])
                      ->columns(['position', 'qf_base_pk'])
                      ->order(['position' => 'ASC']);

        $adapter->query('SET @num := 0', Adapter::QUERY_MODE_EXECUTE);
        $statement = $sql->prepareStatementForSqlObject($select);

        $positionResult = $statement->execute();
        $current        = 0;

        foreach ($positionResult as $row) {
            if ($row['qf_base_pk'] === $entity->{$primaryKey}) {
                $current = $row['position'];
            }
        }

        $select = $sql->select()
                      ->from(['current' => $subquery])
                      ->columns([
                          'pos'        => new Sql\Expression("IF (position < $current, 'prev', 'next')"),
                          'qf_base_pk' => 'qf_base_pk',
                          $identifier  => 'qfBaseIdentifier',
                          $title       => 'qfBaseTitle',
                      ])
                      ->where('POSITION IN (' . ($current - 1) . ',' . ($current + 1) . ')')
                      ->order(['position' => 'ASC']);

        $adapter->query('SET @num := 0', Adapter::QUERY_MODE_EXECUTE);
        $statement = $sql->prepareStatementForSqlObject($select);
        $results   = new ResultSet();
        $results->initialize($statement->execute());

        $position = [];

        foreach ($results as $row) {
            $position[$row['pos']] = [
                $primaryKey => $row['qf_base_pk'],
                $identifier => $row[$identifier],
                $title      => $row[$title],
            ];
        }

        return $position;
    }

    /**
     * Check if form has been validated.
     */
    public function isValidated(): bool
    {
        return $this->validated;
    }

    /**
     * Check if query parameters were submitted.
     */
    public function isSubmitted(): bool
    {
        return $this->submitted;
    }

    /**
     * Validate that required dependencies are set.
     *
     * @throws RuntimeException If form or queryFilterTable is not set.
     */
    private function validateState(): void
    {
        if (! isset($this->form)) {
            throw new RuntimeException(
                'Form must be set before calling this method. Use setForm() first.'
            );
        }
        if (! isset($this->queryFilterTable)) {
            throw new RuntimeException(
                'QueryFilterTable must be set before calling this method. Use setQueryFilterTable() first.'
            );
        }
    }

    /**
     * Hook called before filters are applied.
     *
     * Override in subclasses to add global query modifications
     * such as tenant isolation, soft delete filters, etc.
     */
    protected function onBeforeFilter(Sql\Select $select): void
    {
        // Default: no-op
    }

    /**
     * Hook called after filters are applied.
     *
     * Override in subclasses to add final query modifications.
     */
    protected function onAfterFilter(Sql\Select $select): void
    {
        // Default: no-op
    }
}

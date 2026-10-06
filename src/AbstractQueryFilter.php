<?php

/**
 * @see       https://github.com/contenir/contenir-db-queryfilter for the canonical source repository
 */

declare(strict_types=1);

namespace Contenir\Db\QueryFilter;

use Contenir\Db\Model\Exception\MappingException;
use Contenir\Db\QueryFilter\Paginator\SelectAdapter;
use Override;
use PhpDb\Adapter\AdapterInterface;
use PhpDb\Sql;
use RuntimeException;

use function is_scalar;

/**
 * Abstract base class for query filters.
 *
 * Coordinates form handling, table interaction, and filter application
 * to build filtered, paginated database queries from HTTP request parameters.
 *
 * @api
 */
abstract class AbstractQueryFilter implements QueryFilterInterface
{
    protected ?AbstractForm $form = null;

    protected ?AdapterInterface $adapter = null;

    protected ?QueryFilterTableInterface $queryFilterTable = null;

    protected ?string $tableName = null;

    /** @var bool Whether the form has been validated */
    protected bool $validated = false;

    /** @var bool Whether query parameters were submitted */
    protected bool $submitted = false;

    /**
     * @param AbstractForm|null $form Optional form instance
     */
    public function __construct(?AbstractForm $form = null)
    {
        if (null !== $form) {
            $this->setForm($form);
        }
    }

    /**
     * Compare a primary key read from the database with the entity's.
     *
     * Drivers commonly return integer columns as strings, so scalar keys
     * compare by their string form.
     */
    private static function isSameKey(mixed $rowKey, mixed $entityKey): bool
    {
        return is_scalar($rowKey) && is_scalar($entityKey) && (string) $rowKey === (string) $entityKey;
    }

    /**
     * Get the database adapter used for counting and position queries.
     *
     * @throws RuntimeException If no adapter has been set.
     */
    #[Override]
    public function getAdapter(): AdapterInterface
    {
        if (null === $this->adapter) {
            throw new RuntimeException('Adapter must be set before calling this method. Use setAdapter() first.');
        }

        return $this->adapter;
    }

    /**
     * Get the filter form.
     *
     * @throws RuntimeException If no form has been set.
     */
    #[Override]
    public function getForm(): AbstractForm
    {
        if (null === $this->form) {
            throw new RuntimeException('Form must be set before calling this method. Use setForm() first.');
        }

        return $this->form;
    }

    /**
     * Get paginated result set with filters applied.
     *
     * Returns a Laminas Paginator adapter over the table's base select with
     * the hooks and filters applied; pages are fetched through the table.
     *
     * @throws RuntimeException If the form, its FilterSet, the table or the adapter is not set.
     */
    #[Override]
    public function getPagingResultSet(): SelectAdapter
    {
        $form    = $this->getForm();
        $table   = $this->getQueryFilterTable();
        $adapter = $this->getAdapter();

        $select = $table->createSelect();

        $this->onBeforeFilter($select);
        $form->getFilterSet()->applyFilters($select);
        $this->onAfterFilter($select);

        return new SelectAdapter($table, $select, $adapter);
    }

    /**
     * Get previous/next position within filtered results.
     *
     * Returns navigation data for the given entity within the current
     * filtered result set, useful for prev/next navigation. The queries use
     * MySQL user variables and IF(), so this requires a MySQL-compatible
     * database.
     *
     * The entity's key is read through its contenir-db-model mapping when it
     * has one, so a `resource_id` column reads the `$resourceId` property.
     * Any other object, such as `(object) $row`, is read by the property
     * named like the column.
     *
     * @param object $entity     Current entity
     * @param string $identifier Identifier column, used for URL slugs
     * @param string $primaryKey Primary key column name
     * @param string $title      Title column name
     * @return array<array-key, array<string, mixed>> Array with 'prev' and/or 'next' keys
     *
     * @throws RuntimeException If the form, its FilterSet, the table, the adapter or the table name is not set.
     * @throws MappingException If the entity carries #[Table] but is not a valid contenir-db-model entity.
     *
     * @mago-expect analysis:mixed-array-access(4) Driver result rows are untyped arrays.
     */
    #[Override]
    public function getPosition(
        object $entity,
        string $identifier = 'slug',
        string $primaryKey = 'resource_id',
        string $title = 'title',
    ): array {
        $form  = $this->getForm();
        $table = $this->getQueryFilterTable();

        $adapter   = $this->getAdapter();
        $platform  = $adapter->getPlatform();
        $sql       = new Sql\Sql($adapter);
        $tableName = $this->getTableName();

        $qfBasePk         = $platform->quoteIdentifierInFragment("{$tableName}.{$primaryKey}");
        $qfBaseIdentifier = $platform->quoteIdentifierInFragment("{$tableName}.{$identifier}");
        $qfBaseTitle      = $platform->quoteIdentifierInFragment("{$tableName}.{$title}");

        $basequery = $table->createSelect();
        $basequery->columns([
            'qf_base_pk'       => new Sql\Expression($qfBasePk),
            'qfBaseIdentifier' => new Sql\Expression($qfBaseIdentifier),
            'qfBaseTitle'      => new Sql\Expression($qfBaseTitle),
        ]);

        $this->onBeforeFilter($basequery);
        $form->getFilterSet()->applyFilters($basequery);
        $this->onAfterFilter($basequery);

        $subquery = $sql->select();
        $subquery->from(['base' => $basequery])
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

        $current  = $this->findCurrentPosition($sql, $adapter, $select, EntityKey::read($entity, $primaryKey));
        $previous = $current - 1;
        $next     = $current + 1;

        $select = $sql->select()
            ->from(['current' => $subquery])
            ->columns([
                'pos'        => new Sql\Expression("IF (position < {$current}, 'prev', 'next')"),
                'qf_base_pk' => 'qf_base_pk',
                $identifier  => 'qfBaseIdentifier',
                $title       => 'qfBaseTitle',
            ])
            ->where("POSITION IN ({$previous},{$next})")
            ->order(['position' => 'ASC']);

        $adapter->query('SET @num := 0', AdapterInterface::QUERY_MODE_EXECUTE);

        $position = [];

        foreach ($sql->prepareStatementForSqlObject($select)->execute() ?? [] as $row) {
            $position[$row['pos']] = [
                $primaryKey => $row['qf_base_pk'],
                $identifier => $row[$identifier],
                $title      => $row[$title],
            ];
        }

        return $position;
    }

    /**
     * Get the query filter table.
     *
     * @throws RuntimeException If no table has been set.
     */
    #[Override]
    public function getQueryFilterTable(): QueryFilterTableInterface
    {
        if (null === $this->queryFilterTable) {
            throw new RuntimeException(
                'QueryFilterTable must be set before calling this method. Use setQueryFilterTable() first.',
            );
        }

        return $this->queryFilterTable;
    }

    /**
     * Get the table name.
     *
     * @throws RuntimeException If no table name has been set.
     */
    #[Override]
    public function getTableName(): string
    {
        if (null === $this->tableName) {
            throw new RuntimeException(
                'Table name must be set before calling this method. Use setQueryFilterTable() or setTableName() first.',
            );
        }

        return $this->tableName;
    }

    /**
     * Check if query parameters were submitted.
     */
    #[Override]
    public function isSubmitted(): bool
    {
        return $this->submitted;
    }

    /**
     * Check if form has been validated.
     */
    #[Override]
    public function isValidated(): bool
    {
        return $this->validated;
    }

    /**
     * Set the database adapter used for counting and position queries.
     */
    #[Override]
    public function setAdapter(AdapterInterface $adapter): QueryFilterInterface
    {
        $this->adapter = $adapter;

        return $this;
    }

    /**
     * Set the filter form.
     *
     * @param AbstractForm $form Form instance with FilterSet attached
     */
    #[Override]
    public function setForm(AbstractForm $form): QueryFilterInterface
    {
        $this->form = $form;

        return $this;
    }

    /**
     * Set the query filter table for database operations.
     *
     * Also sets the table name from the FROM of the table's createSelect():
     * the table, or its alias (null when the select has no table).
     *
     * @param QueryFilterTableInterface $queryFilterTable Table instance
     */
    #[Override]
    public function setQueryFilterTable(QueryFilterTableInterface $queryFilterTable): QueryFilterInterface
    {
        $this->queryFilterTable = $queryFilterTable;

        $this->tableName = SelectTable::nameOf($queryFilterTable->createSelect());

        return $this;
    }

    /**
     * Set query parameters and populate filter values.
     *
     * Extracts query parameters matching filter names, validates the form,
     * and stores the input in the FilterSet. Parameters missing from the
     * request fall back to each filter's default. Values with an input filter
     * specification are stored after the form's input filters run, whether or
     * not validation passed; values without one (hidden filters) are stored
     * as given.
     *
     * Works with both Mezzio (PSR-7) and Laminas MVC:
     * - Mezzio: $queryFilter->setQueryParams($request->getQueryParams())
     * - MVC: $queryFilter->setQueryParams($this->getRequest()->getQuery()->toArray())
     *
     * @param array<string, mixed> $params Query parameters from the request
     *
     * @throws RuntimeException If the form, its FilterSet or the table is not set.
     *
     */
    #[Override]
    public function setQueryParams(array $params): void
    {
        $form = $this->getForm();
        $this->getQueryFilterTable();

        $filterSet = $form->getFilterSet();
        $data      = [];

        foreach ($filterSet->getFilters() as $filter) {
            $name = $filter->getFilterParam();
            if (null === $name) {
                continue;
            }

            $data[$name] = $params[$name] ?? $filter->getFilterDefault();
        }

        $form->setData($data);
        $form->isValid();

        $this->validated = true;
        $this->submitted = [] !== $params;

        $filterSet->setInput([...$data, ...$form->getInputFilter()->getValues()]);
    }

    /**
     * Set the table name for queries.
     *
     * @param string $tableName Database table name
     */
    #[Override]
    public function setTableName(string $tableName): QueryFilterInterface
    {
        $this->tableName = $tableName;

        return $this;
    }

    /**
     * Hook called after filters are applied.
     *
     * Override in subclasses to add final query modifications.
     *
     * @mago-expect analysis:unused-parameter Extension hook; subclasses use the select.
     */
    protected function onAfterFilter(Sql\Select $select): void {}

    /**
     * Hook called before filters are applied.
     *
     * Override in subclasses to add global query modifications
     * such as tenant isolation, soft delete filters, etc.
     *
     * @mago-expect analysis:unused-parameter Extension hook; subclasses use the select.
     */
    protected function onBeforeFilter(Sql\Select $select): void {}

    /**
     * Find the 1-based position of the entity's key in the numbered result,
     * or 0 when it is not in the filtered set.
     *
     * @mago-expect analysis:mixed-array-access(2) Driver result rows are untyped arrays.
     */
    private function findCurrentPosition(
        Sql\Sql $sql,
        AdapterInterface $adapter,
        Sql\Select $select,
        mixed $entityKey,
    ): int {
        $adapter->query('SET @num := 0', AdapterInterface::QUERY_MODE_EXECUTE);

        $current = 0;
        foreach ($sql->prepareStatementForSqlObject($select)->execute() ?? [] as $row) {
            if (! self::isSameKey($row['qf_base_pk'], $entityKey)) {
                continue;
            }

            $current = (int) $row['position'];
        }

        return $current;
    }
}

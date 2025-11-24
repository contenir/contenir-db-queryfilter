<?php

/**
 * @see       https://github.com/contenir/contenir-db-queryfilter for the canonical source repository
 * @copyright https://github.com/contenir/contenir-db-queryfilter/blob/master/COPYRIGHT.md
 * @license   https://github.com/contenir/contenir-db-queryfilter/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Contenir\Db\QueryFilter;

use ArrayObject;
use Contenir\Db\Model\Entity\AbstractEntity;
use Contenir\Db\Model\Repository\AbstractRepository;
use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Resultset\Resultset;
use Laminas\Db\Sql;
use Laminas\Http\Request;
use Laminas\Paginator\Adapter\LaminasDb\DbSelect;

use function count;

/**
 * Abstract base class for query filters.
 *
 * Coordinates form handling, repository interaction, and filter application
 * to build filtered, paginated database queries from HTTP request parameters.
 */
abstract class AbstractQueryFilter
{
    /** @var AbstractForm */
    protected AbstractForm $form;

    /** @var AbstractRepository */
    protected AbstractRepository $repository;

    /** @var string */
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
     * Process HTTP request and populate filter values.
     *
     * Extracts query parameters matching filter names, validates the form,
     * and stores validated input in the FilterSet.
     *
     * @param Request $request HTTP request containing query parameters
     */
    public function setRequest(Request $request): void
    {
        $data = new ArrayObject();

        foreach ($this->getForm()->getFilterSet()->getFilters() as $filter) {
            $name        = $filter->getFilterParam();
            $default     = $filter->getFilterDefault();
            $data[$name] = $request->getQuery($name, $default);
        }

        $this->form->bind($data);
        $this->form->isValid();

        $this->validated = true;
        $this->submitted = (bool) count($request->getQuery());

        $data = $this->form->getData();

        $this->getForm()->getFilterSet()->setInput($data->getArrayCopy());
    }

    /**
     * Set the filter form.
     *
     * @param AbstractForm $form Form instance with FilterSet attached
     * @return AbstractQueryFilter
     */
    public function setForm(AbstractForm $form): AbstractQueryFilter
    {
        $this->form = $form;

        return $this;
    }

    /**
     * Get the filter form.
     *
     * @return AbstractForm
     */
    public function getForm(): AbstractForm
    {
        return $this->form;
    }

    /**
     * Set the repository for database operations.
     *
     * @param AbstractRepository $repository Repository instance
     * @return self
     */
    public function setRepository(AbstractRepository $repository): self
    {
        $this->repository = $repository;
        $this->setTableName($repository->getTable());

        return $this;
    }

    /**
     * Get the repository.
     *
     * @return AbstractRepository
     */
    public function getRepository(): AbstractRepository
    {
        return $this->repository;
    }

    /**
     * Set the table name for queries.
     *
     * @param string $tableName Database table name
     */
    public function setTableName(string $tableName): void
    {
        $this->tableName = $tableName;
    }

    /**
     * Get the table name.
     *
     * @return string
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
        $adapter = $this->repository->getAdapter();
        $select  = $this->repository->select();

        $this->form->getFilterSet()->filter($select);

        $this->repository->prepareSelect($select);

        $countSelect = new Sql\Select();
        $countSelect
            ->from(['total_count' => $select])
            ->columns(['C' => new Sql\Expression('COUNT(*)')]);

        return new DbSelect(
            $select,
            $adapter,
            $this->repository->getResultSet(),
            $countSelect
        );
    }

    /**
     * Get previous/next position within filtered results.
     *
     * Returns navigation data for the given entity within the current
     * filtered result set, useful for prev/next navigation.
     *
     * @param AbstractEntity   $entity     Current entity
     * @param string           $identifier Field used for URL slugs
     * @param string|iterable  $primaryKey Primary key field name
     * @param string           $title      Title field name
     * @return array<string, array<string, mixed>> Array with 'prev' and 'next' keys
     */
    public function getPosition(
        AbstractEntity $entity,
        string $identifier = 'slug',
        string|iterable $primaryKey = 'resource_id',
        string $title = 'title'
    ): array {
        $adapter  = $this->repository->getAdapter();
        $platform = $adapter->getPlatform();
        $sql      = new Sql\Sql($adapter);

        $qfBasePk         = $platform->quoteIdentifierInFragment("{$this->getTableName()}.$primaryKey");
        $qfBaseIdentifier = $platform->quoteIdentifierInFragment("{$this->getTableName()}.$identifier");
        $qfBaseTitle      = $platform->quoteIdentifierInFragment("{$this->getTableName()}.$title");

        $basequery = $sql->select();
        $basequery
            ->from($this->getRepository()->getTable())
            ->columns([
                'qf_base_pk'       => new Sql\Expression($qfBasePk),
                'qfBaseIdentifier' => new Sql\Expression($qfBaseIdentifier),
                'qfBaseTitle'      => new Sql\Expression($qfBaseTitle),
            ]);

        $this->form->getFilterSet()->filter($basequery);

        $this->repository->prepareSelect($basequery);

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
        $results   = new Resultset();
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
     *
     * @return bool
     */
    public function isValidated(): bool
    {
        return $this->validated;
    }

    /**
     * Check if query parameters were submitted.
     *
     * @return bool
     */
    public function isSubmitted(): bool
    {
        return $this->submitted;
    }
}

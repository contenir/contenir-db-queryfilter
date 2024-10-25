<?php

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

abstract class AbstractQueryFilter
{
    protected AbstractForm $form;
    protected AbstractRepository $repository;
    protected string $tableName;
    protected bool $validated = false;
    protected bool $submitted = false;

    public function __construct(
        ?AbstractForm $form = null
    ) {
        if ($form !== null) {
            $this->setForm($form);
        }
    }

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

    public function setForm(AbstractForm $form): AbstractQueryFilter
    {
        $this->form = $form;

        return $this;
    }

    public function getForm(): AbstractForm
    {
        return $this->form;
    }

    public function setRepository(AbstractRepository $repository): self
    {
        $this->repository = $repository;
        $this->setTableName($repository->getTable());

        return $this;
    }

    public function getRepository(): AbstractRepository
    {
        return $this->repository;
    }

    public function setTableName(string $tableName): void
    {
        $this->tableName = $tableName;
    }

    public function getTableName(): string
    {
        return $this->tableName;
    }

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

    public function isValidated(): bool
    {
        return $this->validated;
    }

    public function isSubmitted(): bool
    {
        return $this->submitted;
    }
}

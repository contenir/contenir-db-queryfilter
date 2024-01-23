<?php

declare(strict_types=1);

namespace Contenir\Db\QueryFilter;

use Contenir\Db\Model\Entity\AbstractEntity;
use Contenir\Db\Model\Repository\AbstractRepository;
use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Resultset\Resultset;
use Laminas\Db\Sql;
use Laminas\Paginator\Adapter\LaminasDb\DbSelect;
use Laminas\Http\Request;
use ArrayObject;

abstract class AbstractQueryFilter
{
    protected AbstractForm $form;
    protected AbstractRepository $repository;
    protected $tableName;
    protected $validated = false;
    protected $submitted = false;

    public function __construct(
        AbstractForm $form = null
    ) {
        if ($form !== null) {
            $this->setForm($form);
        }
    }

    public function setRequest(Request $request)
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

    public function setForm(AbstractForm $form)
    {
        $this->form = $form;
        return $this;
    }

    public function getForm()
    {
        return $this->form;
    }

    public function setRepository(AbstractRepository $repository)
    {
        $this->repository = $repository;
        $this->setTableName($repository->getTable());
        return $this;
    }

    public function getRepository(): AbstractRepository
    {
        return $this->repository;
    }

    public function setTableName($tableName): void
    {
        $this->tableName = $tableName;
    }

    public function getTableName(): string
    {
        return $this->tableName;
    }

    public function getPagingResultSet()
    {
        $adapter = $this->repository->getAdapter();
        $select  = $this->repository->select();

        $this->form->getFilterSet()->filter($select);

        $this->repository->prepareSelect($select);

        /*
         * Debug Result Set Select
         * echo (new Sql\Sql($adapter))->buildSqlString($select);
         * exit;
         */

        $countSelect = new Sql\Select();
        $countSelect
            ->from(['total_count' => $select])
            ->columns(['C' => new Sql\Expression('COUNT(*)')]);

        $collectionAdapter = new DbSelect(
            $select,
            $adapter,
            $this->repository->getResultSet(),
            $countSelect
        );

        return $collectionAdapter;
    }

    public function getPosition(
        AbstractEntity $entity,
        $identifier = 'slug',
        $primaryKey = 'resource_id',
        $title = 'title'
    ) {
        $adapter  = $this->repository->getAdapter();
        $platform = $adapter->getPlatform();
        $sql      = new Sql\Sql($adapter);

        $basequery = $sql->select();
        $basequery
            ->from($this->getRepository()->getTable())
            ->columns([
                'qf_base_pk'         => new Sql\Expression($platform->quoteIdentifierInFragment("{$this->getTableName()}.{$primaryKey}")),
                'qf_base_identifier' => new Sql\Expression($platform->quoteIdentifierInFragment("{$this->getTableName()}.{$identifier}")),
                'qf_base_title'      => new Sql\Expression($platform->quoteIdentifierInFragment("{$this->getTableName()}.{$title}"))
            ]);

        $this->form->getFilterSet()->filter($basequery);

        $this->repository->prepareSelect($basequery);

        $subquery = $sql->select();
        $subquery
            ->from(['base' => $basequery])
            ->columns([
                'position'           => new Sql\Expression('@num := @num + 1'),
                'qf_base_pk'         => 'qf_base_pk',
                'qf_base_identifier' => 'qf_base_identifier',
                'qf_base_title'      => 'qf_base_title'
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
            if ($row['qf_base_pk'] == $entity->{$primaryKey}) {
                $current = $row['position'];
            }
        }

        $select = $sql->select()
            ->from(['current' => $subquery])
            ->columns([
                'pos'        => new Sql\Expression("IF (position < {$current}, 'prev', 'next')"),
                'qf_base_pk' => 'qf_base_pk',
                $identifier  => 'qf_base_identifier',
                $title       => 'qf_base_title'
            ])
            ->where('POSITION IN (' . ($current - 1) . ',' . ($current + 1) . ')')
            ->order(['position' => 'ASC']);

        $adapter->query('SET @num := 0', Adapter::QUERY_MODE_EXECUTE);
        $statement = $sql->prepareStatementForSqlObject($select);
        $results   = new ResultSet();
        $results->initialize($statement->execute());

        $position = [
        ];

        foreach ($results as $row) {
            $position[$row['pos']] = [
                $primaryKey => $row['qf_base_pk'],
                $identifier => $row[$identifier],
                $title      => $row[$title]
            ];
        }

        return $position;
    }

    public function isValidated()
    {
        return $this->validated;
    }

    public function isSubmitted()
    {
        return $this->submitted;
    }
}

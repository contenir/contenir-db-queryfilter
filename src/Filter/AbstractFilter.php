<?php

declare(strict_types=1);

namespace Contenir\Db\QueryFilter\Filter;

use Contenir\Db\QueryFilter\FilterSet;
use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Sql\Select;
use Laminas\Db\Sql\Sql;
use Laminas\Db\Sql\Where;

abstract class AbstractFilter
{
    use FilterTrait;

    protected Adapter $adapter;
    protected FilterSet $filterSet;
    protected array $input = [];

    /**
     * setInput
     */
    final public function setAdapter(Adapter $adapter): self
    {
        $this->adapter = $adapter;
        return $this;
    }

    /**
     * setInput
     */
    final public function setFilterSet(FilterSet $filterSet): self
    {
        $this->filterSet = $filterSet;
        return $this;
    }

    /**
     * filter
     *
     * @param  mixed $query
     */
    abstract public function filter(Select $query): void;

    protected function getSql(): Sql
    {
        return new Sql($this->adapter);
    }

    protected function getWhere(Select $select): Where
    {
        $where = $select->where;
        if ($where === null) {
            $where = new Where();
        }

        return $where;
    }

    protected function hasJoin(Select $select, string $joinName): bool
    {
        $joins = $select->joins->getJoins();

        foreach ($joins as $join) {
            if ($join['name'] === $joinName) {
                return true;
            }
        }

        return false;
    }
}

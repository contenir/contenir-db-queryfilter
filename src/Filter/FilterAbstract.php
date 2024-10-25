<?php

declare(strict_types=1);

namespace Contenir\Db\QueryFilter\Filter;

use Contenir\Db\QueryFilter\FilterSet;
use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Sql\Select;
use Laminas\Db\Sql\Sql;
use Laminas\Db\Sql\Where;

abstract class FilterAbstract
{
    use FilterTrait;

    protected Adapter $adapter;
    protected FilterSet $filterSet;
    protected array $input = [];

    /**
     * setInput
     *
     * @param Adapter $adapter
     *
     * @return FilterAbstract
     */
    final public function setAdapter(Adapter $adapter): FilterAbstract
    {
        $this->adapter = $adapter;
        return $this;
    }

    /**
     * setInput
     *
     * @param FilterSet $filterSet
     *
     * @return FilterAbstract
     */
    final public function setFilterSet(FilterSet $filterSet): FilterAbstract
    {
        $this->filterSet = $filterSet;
        return $this;
    }

    /**
     * filter
     *
     * @param  mixed $query
     *
     * @return void
     */
    abstract public function filter(Select $query);

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

    protected function hasJoin(Select $select, $joinName): bool
    {
        $joins = $select->joins->getJoins();

        foreach ($joins as $join) {
            if ($join['name'] == $joinName) {
                return true;
            }
        }

        return false;
    }
}

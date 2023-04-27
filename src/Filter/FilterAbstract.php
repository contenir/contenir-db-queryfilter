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
    protected $input = [];

    /**
     * setInput
     *
     * @param  mixed $input
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
     * @param  mixed $input
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
    public function filter(Select $query)
    {
    }

    protected function getSql()
    {
        return new Sql($this->adapter);
    }

    protected function getWhere(Select $query)
    {
        $where = $query->where;
        if ($where === null) {
            $where = new Where();
        }

        return $where;
    }
}

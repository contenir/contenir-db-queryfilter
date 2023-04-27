<?php

declare(strict_types=1);

namespace Contenir\Db\QueryFilter;

class FilterSet
{
    protected $filter = [];
    protected $input  = [];

    public function __construct(
        iterable $filters = [],
        array $input = []
    ) {
        $this->addFilters($filters);
        $this->setInput($input);
    }

    public function setInput(array $input)
    {
        $this->input = $input;
        return $this;
    }

    public function getInput()
    {
        return $this->input;
    }

    public function filter($query)
    {
        foreach ($this->filter as $filter) {
            $filter->filter($query);
        }

        return $query;
    }

    public function addFilter($filter)
    {
        if (is_string($filter)) {
            $filter = new $filter();
        }

        $filter->setFilterSet($this);
        $this->filter[] = $filter;

        return $this;
    }

    public function addFilters($filters)
    {
        foreach ($filters as $filter) {
            $this->addFilter($filter);
        }

        return $this;
    }

    public function getFilters()
    {
        return $this->filter;
    }
}

<?php

declare(strict_types=1);

namespace Contenir\Db\QueryFilter;

use Laminas\Db\Sql\Select;

use function is_string;

class FilterSet
{
    protected array $filter = [];
    protected array $input  = [];

    public function __construct(
        iterable $filters = [],
        array $input = []
    ) {
        $this->addFilters($filters);
        $this->setInput($input);
    }

    public function setInput(array $input): self
    {
        $this->input = $input;
        return $this;
    }

    public function getInput(): array
    {
        return $this->input;
    }

    public function filter(Select $query): Select
    {
        foreach ($this->filter as $filter) {
            $filter->filter($query);
        }

        return $query;
    }

    public function addFilter(string|object $filter): self
    {
        if (is_string($filter)) {
            $filter = new $filter();
        }

        $filter->setFilterSet($this);
        $this->filter[] = $filter;

        return $this;
    }

    public function addFilters(iterable $filters): self
    {
        foreach ($filters as $filter) {
            $this->addFilter($filter);
        }

        return $this;
    }

    public function getFilters(): array
    {
        return $this->filter;
    }
}

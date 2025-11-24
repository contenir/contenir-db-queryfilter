<?php

/**
 * @see       https://github.com/contenir/contenir-db-queryfilter for the canonical source repository
 * @copyright https://github.com/contenir/contenir-db-queryfilter/blob/master/COPYRIGHT.md
 * @license   https://github.com/contenir/contenir-db-queryfilter/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Contenir\Db\QueryFilter;

use Laminas\Db\Sql\Select;

use function is_string;

/**
 * Container for filter definitions.
 *
 * Manages a collection of filter objects and coordinates their application
 * to database queries.
 */
class FilterSet
{
    /** @var array<int, Filter\AbstractFilter> */
    protected array $filter = [];

    /** @var array<string, mixed> User input values */
    protected array $input  = [];

    /**
     * @param iterable<Filter\AbstractFilter|string> $filters Filter instances or class names
     * @param array<string, mixed>                   $input   Initial input values
     */
    public function __construct(
        iterable $filters = [],
        array $input = []
    ) {
        $this->addFilters($filters);
        $this->setInput($input);
    }

    /**
     * Set user input values.
     *
     * @param array<string, mixed> $input Input values keyed by filter param name
     * @return self
     */
    public function setInput(array $input): self
    {
        $this->input = $input;
        return $this;
    }

    /**
     * Get user input values.
     *
     * @return array<string, mixed>
     */
    public function getInput(): array
    {
        return $this->input;
    }

    /**
     * Apply all filters to a SELECT query.
     *
     * @param Select $query SQL SELECT statement to modify
     * @return Select Modified query
     */
    public function filter(Select $query): Select
    {
        foreach ($this->filter as $filter) {
            $filter->filter($query);
        }

        return $query;
    }

    /**
     * Add a filter to the set.
     *
     * @param string|object $filter Filter instance or class name
     * @return self
     */
    public function addFilter(string|object $filter): self
    {
        if (is_string($filter)) {
            $filter = new $filter();
        }

        $filter->setFilterSet($this);
        $this->filter[] = $filter;

        return $this;
    }

    /**
     * Add multiple filters to the set.
     *
     * @param iterable<Filter\AbstractFilter|string> $filters Filter instances or class names
     * @return self
     */
    public function addFilters(iterable $filters): self
    {
        foreach ($filters as $filter) {
            $this->addFilter($filter);
        }

        return $this;
    }

    /**
     * Get all filters in this set.
     *
     * @return array<int, Filter\AbstractFilter>
     */
    public function getFilters(): array
    {
        return $this->filter;
    }
}

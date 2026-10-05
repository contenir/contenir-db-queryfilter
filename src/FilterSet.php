<?php

/**
 * @see       https://github.com/contenir/contenir-db-queryfilter for the canonical source repository
 */

declare(strict_types=1);

namespace Contenir\Db\QueryFilter;

use InvalidArgumentException;
use Laminas\Db\Sql\Select;
use RuntimeException;

use function array_filter;
use function array_values;
use function is_a;
use function is_string;
use function sprintf;

/**
 * Container for filter definitions.
 *
 * Manages a collection of filter objects and coordinates their application
 * to database queries.
 *
 * @api
 */
class FilterSet
{
    /** @var array<int, Filter\AbstractFilter> */
    protected array $filter = [];

    /** @var array<string, mixed> User input values */
    protected array $input = [];

    /**
     * @param iterable<Filter\AbstractFilter|class-string<Filter\AbstractFilter>> $filters Filter instances or class names
     * @param array<string, mixed>                                               $input   Initial input values
     */
    public function __construct(
        iterable $filters = [],
        array $input = [],
    ) {
        $this->addFilters($filters);
        $this->setInput($input);
    }

    /**
     * Add a filter to the set.
     *
     * @param Filter\AbstractFilter|string $filter Filter instance, or the class name of an AbstractFilter subclass
     *
     * @throws InvalidArgumentException If a class name does not name an AbstractFilter subclass.
     *
     * @mago-expect analysis:unsafe-instantiation Filters are documented as constructible without arguments.
     */
    public function addFilter(Filter\AbstractFilter|string $filter): self
    {
        if (is_string($filter)) {
            if (! is_a($filter, Filter\AbstractFilter::class, allow_string: true)) {
                throw new InvalidArgumentException(sprintf(
                    'Filter class "%s" must extend %s.',
                    $filter,
                    Filter\AbstractFilter::class,
                ));
            }

            $filter = new $filter();
        }

        $filter->setFilterSet($this);
        $this->filter[] = $filter;

        return $this;
    }

    /**
     * Add multiple filters to the set.
     *
     * @param iterable<Filter\AbstractFilter|class-string<Filter\AbstractFilter>> $filters Filter instances or class names
     */
    public function addFilters(iterable $filters): self
    {
        foreach ($filters as $filter) {
            $this->addFilter($filter);
        }

        return $this;
    }

    /**
     * Apply all filters to a SELECT query.
     *
     * @param Select $query SQL SELECT statement to modify
     * @return Select Modified query
     */
    public function applyFilters(Select $query): Select
    {
        foreach ($this->filter as $filter) {
            $filter->filter($query);
        }

        return $query;
    }

    /**
     * Clear all filters.
     */
    public function clear(): self
    {
        $this->filter = [];

        return $this;
    }

    /**
     * Apply all filters to a SELECT query.
     *
     * @deprecated Use applyFilters() instead.
     * @param Select $query SQL SELECT statement to modify
     * @return Select Modified query
     */
    public function filter(Select $query): Select
    {
        return $this->applyFilters($query);
    }

    /**
     * Get a filter by its parameter name.
     *
     * @throws RuntimeException If a filter in the set has no query parameter name.
     */
    public function getFilter(string $filterParam): ?Filter\AbstractFilter
    {
        foreach ($this->filter as $filter) {
            if ($filter->getFilterParam() === $filterParam) {
                return $filter;
            }
        }

        return null;
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
     * Check if a filter with a given parameter name exists.
     *
     * @throws RuntimeException If a filter in the set has no query parameter name.
     */
    public function hasFilter(string $filterParam): bool
    {
        foreach ($this->filter as $filter) {
            if ($filter->getFilterParam() === $filterParam) {
                return true;
            }
        }

        return false;
    }

    /**
     * Remove a filter by parameter name.
     *
     * @throws RuntimeException If a filter in the set has no query parameter name.
     */
    public function removeFilter(string $filterParam): self
    {
        $this->filter = array_values(array_filter(
            $this->filter,
            /** @throws RuntimeException */
            static fn(Filter\AbstractFilter $filter): bool => $filter->getFilterParam() !== $filterParam,
        ));

        return $this;
    }

    /**
     * Set user input values.
     *
     * @param array<string, mixed> $input Input values keyed by filter param name
     */
    public function setInput(array $input): self
    {
        $this->input = $input;
        return $this;
    }
}

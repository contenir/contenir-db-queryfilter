<?php

/**
 * @see       https://github.com/contenir/contenir-db-queryfilter for the canonical source repository
 */

declare(strict_types=1);

namespace ContenirTest\Db\QueryFilter\TestAsset;

use Contenir\Db\QueryFilter\AbstractQueryFilter;
use Laminas\Db\Sql\Select;
use ReflectionMethod;
use RuntimeException;

/**
 * Testable QueryFilter subclass that tracks hook calls.
 *
 * Exposes protected methods for testing and tracks when hooks are called.
 */
class TestableQueryFilter extends AbstractQueryFilter
{
    /** @var bool Whether onBeforeFilter was called */
    public bool $onBeforeFilterCalled = false;

    /** @var bool Whether onAfterFilter was called */
    public bool $onAfterFilterCalled = false;

    /** @var Select|null The select passed to onBeforeFilter */
    public ?Select $beforeFilterSelect = null;

    /** @var Select|null The select passed to onAfterFilter */
    public ?Select $afterFilterSelect = null;

    /** @var callable|null Custom before filter callback */
    public $beforeFilterCallback = null;

    /** @var callable|null Custom after filter callback */
    public $afterFilterCallback = null;

    /**
     * Hook called before filters are applied.
     */
    protected function onBeforeFilter(Select $select): void
    {
        $this->onBeforeFilterCalled = true;
        $this->beforeFilterSelect   = $select;

        if ($this->beforeFilterCallback !== null) {
            ($this->beforeFilterCallback)($select);
        }
    }

    /**
     * Hook called after filters are applied.
     */
    protected function onAfterFilter(Select $select): void
    {
        $this->onAfterFilterCalled = true;
        $this->afterFilterSelect   = $select;

        if ($this->afterFilterCallback !== null) {
            ($this->afterFilterCallback)($select);
        }
    }

    /**
     * Expose validateState for testing.
     *
     * @throws RuntimeException If form or table not set.
     */
    public function callValidateState(): void
    {
        $reflection = new ReflectionMethod($this, 'validateState');
        $reflection->setAccessible(true);
        $reflection->invoke($this);
    }

    /**
     * Build and return the filtered Select without executing.
     *
     * This allows testing the SQL generation without a database connection.
     *
     * @return Select The Select with all filters applied
     */
    public function buildFilteredSelect(): Select
    {
        $select = $this->queryFilterTable->select();

        $this->onBeforeFilter($select);
        $this->form->getFilterSet()->applyFilters($select);
        $this->onAfterFilter($select);

        $this->queryFilterTable->prepareSelect($select);

        return $select;
    }
}

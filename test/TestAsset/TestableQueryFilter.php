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

    /**
     * Hook called before filters are applied.
     */
    protected function onBeforeFilter(Select $select): void
    {
        $this->onBeforeFilterCalled = true;
        $this->beforeFilterSelect   = $select;
    }

    /**
     * Hook called after filters are applied.
     */
    protected function onAfterFilter(Select $select): void
    {
        $this->onAfterFilterCalled = true;
        $this->afterFilterSelect   = $select;
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
}

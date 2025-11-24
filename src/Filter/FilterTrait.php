<?php

/**
 * @see       https://github.com/contenir/contenir-db-queryfilter for the canonical source repository
 */

declare(strict_types=1);

namespace Contenir\Db\QueryFilter\Filter;

use RuntimeException;

use function sprintf;

/**
 * Common filter properties and methods.
 *
 * Provides standard filter configuration options and accessors
 * shared by all filter implementations.
 */
trait FilterTrait
{
    /** @var string|null Query parameter name */
    protected ?string $filterParam = null;

    /** @var string|iterable|null Default value when parameter is missing */
    protected string|iterable|null $filterDefault = null;

    /** @var bool Whether the filter is required */
    protected bool $filterRequired = false;

    /** @var string|null Form element label */
    protected ?string $filterLabel = null;

    /** @var array<string, mixed>|null HTML attributes for form element */
    protected ?array $filterAttributes = [];

    /**
     * Get the current filter value from input or default.
     */
    public function getFilterValue(): string|iterable|int|null
    {
        return $this->filterSet->getInput()[$this->filterParam] ?? $this->filterDefault;
    }

    /**
     * Get the query parameter name.
     *
     * @throws RuntimeException If filterParam is not set.
     */
    public function getFilterParam(): ?string
    {
        if ($this->filterParam === null) {
            throw new RuntimeException(
                sprintf(
                    'No param has been named for the filter %s',
                    static::class
                )
            );
        }

        return $this->filterParam;
    }

    /**
     * Get the default filter value.
     */
    public function getFilterDefault(): string|null|iterable
    {
        return $this->filterDefault;
    }

    /**
     * Get the form element label.
     */
    public function getFilterLabel(): ?string
    {
        return $this->filterLabel;
    }

    /**
     * Check if filter is required.
     */
    public function getFilterRequired(): bool
    {
        return $this->filterRequired;
    }

    /**
     * Get form element specification.
     *
     * Override in subclasses to provide form element configuration.
     *
     * @return array<string, mixed>|null
     */
    public function getElement(): ?array
    {
        return null;
    }

    /**
     * Get input filter specification.
     *
     * Override in subclasses to provide validation rules.
     *
     * @return array<string, mixed>|null
     */
    public function getInputFilterSpecification(): ?array
    {
        return null;
    }
}

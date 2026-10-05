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
 *
 * @api
 *
 * @require-extends AbstractFilter
 */
trait FilterTrait
{
    /** @var string|null Query parameter name */
    protected ?string $filterParam = null;

    /** @var string|iterable<array-key, mixed>|null Default value when parameter is missing */
    protected string|iterable|null $filterDefault = null;

    /** @var bool Whether the filter is required */
    protected bool $filterRequired = false;

    /** @var string|null Form element label */
    protected ?string $filterLabel = null;

    /** @var array<string, mixed>|null HTML attributes for form element */
    protected ?array $filterAttributes = [];

    /**
     * Get form element specification.
     *
     * Override in subclasses to provide form element configuration.
     *
     * @mago-expect analysis:overly-wide-return-type Subclasses override this hook and return an element spec.
     * @mago-expect analysis:imprecise-type Laminas spec arrays have no narrower shared type.
     */
    public function getElement(): ?array
    {
        return null;
    }

    /**
     * Get the default filter value.
     *
     * @return string|iterable<array-key, mixed>|null
     */
    public function getFilterDefault(): string|iterable|null
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
     * Get the query parameter name.
     *
     * Nullable because immutable filters override it to return null.
     *
     * @throws RuntimeException If filterParam is not set.
     *
     * @mago-expect analysis:overly-wide-return-type AbstractFilterImmutable overrides this to return null.
     */
    public function getFilterParam(): ?string
    {
        if (null === $this->filterParam) {
            throw new RuntimeException(
                sprintf(
                    'No param has been named for the filter %s',
                    static::class,
                ),
            );
        }

        return $this->filterParam;
    }

    /**
     * Check if filter is required.
     */
    public function getFilterRequired(): bool
    {
        return $this->filterRequired;
    }

    /**
     * Get the current filter value from input or default.
     *
     * Filters without a query parameter (immutable filters) always resolve
     * to their default.
     *
     * @return string|iterable<array-key, mixed>|int|null
     *
     * @mago-expect analysis:mixed-return-statement Input values are request data; PHP enforces the declared type on return.
     */
    public function getFilterValue(): string|iterable|int|null
    {
        if (null === $this->filterParam) {
            return $this->filterDefault;
        }

        return $this->filterSet->getInput()[$this->filterParam] ?? $this->filterDefault;
    }

    /**
     * Get input filter specification.
     *
     * Override in subclasses to provide validation rules.
     *
     * @mago-expect analysis:overly-wide-return-type Subclasses override this hook and return an input spec.
     * @mago-expect analysis:imprecise-type Laminas spec arrays have no narrower shared type.
     */
    public function getInputFilterSpecification(): ?array
    {
        return null;
    }
}

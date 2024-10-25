<?php

declare(strict_types=1);

namespace Contenir\Db\QueryFilter\Filter;

use RuntimeException;

use function sprintf;

trait FilterTrait
{
    protected ?string $filterParam                = null;
    protected string|iterable|null $filterDefault = null;
    protected bool $filterRequired                = false;
    protected ?string $filterLabel                = null;
    protected ?array $filterAttributes            = [];

    public function getFilterValue(): string|iterable|null
    {
        return $this->filterSet->getInput()[$this->filterParam] ?? $this->filterDefault;
    }

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

    public function getFilterDefault(): string|null|iterable
    {
        return $this->filterDefault;
    }

    public function getFilterLabel(): ?string
    {
        return $this->filterLabel;
    }

    public function getFilterRequired(): bool
    {
        return $this->filterRequired;
    }

    public function getElement(): ?array
    {
        return null;
    }

    public function getInputFilterSpecification(): ?array
    {
        return null;
    }
}

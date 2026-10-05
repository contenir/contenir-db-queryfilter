<?php

declare(strict_types=1);

namespace ContenirTest\Db\QueryFilter\TestAsset\Filter;

use Contenir\Db\QueryFilter\Filter\AbstractFilter;
use Laminas\Db\Sql\Select;
use Override;

/**
 * Filter whose element and input specifications are empty arrays.
 */
final class BlankSpecFilter extends AbstractFilter
{
    protected ?string $filterParam = 'blank';

    #[Override]
    public function filter(Select $query): void {}

    /**
     * @return array<string, mixed>
     */
    #[Override]
    public function getElement(): array
    {
        return [];
    }

    /**
     * @return array<string, mixed>
     */
    #[Override]
    public function getInputFilterSpecification(): array
    {
        return [];
    }
}

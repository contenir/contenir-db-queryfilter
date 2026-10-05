<?php

declare(strict_types=1);

namespace ContenirTest\Db\QueryFilter\TestAsset\Filter;

use Contenir\Db\QueryFilter\Filter\AbstractFilterSelect;
use Override;
use PhpDb\Sql\Select;

/**
 * Select filter: category = value.
 */
final class CategoryFilter extends AbstractFilterSelect
{
    protected ?string $filterParam = 'category';

    protected ?string $filterLabel = 'Category';

    #[Override]
    public function filter(Select $query): void
    {
        $value = $this->getFilterValue();
        if (null === $value) {
            return;
        }

        $query->where->equalTo('category', $value);
    }

    /**
     * @return array<string, string>
     */
    #[Override]
    public function getValueOptions(): array
    {
        return ['books' => 'Books', 'music' => 'Music'];
    }
}

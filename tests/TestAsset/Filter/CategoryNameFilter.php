<?php

declare(strict_types=1);

namespace ContenirTest\Db\QueryFilter\TestAsset\Filter;

use Contenir\Db\QueryFilter\Filter\AbstractFilter;
use Laminas\Db\Sql\Select;
use Override;

/**
 * Joins the category table once, however often it is applied, then
 * filters on the joined name through getWhere().
 */
final class CategoryNameFilter extends AbstractFilter
{
    protected ?string $filterParam = 'category_name';

    #[Override]
    public function filter(Select $query): void
    {
        if (! $this->hasJoin($query, 'category')) {
            $query->join('category', 'category.code = products.category', []);
        }

        $this->getWhere($query)->equalTo('category.name', $this->getFilterValue());
    }
}

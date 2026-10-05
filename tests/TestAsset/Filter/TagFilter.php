<?php

declare(strict_types=1);

namespace ContenirTest\Db\QueryFilter\TestAsset\Filter;

use Contenir\Db\QueryFilter\Filter\AbstractFilter;
use Laminas\Db\Sql\Select;
use Override;

/**
 * Builds a sub-select through getSql(): id IN (tagged product ids).
 */
final class TagFilter extends AbstractFilter
{
    protected ?string $filterParam = 'tag';

    #[Override]
    public function filter(Select $query): void
    {
        $tagged = $this->getSql()
            ->select('product_tag')
            ->columns(['product_id'])
            ->where(['tag' => $this->getFilterValue()]);

        $query->where->in('id', $tagged);
    }
}

<?php

declare(strict_types=1);

namespace ContenirTest\Db\QueryFilter\TestAsset\Filter;

use Contenir\Db\QueryFilter\Filter\AbstractFilterHidden;
use Override;
use PhpDb\Sql\Select;

/**
 * Hidden filter: no form element, value from input or its default.
 */
final class TenantFilter extends AbstractFilterHidden
{
    protected ?string $filterParam = 'tenant';

    protected string|iterable|null $filterDefault = '7';

    #[Override]
    public function filter(Select $query): void
    {
        $query->where->equalTo('tenant_id', $this->getFilterValue());
    }
}

<?php

declare(strict_types=1);

namespace ContenirTest\Db\QueryFilter\TestAsset\QueryFilter;

use Contenir\Db\QueryFilter\AbstractQueryFilter;
use Override;
use PhpDb\Sql\Select;

/**
 * Uses both hooks: tenant isolation before the filters, a soft-delete
 * condition after them.
 */
final class HookedQueryFilter extends AbstractQueryFilter
{
    #[Override]
    protected function onAfterFilter(Select $select): void
    {
        $select->where->isNull('deleted_at');
    }

    #[Override]
    protected function onBeforeFilter(Select $select): void
    {
        $select->where->equalTo('tenant_id', 3);
    }
}

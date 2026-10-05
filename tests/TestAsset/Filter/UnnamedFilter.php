<?php

declare(strict_types=1);

namespace ContenirTest\Db\QueryFilter\TestAsset\Filter;

use Contenir\Db\QueryFilter\Filter\AbstractFilter;
use Override;
use PhpDb\Sql\Select;

/**
 * Misconfigured filter: no query parameter name and not immutable.
 */
final class UnnamedFilter extends AbstractFilter
{
    #[Override]
    public function filter(Select $query): void {}
}

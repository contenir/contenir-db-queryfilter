<?php

declare(strict_types=1);

namespace ContenirTest\Db\QueryFilter\TestAsset\Db;

use Override;
use PhpDb\Adapter\Platform\Sql92;

use function addslashes;

/**
 * SQL-92 platform that quotes values without a driver connection, so SQL
 * can be rendered in unit tests (the stock platform refuses to).
 */
final class MockPlatform extends Sql92
{
    #[Override]
    public function quoteValue(string $value): string
    {
        return "'" . addslashes($value) . "'";
    }
}

<?php

declare(strict_types=1);

namespace ContenirTest\Db\QueryFilter\TestAsset\Db;

use Laminas\Db\Adapter\Platform\Sql92;
use Override;

use function addslashes;
use function is_int;

/**
 * SQL-92 platform that quotes values without a driver connection, so SQL
 * can be rendered in unit tests without the platform's quoting notice.
 */
final class MockPlatform extends Sql92
{
    /**
     * @param mixed $value
     */
    #[Override]
    public function quoteValue($value): string
    {
        if (is_int($value)) {
            return (string) $value;
        }

        return "'" . addslashes((string) $value) . "'";
    }
}

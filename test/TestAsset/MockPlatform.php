<?php

/**
 * @see       https://github.com/contenir/contenir-db-queryfilter for the canonical source repository
 */

declare(strict_types=1);

namespace ContenirTest\Db\QueryFilter\TestAsset;

use Laminas\Db\Adapter\Platform\Sql92;

/**
 * Mock platform for testing SQL generation.
 *
 * Allows quoting values without a database driver connection,
 * which is necessary for unit testing SQL generation.
 */
class MockPlatform extends Sql92
{
    /**
     * Quote a value without requiring a database driver.
     *
     * @param mixed $value
     * @return string
     */
    public function quoteValue($value)
    {
        if (is_int($value)) {
            return (string) $value;
        }

        return "'" . addslashes((string) $value) . "'";
    }
}
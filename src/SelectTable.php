<?php

/**
 * @see       https://github.com/contenir/contenir-db-queryfilter for the canonical source repository
 */

declare(strict_types=1);

namespace Contenir\Db\QueryFilter;

use PhpDb\Sql\Select;
use PhpDb\Sql\TableIdentifier;

use function array_key_first;
use function is_array;
use function is_string;

/**
 * Reads the table a SELECT is FROM.
 *
 * @internal
 */
final readonly class SelectTable
{
    /**
     * The name to qualify columns with: the table, or the alias of an
     * aliased table. Null for a select without a table.
     *
     * @mago-expect analysis:mixed-assignment Select::getRawState() is untyped; the type is checked here.
     */
    public static function nameOf(Select $select): ?string
    {
        $table = $select->getRawState(Select::TABLE);
        if ($table instanceof TableIdentifier) {
            return $table->getTable();
        }

        if (is_array($table)) {
            /** Select::from() only accepts a one-entry [alias => table] array. */
            return (string) array_key_first($table);
        }

        return is_string($table) ? $table : null;
    }
}

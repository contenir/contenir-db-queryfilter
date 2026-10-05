<?php

/**
 * @see       https://github.com/contenir/contenir-db-queryfilter for the canonical source repository
 */

declare(strict_types=1);

namespace Contenir\Db\QueryFilter;

use PhpDb\Sql\Select;

/**
 * The source a query filter filters and pages: something that creates a base
 * SELECT and runs a SELECT into items.
 *
 * The two methods match contenir/contenir-db-model 2.x's `Repository`, so a
 * repository subclass implements this interface without any code:
 *
 *     final class ProductRepository extends Repository implements QueryFilterTableInterface
 *
 * Any other table gateway or repository can implement it too.
 *
 * @api
 */
interface QueryFilterTableInterface
{
    /**
     * A new SELECT over the table, with the columns, joins and default order
     * that fetch() needs. Filters and hooks add their conditions to it.
     */
    public function createSelect(): Select;

    /**
     * Run a SELECT built from createSelect() and return its items (entities
     * or rows) in order.
     *
     * @return array<int, mixed>
     */
    public function fetch(Select $select): array;
}

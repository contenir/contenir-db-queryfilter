<?php

/**
 * @see       https://github.com/contenir/contenir-db-queryfilter for the canonical source repository
 */

declare(strict_types=1);

namespace Contenir\Db\QueryFilter\Paginator;

use Contenir\Db\QueryFilter\QueryFilterTableInterface;
use Laminas\Paginator\Adapter\AdapterInterface as PaginatorAdapterInterface;
use Override;
use PhpDb\Adapter\AdapterInterface;
use PhpDb\Sql\Expression;
use PhpDb\Sql\Select;
use PhpDb\Sql\Sql;

use function is_array;
use function is_numeric;

/**
 * Laminas Paginator adapter over a filtered SELECT.
 *
 * Pages are fetched through the table's fetch(), so they are whatever the
 * table returns (entities for a contenir-db-model repository). The total is
 * counted with `SELECT COUNT(*) AS C FROM (<select>) AS total_count` on the
 * adapter, once per instance.
 *
 * @api
 *
 * @implements PaginatorAdapterInterface<int, mixed>
 */
final class SelectAdapter implements PaginatorAdapterInterface
{
    private ?int $count = null;

    public function __construct(
        private readonly QueryFilterTableInterface $table,
        private readonly Select $select,
        private readonly AdapterInterface $adapter,
    ) {}

    /**
     * The total number of items, counted on the first call.
     */
    #[Override]
    public function count(): int
    {
        return $this->count ??= $this->countRows();
    }

    /**
     * Fetch one page of items through the table. The filtered select is
     * cloned, never modified.
     *
     * @param int $offset           Page offset
     * @param int $itemCountPerPage Number of items per page
     *
     * @return array<int, mixed>
     */
    #[Override]
    public function getItems($offset, $itemCountPerPage): array
    {
        $page = clone $this->select;
        $page->offset($offset)->limit($itemCountPerPage);

        return $this->table->fetch($page);
    }

    /**
     * @mago-expect analysis:mixed-assignment Driver result rows are untyped arrays.
     */
    private function countRows(): int
    {
        /** The count query renders a copy, so the filtered select is never modified. */
        $countSelect = new Select();
        $countSelect->from(['total_count' => clone $this->select])->columns(['C' => new Expression('COUNT(*)')]);

        $result = (new Sql($this->adapter))->prepareStatementForSqlObject($countSelect)
            ->execute();
        $row   = $result?->current();
        $count = is_array($row) ? $row['C'] ?? 0 : 0;

        return is_numeric($count) ? (int) $count : 0;
    }
}

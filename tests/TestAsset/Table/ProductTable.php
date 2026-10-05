<?php

declare(strict_types=1);

namespace ContenirTest\Db\QueryFilter\TestAsset\Table;

use Contenir\Db\QueryFilter\QueryFilterTableInterface;
use Override;
use PhpDb\Adapter\AdapterInterface;
use PhpDb\Sql\Select;
use PhpDb\Sql\Sql;

use function array_values;
use function is_array;

/**
 * Minimal table gateway over "products" that returns rows as arrays,
 * ordered by id.
 */
final class ProductTable implements QueryFilterTableInterface
{
    /**
     * @param string|array<string, string> $table A table name, or [alias => table]
     */
    public function __construct(
        private readonly AdapterInterface $adapter,
        private readonly string|array $table = 'products',
    ) {}

    #[Override]
    public function createSelect(): Select
    {
        return (new Select($this->table))->order(['id' => 'ASC']);
    }

    /**
     * @return list<array<string, mixed>>
     */
    #[Override]
    public function fetch(Select $select): array
    {
        $rows = [];
        foreach ((new Sql($this->adapter))->prepareStatementForSqlObject($select)
            ->execute() ?? [] as $row) {
            if (! is_array($row)) {
                continue;
            }

            $rows[] = $row;
        }

        return array_values($rows);
    }
}

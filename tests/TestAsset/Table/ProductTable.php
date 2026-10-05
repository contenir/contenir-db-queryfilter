<?php

declare(strict_types=1);

namespace ContenirTest\Db\QueryFilter\TestAsset\Table;

use Contenir\Db\QueryFilter\QueryFilterTableInterface;
use Laminas\Db\Adapter\Adapter;
use Laminas\Db\ResultSet\ResultSet;
use Laminas\Db\ResultSet\ResultSetInterface;
use Laminas\Db\Sql\Select;
use Override;

/**
 * Minimal table gateway over the "products" table; orders by id.
 */
final class ProductTable implements QueryFilterTableInterface
{
    public function __construct(
        private readonly Adapter $adapter,
        private readonly string $table = 'products',
    ) {}

    #[Override]
    public function getAdapter(): Adapter
    {
        return $this->adapter;
    }

    #[Override]
    public function getResultSet(): ResultSetInterface
    {
        return new ResultSet(ResultSet::TYPE_ARRAY);
    }

    #[Override]
    public function getTable(): string
    {
        return $this->table;
    }

    #[Override]
    public function prepareSelect(Select $select): void
    {
        $select->order(['id' => 'ASC']);
    }

    #[Override]
    public function select(): Select
    {
        return new Select($this->table);
    }
}

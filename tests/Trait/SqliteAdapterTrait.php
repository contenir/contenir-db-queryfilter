<?php

declare(strict_types=1);

namespace ContenirTest\Db\QueryFilter\Trait;

use Laminas\Db\Adapter\Adapter;

/**
 * Builds a fresh in-memory SQLite database per test, seeded with a small
 * product catalogue, so no state survives between tests. Call
 * {@see self::setUpSqliteAdapter()} from setUp().
 */
trait SqliteAdapterTrait
{
    protected Adapter $adapter;

    protected function setUpSqliteAdapter(): void
    {
        $this->adapter = new Adapter([
            'driver'   => 'Pdo_Sqlite',
            'database' => ':memory:',
        ]);

        $this->execute(
            'CREATE TABLE products (id INTEGER PRIMARY KEY, name TEXT NOT NULL, category TEXT NOT NULL, '
                . 'status TEXT NOT NULL, active INTEGER NOT NULL)',
        );

        foreach ([
            [1, 'Red Book',     'books', 'live',  1],
            [2, 'Blue Book',    'books', 'draft', 1],
            [3, 'Red Record',   'music', 'live',  1],
            [4, 'Old Red Book', 'books', 'live',  0],
        ] as [$id, $name, $category, $status, $active]) {
            $this->execute(
                "INSERT INTO products VALUES ({$id}, '{$name}', '{$category}', '{$status}', {$active})",
            );
        }
    }

    private function execute(string $sql): void
    {
        $this->adapter->query($sql, Adapter::QUERY_MODE_EXECUTE);
    }
}

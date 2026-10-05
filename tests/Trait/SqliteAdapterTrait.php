<?php

declare(strict_types=1);

namespace ContenirTest\Db\QueryFilter\Trait;

use PDO;
use PhpDb\Adapter\Adapter;
use PhpDb\Sqlite\AdapterPlatform;
use PhpDb\Sqlite\Pdo\Connection;
use PhpDb\Sqlite\Pdo\Driver;

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
        $pdo = new PDO('sqlite::memory:');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->exec(
            'CREATE TABLE products (id INTEGER PRIMARY KEY, name TEXT NOT NULL, category TEXT NOT NULL, '
                . 'status TEXT NOT NULL, active INTEGER NOT NULL)',
        );
        $pdo->exec(
            "INSERT INTO products VALUES (1, 'Red Book', 'books', 'live', 1), (2, 'Blue Book', 'books', 'draft', 1), "
                . "(3, 'Red Record', 'music', 'live', 1), (4, 'Old Red Book', 'books', 'live', 0)",
        );

        $driver        = new Driver(new Connection($pdo));
        $this->adapter = new Adapter($driver, new AdapterPlatform($driver));
    }
}

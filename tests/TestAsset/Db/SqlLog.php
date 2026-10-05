<?php

declare(strict_types=1);

namespace ContenirTest\Db\QueryFilter\TestAsset\Db;

/**
 * Records the SQL a fake adapter runs, in order.
 */
final class SqlLog
{
    /** @var list<string> */
    public array $statements = [];

    public function record(string $sql): void
    {
        $this->statements[] = $sql;
    }
}

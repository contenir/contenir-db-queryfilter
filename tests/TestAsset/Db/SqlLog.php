<?php

declare(strict_types=1);

namespace ContenirTest\Db\QueryFilter\TestAsset\Db;

/**
 * Records the SQL a fake adapter runs, and the parameters bound to each
 * statement, in order.
 */
final class SqlLog
{
    /** @var list<string> */
    public array $statements = [];

    /** @var list<array<string, mixed>> */
    public array $parameters = [];

    /**
     * @param array<string, mixed> $parameters
     */
    public function record(string $sql, array $parameters = []): void
    {
        $this->statements[] = $sql;
        $this->parameters[] = $parameters;
    }
}

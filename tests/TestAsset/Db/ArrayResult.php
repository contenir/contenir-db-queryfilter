<?php

declare(strict_types=1);

namespace ContenirTest\Db\QueryFilter\TestAsset\Db;

use ArrayIterator;
use Override;
use PhpDb\Adapter\Driver\ResultInterface;
use PhpDb\ResultSet\ResultSet;
use PhpDb\ResultSet\ResultSetInterface;

use function count;

/**
 * In-memory driver result over a fixed list of rows.
 */
final class ArrayResult implements ResultInterface
{
    /** @var ArrayIterator<int, array<string, mixed>> */
    private ArrayIterator $rows;

    /**
     * @param list<array<string, mixed>> $rows
     */
    public function __construct(array $rows = [])
    {
        $this->rows = new ArrayIterator($rows);
    }

    #[Override]
    public function buffer(): void {}

    #[Override]
    public function count(): int
    {
        return count($this->rows);
    }

    /**
     * @return array<string, mixed>|null
     */
    #[Override]
    public function current(): ?array
    {
        return $this->rows->current();
    }

    #[Override]
    public function getAffectedRows(): int
    {
        return 0;
    }

    #[Override]
    public function getFieldCount(): int
    {
        return 0;
    }

    #[Override]
    public function getGeneratedValue(): null
    {
        return null;
    }

    #[Override]
    public function getQueryResult(?ResultSetInterface $resultPrototype = null): ResultSetInterface
    {
        return (new ResultSet())->initialize($this);
    }

    #[Override]
    public function getResource(): null
    {
        return null;
    }

    #[Override]
    public function isBuffered(): bool
    {
        return false;
    }

    #[Override]
    public function isQueryResult(): bool
    {
        return true;
    }

    #[Override]
    public function key(): ?int
    {
        return $this->rows->key();
    }

    #[Override]
    public function next(): void
    {
        $this->rows->next();
    }

    #[Override]
    public function rewind(): void
    {
        $this->rows->rewind();
    }

    #[Override]
    public function valid(): bool
    {
        return $this->rows->valid();
    }
}

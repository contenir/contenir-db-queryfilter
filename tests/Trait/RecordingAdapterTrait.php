<?php

declare(strict_types=1);

namespace ContenirTest\Db\QueryFilter\Trait;

use ContenirTest\Db\QueryFilter\TestAsset\Db\ArrayResult;
use ContenirTest\Db\QueryFilter\TestAsset\Db\MockPlatform;
use ContenirTest\Db\QueryFilter\TestAsset\Db\RecordingStatement;
use ContenirTest\Db\QueryFilter\TestAsset\Db\SqlLog;
use PhpDb\Adapter\AdapterInterface;
use PhpDb\Adapter\Driver\DriverInterface;

use function array_shift;

/**
 * Builds a php-db adapter double: nothing touches a database. Every
 * statement, prepared or run directly through query(), is recorded in
 * {@see self::$sqlLog}; prepared statements return the queued rows in order.
 */
trait RecordingAdapterTrait
{
    protected SqlLog $sqlLog;

    /**
     * @param list<list<array<string, mixed>>|null> $resultRows Rows for each prepared statement, in execution
     *                                                     order; null for a statement that returns no result
     */
    protected function createRecordingAdapter(array $resultRows = []): AdapterInterface
    {
        $this->sqlLog = new SqlLog();
        $log          = $this->sqlLog;

        $driver = $this->createStub(DriverInterface::class);
        $driver->method('formatParameterName')->willReturn('?');
        $driver->method('createStatement')
            ->willReturnCallback(static function () use ($log, &$resultRows): RecordingStatement {
                return new RecordingStatement($log, [] === $resultRows ? [] : array_shift($resultRows));
            });

        $adapter = $this->createStub(AdapterInterface::class);
        $adapter->method('getDriver')->willReturn($driver);
        $adapter->method('getPlatform')->willReturn(new MockPlatform());
        $adapter->method('query')
            ->willReturnCallback(static function (string $sql) use ($log): ArrayResult {
                $log->record($sql);

                return new ArrayResult();
            });

        return $adapter;
    }
}

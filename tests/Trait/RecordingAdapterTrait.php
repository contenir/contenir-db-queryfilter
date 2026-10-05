<?php

declare(strict_types=1);

namespace ContenirTest\Db\QueryFilter\Trait;

use ContenirTest\Db\QueryFilter\TestAsset\Db\ArrayResult;
use ContenirTest\Db\QueryFilter\TestAsset\Db\MockPlatform;
use ContenirTest\Db\QueryFilter\TestAsset\Db\RecordingStatement;
use ContenirTest\Db\QueryFilter\TestAsset\Db\SqlLog;
use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Adapter\Driver\ConnectionInterface;
use Laminas\Db\Adapter\Driver\DriverInterface;

use function array_shift;

/**
 * Builds a laminas-db Adapter over test doubles: nothing touches a database.
 * Every statement, prepared or executed directly, is recorded in
 * {@see self::$sqlLog}; prepared statements return the queued rows in order.
 */
trait RecordingAdapterTrait
{
    protected SqlLog $sqlLog;

    /**
     * @param list<list<array<string, mixed>>> $resultRows Rows for each prepared statement, in execution order
     */
    protected function createRecordingAdapter(array $resultRows = []): Adapter
    {
        $this->sqlLog = new SqlLog();
        $log          = $this->sqlLog;

        $connection = $this->createStub(ConnectionInterface::class);
        $connection->method('execute')
            ->willReturnCallback(static function (string $sql) use ($log): ArrayResult {
                $log->record($sql);

                return new ArrayResult();
            });

        $driver = $this->createStub(DriverInterface::class);
        $driver->method('getConnection')->willReturn($connection);
        $driver->method('formatParameterName')->willReturn('?');
        $driver->method('createStatement')
            ->willReturnCallback(static function () use ($log, &$resultRows): RecordingStatement {
                return new RecordingStatement($log, array_shift($resultRows) ?? []);
            });

        return new Adapter($driver, new MockPlatform());
    }
}

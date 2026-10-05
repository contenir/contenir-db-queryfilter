<?php

declare(strict_types=1);

namespace ContenirTest\Db\QueryFilter\Unit\Paginator;

use Contenir\Db\QueryFilter\Paginator\SelectAdapter;
use ContenirTest\Db\QueryFilter\TestAsset\Table\ProductTable;
use ContenirTest\Db\QueryFilter\Trait\RecordingAdapterTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class SelectAdapterTest extends TestCase
{
    use RecordingAdapterTrait;

    /**
     * @return array<string, array{list<array<string, mixed>>|null, int}>
     */
    public static function countProvider(): array
    {
        return [
            'count column'      => [[['C' => '3']], 3],
            'integer count'     => [[['C' => 5]], 5],
            'no row'            => [[], 0],
            'no count column'   => [[['X' => '3']], 0],
            'non-numeric count' => [[['C' => 'many']], 0],
            'no result'         => [null, 0],
        ];
    }

    /**
     * @param list<array<string, mixed>>|null $rows
     */
    #[Test]
    #[DataProvider('countProvider')]
    public function countReadsTheCountColumn(?array $rows, int $expected): void
    {
        static::assertSame($expected, $this->adapterFor([$rows])->count());
    }

    #[Test]
    public function countWrapsTheSelectInACountQueryOnce(): void
    {
        $pages = $this->adapterFor([[['C' => '2']]]);

        $pages->count();
        $pages->count();

        static::assertSame(
            ['SELECT COUNT(*) AS "C" FROM (SELECT "products".* FROM "products" ORDER BY "id" ASC) AS "total_count"'],
            $this->sqlLog->statements,
        );
    }

    #[Test]
    public function getItemsFetchesAPageThroughTheTable(): void
    {
        $rows = [['id' => '3', 'name' => 'Red Record']];

        static::assertSame($rows, $this->adapterFor([$rows])->getItems(20, 10));
    }

    #[Test]
    public function getItemsLimitsACopyOfTheSelect(): void
    {
        $pages = $this->adapterFor();

        $pages->getItems(0, 10);
        $pages->getItems(10, 10);

        static::assertSame(
            [
                'SELECT "products".* FROM "products" ORDER BY "id" ASC LIMIT ? OFFSET ?',
                'SELECT "products".* FROM "products" ORDER BY "id" ASC LIMIT ? OFFSET ?',
            ],
            $this->sqlLog->statements,
        );
    }

    /**
     * @param list<list<array<string, mixed>>|null> $results
     */
    private function adapterFor(array $results = []): SelectAdapter
    {
        $adapter = $this->createRecordingAdapter($results);
        $table   = new ProductTable($adapter);

        return new SelectAdapter($table, $table->createSelect(), $adapter);
    }
}

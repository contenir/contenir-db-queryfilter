<?php

declare(strict_types=1);

namespace ContenirTest\Db\QueryFilter\Unit;

use Contenir\Db\QueryFilter\QueryFilter;
use ContenirTest\Db\QueryFilter\TestAsset\Factory\QueryFilterFactory;
use ContenirTest\Db\QueryFilter\TestAsset\Filter\ActiveOnlyFilter;
use ContenirTest\Db\QueryFilter\TestAsset\Filter\CategoryFilter;
use ContenirTest\Db\QueryFilter\TestAsset\Filter\SearchFilter;
use ContenirTest\Db\QueryFilter\TestAsset\Filter\StatusFilter;
use ContenirTest\Db\QueryFilter\TestAsset\Filter\TenantFilter;
use ContenirTest\Db\QueryFilter\TestAsset\QueryFilter\HookedQueryFilter;
use ContenirTest\Db\QueryFilter\TestAsset\Table\ProductTable;
use ContenirTest\Db\QueryFilter\Trait\RecordingAdapterTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;

#[Group('unit')]
final class AbstractQueryFilterTest extends TestCase
{
    use RecordingAdapterTrait;

    /**
     * @return array<string, array{callable(QueryFilter): mixed, string}>
     */
    public static function missingDependencyProvider(): array
    {
        return [
            'form'       => [
                static fn(QueryFilter $filter): mixed => $filter->getForm(),
                'Form must be set before calling this method. Use setForm() first.',
            ],
            'table'      => [
                static fn(QueryFilter $filter): mixed => $filter->getQueryFilterTable(),
                'QueryFilterTable must be set before calling this method. Use setQueryFilterTable() first.',
            ],
            'table name' => [
                static fn(QueryFilter $filter): mixed => $filter->getTableName(),
                'Table name must be set before calling this method. Use setQueryFilterTable() or setTableName() first.',
            ],
        ];
    }

    /**
     * @return array<string, array{mixed, list<array<string, mixed>>, string}>
     */
    public static function positionProvider(): array
    {
        $rows = [
            ['position' => '1', 'qf_base_pk' => '10'],
            ['position' => '2', 'qf_base_pk' => null],
            ['position' => '3', 'qf_base_pk' => '20'],
        ];

        return [
            'integer key matches string column' => [20, $rows, 'POSITION IN (2,4)'],
            'string key matches string column'  => ['10', $rows, 'POSITION IN (0,2)'],
            'key not in the filtered set'       => [99, $rows, 'POSITION IN (-1,1)'],
            'entity without a key'              => [null, $rows, 'POSITION IN (-1,1)'],
        ];
    }

    /**
     * @return array<string, array{array<string, mixed>, bool}>
     */
    public static function submittedProvider(): array
    {
        return [
            'no parameters'      => [[], false],
            'unknown parameters' => [['page' => '2'], true],
            'filter parameters'  => [['search' => 'red'], true],
        ];
    }

    /**
     * @param callable(QueryFilter): mixed $accessor
     */
    #[Test]
    #[DataProvider('missingDependencyProvider')]
    public function accessorsFailUntilTheirDependencyIsSet(callable $accessor, string $message): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage($message);

        $accessor(new QueryFilter());
    }

    #[Test]
    public function constructorAcceptsTheForm(): void
    {
        $form = QueryFilterFactory::makeForm(new SearchFilter());

        static::assertSame($form, (new QueryFilter($form))->getForm());
    }

    #[Test]
    public function hooksRunBeforeAndAfterTheFilters(): void
    {
        $filter = QueryFilterFactory::make(
            $this->createRecordingAdapter([[['C' => '0']]]),
            [new ActiveOnlyFilter()],
            HookedQueryFilter::class,
        );

        $filter->getPagingResultSet()->count();

        static::assertStringContainsString(
            'WHERE "tenant_id" = ? AND "active" = ? AND "deleted_at" IS NULL',
            $this->sqlLog->statements[0],
        );
    }

    #[Test]
    public function pagingResultSetCountsTheFilteredQuery(): void
    {
        $filter = QueryFilterFactory::make($this->createRecordingAdapter([[['C' => '3']]]), [
            new SearchFilter(),
            new ActiveOnlyFilter(),
        ]);
        $filter->setQueryParams(['search' => 'red']);

        static::assertSame(3, $filter->getPagingResultSet()->count());
        static::assertSame(
            [
                'SELECT COUNT(*) AS "C" FROM (SELECT "products".* FROM "products" WHERE "name" LIKE ? AND "active" = ? '
                    . 'ORDER BY "id" ASC) AS "total_count"',
            ],
            $this->sqlLog->statements,
        );
    }

    #[Test]
    public function pagingResultSetNeedsAForm(): void
    {
        $filter = (new QueryFilter())->setQueryFilterTable(new ProductTable($this->createRecordingAdapter()));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Form must be set before calling this method.');

        $filter->getPagingResultSet();
    }

    /**
     * @param list<array<string, mixed>> $positionRows
     */
    #[Test]
    #[DataProvider('positionProvider')]
    public function positionLocatesTheEntityByPrimaryKey(mixed $key, array $positionRows, string $window): void
    {
        $filter = QueryFilterFactory::make(
            $this->createRecordingAdapter([$positionRows, []]),
            [new ActiveOnlyFilter()],
        );

        $filter->getPosition((object) ['resource_id' => $key]);

        static::assertStringContainsString($window, $this->sqlLog->statements[3]);
    }

    #[Test]
    public function positionNumbersTheFilteredRowsWithMysqlUserVariables(): void
    {
        $filter = QueryFilterFactory::make(
            $this->createRecordingAdapter([
                [['position' => '1', 'qf_base_pk' => '10'], ['position' => '2', 'qf_base_pk' => '20']],
                [],
            ]),
            [new ActiveOnlyFilter()],
        );

        $filter->getPosition((object) ['resource_id' => 20]);

        static::assertSame(
            [
                'SET @num := 0',
                'SELECT "current"."position" AS "position", "current"."qf_base_pk" AS "qf_base_pk" FROM (SELECT '
                    . '@num := @num + 1 AS "position", "base"."qf_base_pk" AS "qf_base_pk", "base"."qfBaseIdentifier" AS '
                    . '"qfBaseIdentifier", "base"."qfBaseTitle" AS "qfBaseTitle" FROM (SELECT "products"."resource_id" AS '
                    . '"qf_base_pk", "products"."slug" AS "qfBaseIdentifier", "products"."title" AS "qfBaseTitle" FROM '
                    . '"products" WHERE "active" = ? ORDER BY "id" ASC) AS "base" GROUP BY "qf_base_pk") AS "current" '
                    . 'ORDER BY "position" ASC',
                'SET @num := 0',
            ],
            [$this->sqlLog->statements[0], $this->sqlLog->statements[1], $this->sqlLog->statements[2]],
        );
    }

    #[Test]
    public function positionReturnsThePreviousAndNextRows(): void
    {
        $filter = QueryFilterFactory::make(
            $this->createRecordingAdapter([
                [['position' => '2', 'qf_base_pk' => '20']],
                [
                    ['pos' => 'prev', 'qf_base_pk' => '10', 'slug' => 'red-book', 'title' => 'Red Book'],
                    ['pos' => 'next', 'qf_base_pk' => '30', 'slug' => 'red-record', 'title' => 'Red Record'],
                ],
            ]),
            [new ActiveOnlyFilter()],
        );

        static::assertSame(
            [
                'prev' => ['id' => '10', 'slug' => 'red-book', 'title' => 'Red Book'],
                'next' => ['id' => '30', 'slug' => 'red-record', 'title' => 'Red Record'],
            ],
            $filter->getPosition((object) ['id' => 20], primaryKey: 'id'),
        );
    }

    #[Test]
    public function setQueryFilterTableAlsoSetsTheTableName(): void
    {
        $filter = (new QueryFilter())->setQueryFilterTable(new ProductTable($this->createRecordingAdapter(), 'items'));

        static::assertSame('items', $filter->getTableName());
    }

    #[Test]
    #[DataProvider('submittedProvider')]
    public function setQueryParamsMarksTheFilterValidatedAndRecordsSubmission(array $params, bool $submitted): void
    {
        $filter = QueryFilterFactory::make($this->createRecordingAdapter(), [new SearchFilter()]);
        $filter->setQueryParams($params);

        static::assertSame([true, $submitted], [$filter->isValidated(), $filter->isSubmitted()]);
    }

    #[Test]
    public function setQueryParamsNeedsATable(): void
    {
        $filter = new QueryFilter(QueryFilterFactory::makeForm(new SearchFilter()));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('QueryFilterTable must be set before calling this method.');

        $filter->setQueryParams([]);
    }

    #[Test]
    public function setQueryParamsPassesInvalidValuesThroughAfterFiltering(): void
    {
        $filter = QueryFilterFactory::make($this->createRecordingAdapter(), [new CategoryFilter()]);

        $filter->setQueryParams(['category' => 'films']);

        static::assertSame(
            [false, ['category' => 'films']],
            [$filter->getForm()->isValid(), $filter->getForm()->getFilterSet()->getInput()],
        );
    }

    #[Test]
    public function setQueryParamsRunsInputFiltersAgainOnEveryCall(): void
    {
        $filter = QueryFilterFactory::make($this->createRecordingAdapter(), [new SearchFilter()]);

        $filter->setQueryParams(['search' => 'red']);
        $filter->setQueryParams(['search' => '']);

        static::assertSame(['search' => null], $filter->getForm()->getFilterSet()->getInput());
    }

    #[Test]
    public function setQueryParamsStoresFilteredValuesAndDefaults(): void
    {
        $filter = QueryFilterFactory::make($this->createRecordingAdapter(), [
            new SearchFilter(),
            new CategoryFilter(),
            new StatusFilter(),
            new TenantFilter(),
            new ActiveOnlyFilter(),
        ]);

        $filter->setQueryParams(['search' => '', 'category' => 'books', 'tenant' => '9', 'page' => '2']);

        static::assertEqualsCanonicalizing(
            ['search' => null, 'category' => 'books', 'status' => 'live', 'tenant' => '9'],
            $filter->getForm()->getFilterSet()->getInput(),
        );
    }

    #[Test]
    public function setTableNameOverridesTheTablesName(): void
    {
        $filter = new QueryFilter();
        $filter->setQueryFilterTable(new ProductTable($this->createRecordingAdapter()))->setTableName('p');

        static::assertSame('p', $filter->getTableName());
    }

    #[Test]
    public function settersAreFluent(): void
    {
        $filter = new QueryFilter();
        $table  = new ProductTable($this->createRecordingAdapter());

        static::assertSame(
            [$filter, $filter, $filter],
            [
                $filter->setForm(QueryFilterFactory::makeForm()),
                $filter->setQueryFilterTable($table),
                $filter->setTableName('p'),
            ],
        );
    }

    #[Test]
    public function stateFlagsStartFalse(): void
    {
        $filter = new QueryFilter();

        static::assertSame([false, false], [$filter->isValidated(), $filter->isSubmitted()]);
    }
}

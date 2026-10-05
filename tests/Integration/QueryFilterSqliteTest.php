<?php

declare(strict_types=1);

namespace ContenirTest\Db\QueryFilter\Integration;

use ContenirTest\Db\QueryFilter\TestAsset\Factory\QueryFilterFactory;
use ContenirTest\Db\QueryFilter\TestAsset\Filter\ActiveOnlyFilter;
use ContenirTest\Db\QueryFilter\TestAsset\Filter\CategoryFilter;
use ContenirTest\Db\QueryFilter\TestAsset\Filter\SearchFilter;
use ContenirTest\Db\QueryFilter\TestAsset\Filter\StatusFilter;
use ContenirTest\Db\QueryFilter\Trait\SqliteAdapterTrait;
use Laminas\Paginator\Paginator;
use Override;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function array_column;
use function count;
use function iterator_to_array;

/**
 * Filtered pagination against a real (in-memory SQLite) database.
 */
#[Group('integration')]
final class QueryFilterSqliteTest extends TestCase
{
    use SqliteAdapterTrait;

    /**
     * @return array<string, array{array<string, mixed>, list<string>}>
     */
    public static function requestProvider(): array
    {
        return [
            'defaults only'            => [[], ['Red Book', 'Red Record']],
            'search'                   => [['search' => 'Book'], ['Red Book']],
            'category'                 => [['category' => 'music'], ['Red Record']],
            'status overrides default' => [['status' => 'draft'], ['Blue Book']],
            'empty search ignored'     => [['search' => ''], ['Red Book', 'Red Record']],
            'no match'                 => [['search' => 'Green'], []],
        ];
    }

    #[Test]
    public function countingFirstDoesNotBreakThePageQuery(): void
    {
        $queryFilter = QueryFilterFactory::make($this->adapter, [new StatusFilter()]);
        $queryFilter->setQueryParams(['status' => 'draft']);
        $pages = $queryFilter->getPagingResultSet();

        static::assertSame([1, ['Blue Book']], [$pages->count(), array_column($pages->getItems(0, 10), 'name')]);
    }

    /**
     * @param array<string, mixed> $params
     * @param list<string>         $expectedNames
     */
    #[Test]
    #[DataProvider('requestProvider')]
    public function pagingResultSetReturnsTheFilteredRows(array $params, array $expectedNames): void
    {
        $queryFilter = QueryFilterFactory::make($this->adapter, [
            new SearchFilter(),
            new CategoryFilter(),
            new StatusFilter(),
            new ActiveOnlyFilter(),
        ]);
        $queryFilter->setQueryParams($params);

        $paginator = new Paginator($queryFilter->getPagingResultSet());

        static::assertSame(
            [$expectedNames, count($expectedNames)],
            [array_column(iterator_to_array($paginator->getCurrentItems()), 'name'), $paginator->getTotalItemCount()],
        );
    }

    #[Override]
    protected function setUp(): void
    {
        $this->setUpSqliteAdapter();
    }
}

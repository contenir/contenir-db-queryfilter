<?php

declare(strict_types=1);

namespace ContenirTest\Db\QueryFilter\Unit;

use Contenir\Db\QueryFilter\FilterSet;
use ContenirTest\Db\QueryFilter\TestAsset\Filter\ActiveOnlyFilter;
use ContenirTest\Db\QueryFilter\TestAsset\Filter\CategoryFilter;
use ContenirTest\Db\QueryFilter\TestAsset\Filter\SearchFilter;
use ContenirTest\Db\QueryFilter\TestAsset\Filter\UnnamedFilter;
use InvalidArgumentException;
use PhpDb\Sql\Select;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use stdClass;

use function array_keys;
use function array_map;

#[Group('unit')]
final class FilterSetTest extends TestCase
{
    /**
     * @return array<string, array{string, bool}>
     */
    public static function hasFilterProvider(): array
    {
        return [
            'present' => ['category', true],
            'absent'  => ['colour', false],
        ];
    }

    /**
     * @return array<string, array{string}>
     */
    public static function nonFilterClassProvider(): array
    {
        return [
            'class that is not a filter' => [stdClass::class],
            'class that does not exist'  => ['NoSuchFilterClass'],
        ];
    }

    /**
     * @return list<string|null>
     */
    private static function params(FilterSet $set): array
    {
        return array_map(static fn($filter): ?string => $filter->getFilterParam(), $set->getFilters());
    }

    #[Test]
    public function addedFilterReadsItsValueFromTheSet(): void
    {
        $filter = new SearchFilter();
        $set    = new FilterSet();
        $set->addFilter($filter)->setInput(['search' => 'blue']);

        static::assertSame('blue', $filter->getFilterValue());
    }

    #[Test]
    public function addFilterInstantiatesAFilterClassName(): void
    {
        $set = new FilterSet();
        $set->addFilter(CategoryFilter::class);

        static::assertInstanceOf(CategoryFilter::class, $set->getFilter('category'));
    }

    #[Test]
    #[DataProvider('nonFilterClassProvider')]
    public function addFilterRejectsAClassNameThatIsNotAFilter(string $className): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Filter class \"{$className}\" must extend");

        (new FilterSet())->addFilter($className);
    }

    #[Test]
    public function addFiltersKeepsTheGivenOrder(): void
    {
        $set = new FilterSet();
        $set->addFilters([new CategoryFilter(), SearchFilter::class]);

        static::assertSame(['category', 'search'], self::params($set));
    }

    #[Test]
    public function applyFiltersAppliesEveryFilterToTheGivenQuery(): void
    {
        $set    = new FilterSet([new SearchFilter(), new ActiveOnlyFilter()], ['search' => 'red']);
        $select = new Select('products');

        static::assertSame($select, $set->applyFilters($select));
        static::assertCount(2, $select->where);
    }

    #[Test]
    public function clearRemovesAllFilters(): void
    {
        $set = new FilterSet([new SearchFilter(), new CategoryFilter()]);

        static::assertSame([], $set->clear()->getFilters());
    }

    #[Test]
    public function constructorAddsFiltersAndStoresInput(): void
    {
        $set = new FilterSet([new SearchFilter()], ['search' => 'red']);

        static::assertSame(['search'], self::params($set));
        static::assertSame(['search' => 'red'], $set->getInput());
    }

    #[Test]
    public function deprecatedFilterMethodAppliesFilters(): void
    {
        $set    = new FilterSet([new ActiveOnlyFilter()]);
        $select = new Select('products');

        static::assertSame($select, $set->filter($select));
        static::assertCount(1, $select->where);
    }

    #[Test]
    public function getFilterReturnsNullForAnUnknownParam(): void
    {
        static::assertNull((new FilterSet([new SearchFilter()]))->getFilter('category'));
    }

    #[Test]
    public function getFilterReturnsTheFilterForAParam(): void
    {
        $category = new CategoryFilter();
        $set      = new FilterSet([new SearchFilter(), $category]);

        static::assertSame($category, $set->getFilter('category'));
    }

    #[Test]
    #[DataProvider('hasFilterProvider')]
    public function hasFilterReportsWhetherAParamIsPresent(string $param, bool $expected): void
    {
        $set = new FilterSet([new ActiveOnlyFilter(), new SearchFilter(), new CategoryFilter()]);

        static::assertSame($expected, $set->hasFilter($param));
    }

    #[Test]
    public function lookupsFailOnAFilterWithoutAParamName(): void
    {
        $set = new FilterSet([new UnnamedFilter()]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No param has been named for the filter ' . UnnamedFilter::class);

        $set->hasFilter('search');
    }

    #[Test]
    public function removeFilterRemovesOnlyTheMatchingFilterAndReindexes(): void
    {
        $set = new FilterSet([new SearchFilter(), new CategoryFilter(), new ActiveOnlyFilter()]);
        $set->removeFilter('search');

        static::assertSame([0, 1], array_keys($set->getFilters()));
        static::assertSame(['category', null], self::params($set));
    }
}

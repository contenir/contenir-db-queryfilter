<?php

/**
 * @see       https://github.com/contenir/contenir-db-queryfilter for the canonical source repository
 */

declare(strict_types=1);

namespace ContenirTest\Db\QueryFilter;

use Contenir\Db\QueryFilter\FilterSet;
use ContenirTest\Db\QueryFilter\TestAsset\AnotherTestFilterStub;
use ContenirTest\Db\QueryFilter\TestAsset\TestFilterStub;
use ContenirTest\Db\QueryFilter\TestAsset\TrackingFilterStub;
use Laminas\Db\Sql\Select;
use PHPUnit\Framework\TestCase;

/**
 * Tests for FilterSet class.
 */
class FilterSetTest extends TestCase
{
    /**
     * Test that FilterSet can be instantiated.
     */
    public function testCanCreateFilterSet(): void
    {
        $filterSet = new FilterSet();
        $this->assertInstanceOf(FilterSet::class, $filterSet);
    }

    /**
     * Test that FilterSet can be created with an empty filters array.
     */
    public function testCanCreateFilterSetWithFilters(): void
    {
        $filterSet = new FilterSet([]);
        $this->assertInstanceOf(FilterSet::class, $filterSet);
        $this->assertIsArray($filterSet->getFilters());
    }

    /**
     * Test that getFilters returns an array.
     */
    public function testGetFiltersReturnsArray(): void
    {
        $filterSet = new FilterSet();
        $this->assertIsArray($filterSet->getFilters());
        $this->assertEmpty($filterSet->getFilters());
    }

    /**
     * Test setting and getting input.
     */
    public function testCanSetAndGetInput(): void
    {
        $filterSet = new FilterSet();
        $input     = ['search' => 'test', 'category' => 'books'];

        $filterSet->setInput($input);

        $this->assertEquals($input, $filterSet->getInput());
    }

    /**
     * Test adding a filter instance.
     */
    public function testAddFilterWithInstance(): void
    {
        $filterSet = new FilterSet();
        $filter    = new TestFilterStub();

        $result = $filterSet->addFilter($filter);

        $this->assertSame($filterSet, $result);
        $this->assertCount(1, $filterSet->getFilters());
        $this->assertSame($filter, $filterSet->getFilters()[0]);
    }

    /**
     * Test adding a filter by class name.
     */
    public function testAddFilterWithClassName(): void
    {
        $filterSet = new FilterSet();

        $result = $filterSet->addFilter(TestFilterStub::class);

        $this->assertSame($filterSet, $result);
        $this->assertCount(1, $filterSet->getFilters());
        $this->assertInstanceOf(TestFilterStub::class, $filterSet->getFilters()[0]);
    }

    /**
     * Test adding multiple filters at once.
     */
    public function testAddFiltersWithMultiple(): void
    {
        $filter1   = new TestFilterStub();
        $filter2   = new AnotherTestFilterStub();
        $filterSet = new FilterSet();

        $result = $filterSet->addFilters([$filter1, $filter2]);

        $this->assertSame($filterSet, $result);
        $this->assertCount(2, $filterSet->getFilters());
    }

    /**
     * Test constructor with filters parameter.
     */
    public function testConstructorWithFilters(): void
    {
        $filter1   = new TestFilterStub();
        $filter2   = new AnotherTestFilterStub();
        $filterSet = new FilterSet([$filter1, $filter2]);

        $this->assertCount(2, $filterSet->getFilters());
    }

    /**
     * Test constructor with input parameter.
     */
    public function testConstructorWithInput(): void
    {
        $input     = ['key' => 'value'];
        $filterSet = new FilterSet([], $input);

        $this->assertEquals($input, $filterSet->getInput());
    }

    /**
     * Test hasFilter returns true when filter exists.
     */
    public function testHasFilterReturnsTrueWhenFilterExists(): void
    {
        $filter    = new TestFilterStub();
        $filterSet = new FilterSet([$filter]);

        $this->assertTrue($filterSet->hasFilter('test_param'));
    }

    /**
     * Test hasFilter returns false when filter does not exist.
     */
    public function testHasFilterReturnsFalseWhenFilterDoesNotExist(): void
    {
        $filter    = new TestFilterStub();
        $filterSet = new FilterSet([$filter]);

        $this->assertFalse($filterSet->hasFilter('nonexistent_param'));
    }

    /**
     * Test hasFilter returns false when no filters are present.
     */
    public function testHasFilterReturnsFalseWhenNoFilters(): void
    {
        $filterSet = new FilterSet();

        $this->assertFalse($filterSet->hasFilter('any_param'));
    }

    /**
     * Test hasFilter with multiple filters.
     */
    public function testHasFilterWithMultipleFilters(): void
    {
        $filter1   = new TestFilterStub();
        $filter2   = new AnotherTestFilterStub();
        $filterSet = new FilterSet([$filter1, $filter2]);

        $this->assertTrue($filterSet->hasFilter('test_param'));
        $this->assertTrue($filterSet->hasFilter('another_param'));
        $this->assertFalse($filterSet->hasFilter('nonexistent'));
    }

    /**
     * Test getFilter returns the filter when it exists.
     */
    public function testGetFilterReturnsFilterWhenExists(): void
    {
        $filter    = new TestFilterStub();
        $filterSet = new FilterSet([$filter]);

        $result = $filterSet->getFilter('test_param');

        $this->assertSame($filter, $result);
    }

    /**
     * Test getFilter returns null when filter does not exist.
     */
    public function testGetFilterReturnsNullWhenNotExists(): void
    {
        $filter    = new TestFilterStub();
        $filterSet = new FilterSet([$filter]);

        $result = $filterSet->getFilter('nonexistent_param');

        $this->assertNull($result);
    }

    /**
     * Test getFilter returns null when no filters are present.
     */
    public function testGetFilterReturnsNullWhenNoFilters(): void
    {
        $filterSet = new FilterSet();

        $result = $filterSet->getFilter('any_param');

        $this->assertNull($result);
    }

    /**
     * Test getFilter returns the correct filter from multiple.
     */
    public function testGetFilterReturnsCorrectFilterFromMultiple(): void
    {
        $filter1   = new TestFilterStub();
        $filter2   = new AnotherTestFilterStub();
        $filterSet = new FilterSet([$filter1, $filter2]);

        $this->assertSame($filter1, $filterSet->getFilter('test_param'));
        $this->assertSame($filter2, $filterSet->getFilter('another_param'));
    }

    /**
     * Test removeFilter removes an existing filter.
     */
    public function testRemoveFilterRemovesExistingFilter(): void
    {
        $filter    = new TestFilterStub();
        $filterSet = new FilterSet([$filter]);

        $result = $filterSet->removeFilter('test_param');

        $this->assertSame($filterSet, $result);
        $this->assertCount(0, $filterSet->getFilters());
        $this->assertFalse($filterSet->hasFilter('test_param'));
    }

    /**
     * Test removeFilter does nothing when filter does not exist.
     */
    public function testRemoveFilterDoesNothingWhenNotExists(): void
    {
        $filter    = new TestFilterStub();
        $filterSet = new FilterSet([$filter]);

        $result = $filterSet->removeFilter('nonexistent_param');

        $this->assertSame($filterSet, $result);
        $this->assertCount(1, $filterSet->getFilters());
        $this->assertTrue($filterSet->hasFilter('test_param'));
    }

    /**
     * Test removeFilter only removes the matching filter.
     */
    public function testRemoveFilterRemovesOnlyMatchingFilter(): void
    {
        $filter1   = new TestFilterStub();
        $filter2   = new AnotherTestFilterStub();
        $filterSet = new FilterSet([$filter1, $filter2]);

        $filterSet->removeFilter('test_param');

        $this->assertCount(1, $filterSet->getFilters());
        $this->assertFalse($filterSet->hasFilter('test_param'));
        $this->assertTrue($filterSet->hasFilter('another_param'));
    }

    /**
     * Test removeFilter reindexes the array.
     */
    public function testRemoveFilterReindexesArray(): void
    {
        $filter1   = new TestFilterStub();
        $filter2   = new AnotherTestFilterStub();
        $filterSet = new FilterSet([$filter1, $filter2]);

        $filterSet->removeFilter('test_param');

        $filters = $filterSet->getFilters();
        $this->assertArrayHasKey(0, $filters);
        $this->assertArrayNotHasKey(1, $filters);
    }

    /**
     * Test clear removes all filters.
     */
    public function testClearRemovesAllFilters(): void
    {
        $filter1   = new TestFilterStub();
        $filter2   = new AnotherTestFilterStub();
        $filterSet = new FilterSet([$filter1, $filter2]);

        $result = $filterSet->clear();

        $this->assertSame($filterSet, $result);
        $this->assertCount(0, $filterSet->getFilters());
        $this->assertEmpty($filterSet->getFilters());
    }

    /**
     * Test clear on an empty FilterSet.
     */
    public function testClearOnEmptyFilterSet(): void
    {
        $filterSet = new FilterSet();

        $result = $filterSet->clear();

        $this->assertSame($filterSet, $result);
        $this->assertCount(0, $filterSet->getFilters());
    }

    /**
     * Test clear allows adding filters again.
     */
    public function testClearAllowsAddingFiltersAgain(): void
    {
        $filter1   = new TestFilterStub();
        $filter2   = new AnotherTestFilterStub();
        $filterSet = new FilterSet([$filter1]);

        $filterSet->clear();
        $filterSet->addFilter($filter2);

        $this->assertCount(1, $filterSet->getFilters());
        $this->assertTrue($filterSet->hasFilter('another_param'));
        $this->assertFalse($filterSet->hasFilter('test_param'));
    }

    /**
     * Test applyFilters calls filter() on all filters.
     */
    public function testApplyFiltersCallsFilterOnAllFilters(): void
    {
        $filter1 = new TrackingFilterStub();
        $filter2 = new TrackingFilterStub();
        $filter2->setFilterParamName('tracking_param_2');

        $filterSet = new FilterSet([$filter1, $filter2]);
        $query     = new Select('test_table');

        $filterSet->applyFilters($query);

        $this->assertTrue($filter1->filterCalled);
        $this->assertTrue($filter2->filterCalled);
    }

    /**
     * Test applyFilters returns the Select query.
     */
    public function testApplyFiltersReturnsSelectQuery(): void
    {
        $filter    = new TestFilterStub();
        $filterSet = new FilterSet([$filter]);
        $query     = new Select('test_table');

        $result = $filterSet->applyFilters($query);

        $this->assertSame($query, $result);
    }

    /**
     * Test applyFilters with no filters.
     */
    public function testApplyFiltersWithNoFilters(): void
    {
        $filterSet = new FilterSet();
        $query     = new Select('test_table');

        $result = $filterSet->applyFilters($query);

        $this->assertSame($query, $result);
    }

    /**
     * Test deprecated filter() method calls applyFilters().
     */
    public function testFilterMethodCallsApplyFilters(): void
    {
        $filter    = new TrackingFilterStub();
        $filterSet = new FilterSet([$filter]);
        $query     = new Select('test_table');

        $result = $filterSet->filter($query);

        $this->assertTrue($filter->filterCalled);
        $this->assertSame($query, $result);
    }

    /**
     * Test deprecated filter() method returns the Select query.
     */
    public function testFilterMethodReturnsSelectQuery(): void
    {
        $filterSet = new FilterSet();
        $query     = new Select('test_table');

        $result = $filterSet->filter($query);

        $this->assertSame($query, $result);
    }

    /**
     * Test method chaining with all methods.
     */
    public function testMethodChainingWithAllMethods(): void
    {
        $filter1 = new TestFilterStub();
        $filter2 = new AnotherTestFilterStub();

        $filterSet = (new FilterSet())
            ->addFilter($filter1)
            ->addFilter($filter2)
            ->setInput(['key' => 'value'])
            ->removeFilter('test_param');

        $this->assertCount(1, $filterSet->getFilters());
        $this->assertTrue($filterSet->hasFilter('another_param'));
        $this->assertEquals(['key' => 'value'], $filterSet->getInput());
    }

    /**
     * Test clear and rebuild FilterSet.
     */
    public function testClearAndRebuild(): void
    {
        $filter1 = new TestFilterStub();
        $filter2 = new AnotherTestFilterStub();

        $filterSet = (new FilterSet([$filter1]))
            ->clear()
            ->addFilter($filter2);

        $this->assertCount(1, $filterSet->getFilters());
        $this->assertFalse($filterSet->hasFilter('test_param'));
        $this->assertTrue($filterSet->hasFilter('another_param'));
    }
}

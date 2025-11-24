<?php

/**
 * @see       https://github.com/contenir/contenir-db-queryfilter for the canonical source repository
 */

declare(strict_types=1);

namespace ContenirTest\Db\QueryFilter;

use Contenir\Db\QueryFilter\Filter\AbstractFilter;
use Contenir\Db\QueryFilter\Filter\AbstractFilterHidden;
use Contenir\Db\QueryFilter\Filter\AbstractFilterImmutable;
use Contenir\Db\QueryFilter\Filter\AbstractFilterRadio;
use Contenir\Db\QueryFilter\Filter\AbstractFilterSelect;
use Contenir\Db\QueryFilter\Filter\AbstractFilterText;
use Contenir\Db\QueryFilter\FilterSet;
use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Sql\Select;
use Laminas\Db\Sql\Where;
use Laminas\Form\Element;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use RuntimeException;

/**
 * Tests for Filter classes.
 */
class FilterTest extends TestCase
{
    private FilterSet $filterSet;

    protected function setUp(): void
    {
        $this->filterSet = new FilterSet();
    }

    /**
     * Create a text filter instance for testing.
     */
    private function createTextFilter(): AbstractFilterText
    {
        return new class extends AbstractFilterText {
            protected ?string $filterParam                = 'search';
            protected ?string $filterLabel                = 'Search';
            protected string|iterable|null $filterDefault = 'default_value';
            protected bool $filterRequired                = false;

            /** @var array<string, mixed>|null */
            protected ?array $filterAttributes = ['class' => 'search-input', 'placeholder' => 'Search...'];

            public function filter(Select $query): void
            {
                $value = $this->getFilterValue();
                if ($value === null || $value === '') {
                    return;
                }

                $where = $this->getWhere($query);
                $where->like('name', '%' . $value . '%');
                $query->where($where);
            }
        };
    }

    /**
     * Create a select filter instance for testing.
     */
    private function createSelectFilter(): AbstractFilterSelect
    {
        return new class extends AbstractFilterSelect {
            protected ?string $filterParam                = 'category';
            protected ?string $filterLabel                = 'Category';
            protected string|iterable|null $filterDefault = null;
            protected bool $filterRequired                = true;

            /** @var array<string, mixed>|null */
            protected ?array $filterAttributes = ['class' => 'category-select'];

            public function filter(Select $query): void
            {
                $value = $this->getFilterValue();
                if ($value === null) {
                    return;
                }

                $where = $this->getWhere($query);
                $where->equalTo('category_id', $value);
                $query->where($where);
            }

            /**
             * @return array<string, string>
             */
            public function getValueOptions(): array
            {
                return [
                    ''  => 'All Categories',
                    '1' => 'Category 1',
                    '2' => 'Category 2',
                ];
            }
        };
    }

    /**
     * Create a radio filter instance for testing.
     */
    private function createRadioFilter(): AbstractFilterRadio
    {
        return new class extends AbstractFilterRadio {
            protected ?string $filterParam                = 'status';
            protected ?string $filterLabel                = 'Status';
            protected string|iterable|null $filterDefault = 'active';
            protected bool $filterRequired                = false;

            /** @var array<string, mixed>|null */
            protected ?array $filterAttributes = ['class' => 'status-radio'];

            public function filter(Select $query): void
            {
                $value = $this->getFilterValue();
                if ($value === null) {
                    return;
                }

                $where = $this->getWhere($query);
                $where->equalTo('status', $value);
                $query->where($where);
            }

            /**
             * @return array<string, string>
             */
            public function getValueOptions(): array
            {
                return [
                    'active'   => 'Active',
                    'inactive' => 'Inactive',
                ];
            }
        };
    }

    /**
     * Create a hidden filter instance for testing.
     */
    private function createHiddenFilter(): AbstractFilterHidden
    {
        return new class extends AbstractFilterHidden {
            protected ?string $filterParam                = 'user_id';
            protected ?string $filterLabel                = null;
            protected string|iterable|null $filterDefault = null;
            protected bool $filterRequired                = false;

            public function filter(Select $query): void
            {
                $value = $this->getFilterValue();
                if ($value === null) {
                    return;
                }

                $where = $this->getWhere($query);
                $where->equalTo('user_id', $value);
                $query->where($where);
            }
        };
    }

    /**
     * Create an immutable filter instance for testing.
     */
    private function createImmutableFilter(): AbstractFilterImmutable
    {
        return new class extends AbstractFilterImmutable {
            protected string|iterable|null $filterDefault = null;

            public function filter(Select $query): void
            {
                $where = $this->getWhere($query);
                $where->equalTo('is_published', 1);
                $query->where($where);
            }
        };
    }

    /**
     * Create a filter without filterParam for testing exceptions.
     */
    private function createNoParamFilter(): AbstractFilter
    {
        return new class extends AbstractFilter {
            public function filter(Select $query): void
            {
                // No-op
            }
        };
    }

    /**
     * Invoke a protected method on an object.
     *
     * @param object       $object     Object instance
     * @param string       $methodName Method name to invoke
     * @param array<mixed> $parameters Parameters to pass
     * @return mixed Method return value
     */
    private function invokeProtectedMethod(object $object, string $methodName, array $parameters = []): mixed
    {
        $reflection = new ReflectionClass($object);
        $method     = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $parameters);
    }

    // -------------------------------------------------------------------------
    // FilterTrait Tests
    // -------------------------------------------------------------------------

    /**
     * Test getFilterParam returns the configured parameter name.
     */
    public function testGetFilterParamReturnsParam(): void
    {
        $filter = $this->createTextFilter();
        $filter->setFilterSet($this->filterSet);

        $this->assertEquals('search', $filter->getFilterParam());
    }

    /**
     * Test getFilterParam throws exception when not set.
     */
    public function testGetFilterParamThrowsExceptionWhenNotSet(): void
    {
        $filter = $this->createNoParamFilter();
        $filter->setFilterSet($this->filterSet);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No param has been named for the filter');

        $filter->getFilterParam();
    }

    /**
     * Test getFilterDefault returns the configured default value.
     */
    public function testGetFilterDefaultReturnsDefault(): void
    {
        $filter = $this->createTextFilter();
        $filter->setFilterSet($this->filterSet);

        $this->assertEquals('default_value', $filter->getFilterDefault());
    }

    /**
     * Test getFilterDefault returns null when not set.
     */
    public function testGetFilterDefaultReturnsNullWhenNotSet(): void
    {
        $filter = $this->createSelectFilter();
        $filter->setFilterSet($this->filterSet);

        $this->assertNull($filter->getFilterDefault());
    }

    /**
     * Test getFilterLabel returns the configured label.
     */
    public function testGetFilterLabelReturnsLabel(): void
    {
        $filter = $this->createTextFilter();
        $filter->setFilterSet($this->filterSet);

        $this->assertEquals('Search', $filter->getFilterLabel());
    }

    /**
     * Test getFilterLabel returns null when not set.
     */
    public function testGetFilterLabelReturnsNullWhenNotSet(): void
    {
        $filter = $this->createHiddenFilter();
        $filter->setFilterSet($this->filterSet);

        $this->assertNull($filter->getFilterLabel());
    }

    /**
     * Test getFilterRequired returns false by default.
     */
    public function testGetFilterRequiredReturnsFalse(): void
    {
        $filter = $this->createTextFilter();
        $filter->setFilterSet($this->filterSet);

        $this->assertFalse($filter->getFilterRequired());
    }

    /**
     * Test getFilterRequired returns true when configured.
     */
    public function testGetFilterRequiredReturnsTrue(): void
    {
        $filter = $this->createSelectFilter();
        $filter->setFilterSet($this->filterSet);

        $this->assertTrue($filter->getFilterRequired());
    }

    /**
     * Test getFilterValue returns input value when available.
     */
    public function testGetFilterValueReturnsInputValue(): void
    {
        $this->filterSet->setInput(['search' => 'test_query']);
        $filter = $this->createTextFilter();
        $filter->setFilterSet($this->filterSet);

        $this->assertEquals('test_query', $filter->getFilterValue());
    }

    /**
     * Test getFilterValue returns default when no input.
     */
    public function testGetFilterValueReturnsDefaultWhenNoInput(): void
    {
        $filter = $this->createTextFilter();
        $filter->setFilterSet($this->filterSet);

        $this->assertEquals('default_value', $filter->getFilterValue());
    }

    /**
     * Test getFilterValue returns null when no input and no default.
     */
    public function testGetFilterValueReturnsNullWhenNoInputAndNoDefault(): void
    {
        $filter = $this->createSelectFilter();
        $filter->setFilterSet($this->filterSet);

        $this->assertNull($filter->getFilterValue());
    }

    // -------------------------------------------------------------------------
    // AbstractFilter Tests
    // -------------------------------------------------------------------------

    /**
     * Test setAdapter returns the filter instance for chaining.
     */
    public function testSetAdapterReturnsInstance(): void
    {
        $filter  = $this->createTextFilter();
        $adapter = $this->createMock(Adapter::class);

        $result = $filter->setAdapter($adapter);

        $this->assertSame($filter, $result);
    }

    /**
     * Test setFilterSet returns the filter instance for chaining.
     */
    public function testSetFilterSetReturnsInstance(): void
    {
        $filter = $this->createTextFilter();

        $result = $filter->setFilterSet($this->filterSet);

        $this->assertSame($filter, $result);
    }

    /**
     * Test getWhere returns a Where instance.
     */
    public function testGetWhereReturnsWhereInstance(): void
    {
        $filter = $this->createTextFilter();
        $filter->setFilterSet($this->filterSet);

        $select = new Select('test_table');
        $where  = $this->invokeProtectedMethod($filter, 'getWhere', [$select]);

        $this->assertInstanceOf(Where::class, $where);
    }

    /**
     * Test hasJoin returns false when no joins exist.
     */
    public function testHasJoinReturnsFalseWhenNoJoins(): void
    {
        $filter = $this->createTextFilter();
        $filter->setFilterSet($this->filterSet);

        $select = new Select('test_table');
        $result = $this->invokeProtectedMethod($filter, 'hasJoin', [$select, 'other_table']);

        $this->assertFalse($result);
    }

    /**
     * Test hasJoin returns true when the specified join exists.
     */
    public function testHasJoinReturnsTrueWhenJoinExists(): void
    {
        $filter = $this->createTextFilter();
        $filter->setFilterSet($this->filterSet);

        $select = new Select('test_table');
        $select->join('other_table', 'test_table.id = other_table.test_id');

        $result = $this->invokeProtectedMethod($filter, 'hasJoin', [$select, 'other_table']);

        $this->assertTrue($result);
    }

    /**
     * Test hasJoin returns false for a different table.
     */
    public function testHasJoinReturnsFalseForDifferentTable(): void
    {
        $filter = $this->createTextFilter();
        $filter->setFilterSet($this->filterSet);

        $select = new Select('test_table');
        $select->join('other_table', 'test_table.id = other_table.test_id');

        $result = $this->invokeProtectedMethod($filter, 'hasJoin', [$select, 'different_table']);

        $this->assertFalse($result);
    }

    // -------------------------------------------------------------------------
    // AbstractFilterText Tests
    // -------------------------------------------------------------------------

    /**
     * Test text filter getElement returns a Text element specification.
     */
    public function testTextFilterGetElementReturnsTextElement(): void
    {
        $filter = $this->createTextFilter();
        $filter->setFilterSet($this->filterSet);

        $element = $filter->getElement();

        $this->assertIsArray($element);
        $this->assertEquals(Element\Text::class, $element['type']);
        $this->assertEquals('search', $element['name']);
        $this->assertEquals('Search', $element['options']['label']);
        $this->assertEquals('search-input', $element['attributes']['class']);
        $this->assertEquals('Search...', $element['attributes']['placeholder']);
    }

    /**
     * Test text filter getInputFilterSpecification returns specification array.
     */
    public function testTextFilterGetInputFilterSpecificationReturnsSpec(): void
    {
        $filter = $this->createTextFilter();
        $filter->setFilterSet($this->filterSet);

        $spec = $filter->getInputFilterSpecification();

        $this->assertIsArray($spec);
        $this->assertFalse($spec['required']);
        $this->assertArrayHasKey('filters', $spec);
        $this->assertNotEmpty($spec['filters']);
    }

    // -------------------------------------------------------------------------
    // AbstractFilterSelect Tests
    // -------------------------------------------------------------------------

    /**
     * Test select filter getElement returns a Select element specification.
     */
    public function testSelectFilterGetElementReturnsSelectElement(): void
    {
        $filter = $this->createSelectFilter();
        $filter->setFilterSet($this->filterSet);

        $element = $filter->getElement();

        $this->assertIsArray($element);
        $this->assertEquals(Element\Select::class, $element['type']);
        $this->assertEquals('category', $element['name']);
        $this->assertEquals('Category', $element['options']['label']);
        $this->assertArrayHasKey('value_options', $element['options']);
        $this->assertEquals('category-select', $element['attributes']['class']);
    }

    /**
     * Test select filter getInputFilterSpecification uses required setting.
     */
    public function testSelectFilterGetInputFilterSpecificationUsesRequired(): void
    {
        $filter = $this->createSelectFilter();
        $filter->setFilterSet($this->filterSet);

        $spec = $filter->getInputFilterSpecification();

        $this->assertIsArray($spec);
        $this->assertTrue($spec['required']);
    }

    /**
     * Test select filter getValueOptions returns options array.
     */
    public function testSelectFilterGetValueOptionsReturnsArray(): void
    {
        $filter = $this->createSelectFilter();
        $filter->setFilterSet($this->filterSet);

        $options = $filter->getValueOptions();

        $this->assertIsArray($options);
        $this->assertArrayHasKey('', $options);
        $this->assertArrayHasKey('1', $options);
        $this->assertArrayHasKey('2', $options);
    }

    // -------------------------------------------------------------------------
    // AbstractFilterRadio Tests
    // -------------------------------------------------------------------------

    /**
     * Test radio filter getElement returns a Radio element specification.
     */
    public function testRadioFilterGetElementReturnsRadioElement(): void
    {
        $filter = $this->createRadioFilter();
        $filter->setFilterSet($this->filterSet);

        $element = $filter->getElement();

        $this->assertIsArray($element);
        $this->assertEquals(Element\Radio::class, $element['type']);
        $this->assertEquals('status', $element['name']);
        $this->assertEquals('Status', $element['options']['label']);
        $this->assertArrayHasKey('value_options', $element['options']);
        $this->assertEquals('status-radio', $element['attributes']['class']);
    }

    /**
     * Test radio filter getValueOptions returns options array.
     */
    public function testRadioFilterGetValueOptionsReturnsArray(): void
    {
        $filter = $this->createRadioFilter();
        $filter->setFilterSet($this->filterSet);

        $options = $filter->getValueOptions();

        $this->assertIsArray($options);
        $this->assertArrayHasKey('active', $options);
        $this->assertArrayHasKey('inactive', $options);
    }

    // -------------------------------------------------------------------------
    // AbstractFilterHidden Tests
    // -------------------------------------------------------------------------

    /**
     * Test hidden filter getElement returns null.
     */
    public function testHiddenFilterGetElementReturnsNull(): void
    {
        $filter = $this->createHiddenFilter();
        $filter->setFilterSet($this->filterSet);

        $this->assertNull($filter->getElement());
    }

    /**
     * Test hidden filter getInputFilterSpecification returns null.
     */
    public function testHiddenFilterGetInputFilterSpecificationReturnsNull(): void
    {
        $filter = $this->createHiddenFilter();
        $filter->setFilterSet($this->filterSet);

        $this->assertNull($filter->getInputFilterSpecification());
    }

    /**
     * Test hidden filter can still apply filter logic.
     */
    public function testHiddenFilterCanStillApplyFilter(): void
    {
        $this->filterSet->setInput(['user_id' => 123]);
        $filter = $this->createHiddenFilter();
        $filter->setFilterSet($this->filterSet);

        $this->assertEquals(123, $filter->getFilterValue());
    }

    // -------------------------------------------------------------------------
    // AbstractFilterImmutable Tests
    // -------------------------------------------------------------------------

    /**
     * Test immutable filter getFilterParam returns null.
     */
    public function testImmutableFilterGetFilterParamReturnsNull(): void
    {
        $filter = $this->createImmutableFilter();
        $filter->setFilterSet($this->filterSet);

        $this->assertNull($filter->getFilterParam());
    }

    /**
     * Test immutable filter getElement returns null.
     */
    public function testImmutableFilterGetElementReturnsNull(): void
    {
        $filter = $this->createImmutableFilter();
        $filter->setFilterSet($this->filterSet);

        $this->assertNull($filter->getElement());
    }

    /**
     * Test immutable filter getInputFilterSpecification returns null.
     */
    public function testImmutableFilterGetInputFilterSpecificationReturnsNull(): void
    {
        $filter = $this->createImmutableFilter();
        $filter->setFilterSet($this->filterSet);

        $this->assertNull($filter->getInputFilterSpecification());
    }

    // -------------------------------------------------------------------------
    // Filter Application Tests
    // -------------------------------------------------------------------------

    /**
     * Test text filter applies where clause to query.
     */
    public function testTextFilterAppliesWhereClause(): void
    {
        $this->filterSet->setInput(['search' => 'test']);
        $filter = $this->createTextFilter();
        $filter->setFilterSet($this->filterSet);

        $select = new Select('items');
        $filter->filter($select);

        $where = $select->where;
        $this->assertInstanceOf(Where::class, $where);
    }

    /**
     * Test select filter applies where clause to query.
     */
    public function testSelectFilterAppliesWhereClause(): void
    {
        $this->filterSet->setInput(['category' => '1']);
        $filter = $this->createSelectFilter();
        $filter->setFilterSet($this->filterSet);

        $select = new Select('items');
        $filter->filter($select);

        $where = $select->where;
        $this->assertInstanceOf(Where::class, $where);
    }

    /**
     * Test immutable filter always applies where clause.
     */
    public function testImmutableFilterAlwaysApplies(): void
    {
        $filter = $this->createImmutableFilter();
        $filter->setFilterSet($this->filterSet);

        $select = new Select('items');
        $filter->filter($select);

        $where = $select->where;
        $this->assertInstanceOf(Where::class, $where);
    }

    /**
     * Test filter attributes are returned correctly.
     */
    public function testFilterAttributesAreReturnedCorrectly(): void
    {
        $filter = $this->createTextFilter();
        $filter->setFilterSet($this->filterSet);

        $element    = $filter->getElement();
        $attributes = $element['attributes'];

        $this->assertIsArray($attributes);
        $this->assertEquals('search-input', $attributes['class']);
        $this->assertEquals('Search...', $attributes['placeholder']);
    }
}

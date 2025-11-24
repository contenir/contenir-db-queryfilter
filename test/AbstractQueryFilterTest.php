<?php

/**
 * @see       https://github.com/contenir/contenir-db-queryfilter for the canonical source repository
 */

declare(strict_types=1);

namespace ContenirTest\Db\QueryFilter;

use Contenir\Db\QueryFilter\AbstractQueryFilter;
use Contenir\Db\QueryFilter\FilterSet;
use Contenir\Db\QueryFilter\Form;
use Contenir\Db\QueryFilter\QueryFilter;
use Contenir\Db\QueryFilter\QueryFilterInterface;
use ContenirTest\Db\QueryFilter\TestAsset\MockQueryFilterTable;
use ContenirTest\Db\QueryFilter\TestAsset\TestableQueryFilter;
use ContenirTest\Db\QueryFilter\TestAsset\TestCategoryFilter;
use ContenirTest\Db\QueryFilter\TestAsset\TestImmutableFilter;
use ContenirTest\Db\QueryFilter\TestAsset\TestTextFilter;
use Error;
use PHPUnit\Framework\TestCase;
use RuntimeException;

use function method_exists;

/**
 * Tests for AbstractQueryFilter class.
 *
 * Tests the core functionality of the abstract query filter including
 * form handling, table management, query parameter processing, and state tracking.
 */
class AbstractQueryFilterTest extends TestCase
{
    private Form $form;
    private FilterSet $filterSet;
    private MockQueryFilterTable $table;
    private QueryFilter $queryFilter;

    /**
     * Set up test fixtures.
     */
    protected function setUp(): void
    {
        $this->filterSet = new FilterSet([
            new TestTextFilter(),
            new TestCategoryFilter(),
        ]);

        $this->form = new Form();
        $this->form->setFilterSet($this->filterSet);
        $this->form->build();

        $this->table = new MockQueryFilterTable('products');

        $this->queryFilter = new QueryFilter();
    }

    // =========================================================================
    // Tests for setForm() and getForm()
    // =========================================================================

    /**
     * Test that setForm returns QueryFilterInterface.
     */
    public function testSetFormReturnsQueryFilterInterface(): void
    {
        $result = $this->queryFilter->setForm($this->form);

        $this->assertInstanceOf(QueryFilterInterface::class, $result);
    }

    /**
     * Test that setForm returns the same instance for fluent interface.
     */
    public function testSetFormReturnsSameInstance(): void
    {
        $result = $this->queryFilter->setForm($this->form);

        $this->assertSame($this->queryFilter, $result);
    }

    /**
     * Test that getForm returns the form that was set.
     */
    public function testGetFormReturnsSetForm(): void
    {
        $this->queryFilter->setForm($this->form);

        $this->assertSame($this->form, $this->queryFilter->getForm());
    }

    /**
     * Test that constructor with form sets the form.
     */
    public function testConstructorWithFormSetsForm(): void
    {
        $queryFilter = new QueryFilter($this->form);

        $this->assertSame($this->form, $queryFilter->getForm());
    }

    /**
     * Test that constructor with null form does not set form.
     */
    public function testConstructorWithNullFormDoesNotSetForm(): void
    {
        $queryFilter = new QueryFilter(null);

        $this->expectException(Error::class);
        $queryFilter->getForm();
    }

    // =========================================================================
    // Tests for setQueryFilterTable() and getQueryFilterTable()
    // =========================================================================

    /**
     * Test that setQueryFilterTable returns QueryFilterInterface.
     */
    public function testSetQueryFilterTableReturnsQueryFilterInterface(): void
    {
        $result = $this->queryFilter->setQueryFilterTable($this->table);

        $this->assertInstanceOf(QueryFilterInterface::class, $result);
    }

    /**
     * Test that setQueryFilterTable returns the same instance.
     */
    public function testSetQueryFilterTableReturnsSameInstance(): void
    {
        $result = $this->queryFilter->setQueryFilterTable($this->table);

        $this->assertSame($this->queryFilter, $result);
    }

    /**
     * Test that getQueryFilterTable returns the table that was set.
     */
    public function testGetQueryFilterTableReturnsSetTable(): void
    {
        $this->queryFilter->setQueryFilterTable($this->table);

        $this->assertSame($this->table, $this->queryFilter->getQueryFilterTable());
    }

    /**
     * Test that setQueryFilterTable also sets the table name.
     */
    public function testSetQueryFilterTableAlsoSetsTableName(): void
    {
        $this->queryFilter->setQueryFilterTable($this->table);

        $this->assertEquals('products', $this->queryFilter->getTableName());
    }

    // =========================================================================
    // Tests for setTableName() and getTableName()
    // =========================================================================

    /**
     * Test that setTableName returns QueryFilterInterface.
     */
    public function testSetTableNameReturnsQueryFilterInterface(): void
    {
        $result = $this->queryFilter->setTableName('test_table');

        $this->assertInstanceOf(QueryFilterInterface::class, $result);
    }

    /**
     * Test that setTableName returns the same instance.
     */
    public function testSetTableNameReturnsSameInstance(): void
    {
        $result = $this->queryFilter->setTableName('test_table');

        $this->assertSame($this->queryFilter, $result);
    }

    /**
     * Test that getTableName returns the table name that was set.
     */
    public function testGetTableNameReturnsSetTableName(): void
    {
        $this->queryFilter->setTableName('my_custom_table');

        $this->assertEquals('my_custom_table', $this->queryFilter->getTableName());
    }

    /**
     * Test that setTableName can override table name from QueryFilterTable.
     */
    public function testSetTableNameOverridesTableFromQueryFilterTable(): void
    {
        $this->queryFilter->setQueryFilterTable($this->table);
        $this->queryFilter->setTableName('overridden_table');

        $this->assertEquals('overridden_table', $this->queryFilter->getTableName());
    }

    // =========================================================================
    // Tests for setQueryParams()
    // =========================================================================

    /**
     * Test setQueryParams with valid parameters.
     */
    public function testSetQueryParamsWithValidParams(): void
    {
        $this->queryFilter
            ->setForm($this->form)
            ->setQueryFilterTable($this->table);

        $params = ['search' => 'test', 'category' => 'books'];

        $this->queryFilter->setQueryParams($params);

        $input = $this->filterSet->getInput();
        $this->assertEquals('test', $input['search']);
        $this->assertEquals('books', $input['category']);
    }

    /**
     * Test setQueryParams with empty parameters uses defaults.
     */
    public function testSetQueryParamsWithEmptyParams(): void
    {
        $this->queryFilter
            ->setForm($this->form)
            ->setQueryFilterTable($this->table);

        $this->queryFilter->setQueryParams([]);

        $input = $this->filterSet->getInput();
        // Should use default values
        $this->assertEquals('', $input['search']);
        $this->assertNull($input['category']);
    }

    /**
     * Test setQueryParams with partial parameters.
     */
    public function testSetQueryParamsWithPartialParams(): void
    {
        $this->queryFilter
            ->setForm($this->form)
            ->setQueryFilterTable($this->table);

        $params = ['search' => 'partial'];

        $this->queryFilter->setQueryParams($params);

        $input = $this->filterSet->getInput();
        $this->assertEquals('partial', $input['search']);
        $this->assertNull($input['category']); // Default value
    }

    /**
     * Test setQueryParams skips immutable filters.
     */
    public function testSetQueryParamsSkipsImmutableFilters(): void
    {
        // Create a FilterSet with an immutable filter
        $filterSet = new FilterSet([
            new TestTextFilter(),
            new TestImmutableFilter(),
        ]);

        $form = new Form();
        $form->setFilterSet($filterSet);
        $form->build();

        $queryFilter = new QueryFilter($form);
        $queryFilter->setQueryFilterTable($this->table);

        // This should not throw - immutable filters have no param
        $queryFilter->setQueryParams(['search' => 'test']);

        $input = $filterSet->getInput();
        $this->assertEquals('test', $input['search']);
    }

    /**
     * Test setQueryParams ignores unknown parameters.
     */
    public function testSetQueryParamsIgnoresUnknownParams(): void
    {
        $this->queryFilter
            ->setForm($this->form)
            ->setQueryFilterTable($this->table);

        $params = [
            'search'   => 'test',
            'unknown'  => 'ignored',
            'category' => 'books',
        ];

        $this->queryFilter->setQueryParams($params);

        $input = $this->filterSet->getInput();
        $this->assertEquals('test', $input['search']);
        $this->assertEquals('books', $input['category']);
        $this->assertArrayNotHasKey('unknown', $input);
    }

    // =========================================================================
    // Tests for isValidated() and isSubmitted()
    // =========================================================================

    /**
     * Test isValidated returns false initially.
     */
    public function testIsValidatedInitiallyFalse(): void
    {
        $this->assertFalse($this->queryFilter->isValidated());
    }

    /**
     * Test isSubmitted returns false initially.
     */
    public function testIsSubmittedInitiallyFalse(): void
    {
        $this->assertFalse($this->queryFilter->isSubmitted());
    }

    /**
     * Test isValidated returns true after setQueryParams.
     */
    public function testIsValidatedTrueAfterSetQueryParams(): void
    {
        $this->queryFilter
            ->setForm($this->form)
            ->setQueryFilterTable($this->table);

        $this->queryFilter->setQueryParams(['search' => 'test']);

        $this->assertTrue($this->queryFilter->isValidated());
    }

    /**
     * Test isSubmitted returns true when params are not empty.
     */
    public function testIsSubmittedTrueWhenParamsNotEmpty(): void
    {
        $this->queryFilter
            ->setForm($this->form)
            ->setQueryFilterTable($this->table);

        $this->queryFilter->setQueryParams(['search' => 'test']);

        $this->assertTrue($this->queryFilter->isSubmitted());
    }

    /**
     * Test isSubmitted returns false when params are empty.
     */
    public function testIsSubmittedFalseWhenParamsEmpty(): void
    {
        $this->queryFilter
            ->setForm($this->form)
            ->setQueryFilterTable($this->table);

        $this->queryFilter->setQueryParams([]);

        $this->assertFalse($this->queryFilter->isSubmitted());
    }

    /**
     * Test isValidated returns true even with empty params.
     */
    public function testIsValidatedTrueEvenWithEmptyParams(): void
    {
        $this->queryFilter
            ->setForm($this->form)
            ->setQueryFilterTable($this->table);

        $this->queryFilter->setQueryParams([]);

        $this->assertTrue($this->queryFilter->isValidated());
    }

    // =========================================================================
    // Tests for validateState()
    // =========================================================================

    /**
     * Test validateState throws when form not set.
     */
    public function testValidateStateThrowsWhenFormNotSet(): void
    {
        $queryFilter = new TestableQueryFilter();
        $queryFilter->setQueryFilterTable($this->table);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Form must be set before calling this method');

        $queryFilter->callValidateState();
    }

    /**
     * Test validateState throws when table not set.
     */
    public function testValidateStateThrowsWhenTableNotSet(): void
    {
        $queryFilter = new TestableQueryFilter();
        $queryFilter->setForm($this->form);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('QueryFilterTable must be set before calling this method');

        $queryFilter->callValidateState();
    }

    /**
     * Test validateState does not throw when both form and table are set.
     */
    public function testValidateStateDoesNotThrowWhenBothSet(): void
    {
        $queryFilter = new TestableQueryFilter();
        $queryFilter->setForm($this->form);
        $queryFilter->setQueryFilterTable($this->table);

        // Should not throw
        $queryFilter->callValidateState();

        $this->assertTrue(true); // If we get here, test passed
    }

    /**
     * Test setQueryParams throws when form not set.
     */
    public function testSetQueryParamsThrowsWhenFormNotSet(): void
    {
        $this->queryFilter->setQueryFilterTable($this->table);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Form must be set');

        $this->queryFilter->setQueryParams(['search' => 'test']);
    }

    /**
     * Test setQueryParams throws when table not set.
     */
    public function testSetQueryParamsThrowsWhenTableNotSet(): void
    {
        $this->queryFilter->setForm($this->form);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('QueryFilterTable must be set');

        $this->queryFilter->setQueryParams(['search' => 'test']);
    }

    // =========================================================================
    // Tests for fluent interface
    // =========================================================================

    /**
     * Test fluent interface method chaining.
     */
    public function testFluentInterfaceChaining(): void
    {
        $result = $this->queryFilter
            ->setForm($this->form)
            ->setQueryFilterTable($this->table)
            ->setTableName('chained_table');

        $this->assertInstanceOf(QueryFilterInterface::class, $result);
        $this->assertSame($this->queryFilter, $result);
    }

    /**
     * Test all setters return QueryFilterInterface.
     */
    public function testAllSettersReturnQueryFilterInterface(): void
    {
        $this->assertInstanceOf(
            QueryFilterInterface::class,
            $this->queryFilter->setForm($this->form)
        );

        $this->assertInstanceOf(
            QueryFilterInterface::class,
            $this->queryFilter->setQueryFilterTable($this->table)
        );

        $this->assertInstanceOf(
            QueryFilterInterface::class,
            $this->queryFilter->setTableName('test')
        );
    }

    // =========================================================================
    // Tests for onBeforeFilter() and onAfterFilter() hooks
    // =========================================================================

    /**
     * Test hooks are callable via subclass.
     *
     * Note: We cannot directly test getPagingResultSet() without a database
     * connection, but we can verify the hooks exist and are properly typed.
     */
    public function testHooksAreCallableViaSubclass(): void
    {
        $queryFilter = new TestableQueryFilter();
        $queryFilter->setForm($this->form);
        $queryFilter->setQueryFilterTable($this->table);

        // Initially hooks not called
        $this->assertFalse($queryFilter->onBeforeFilterCalled);
        $this->assertFalse($queryFilter->onAfterFilterCalled);
        $this->assertNull($queryFilter->beforeFilterSelect);
        $this->assertNull($queryFilter->afterFilterSelect);
    }

    /**
     * Test subclass can override hooks.
     */
    public function testSubclassCanOverrideHooks(): void
    {
        $queryFilter = new TestableQueryFilter();

        // Verify the subclass has the hook methods
        $this->assertTrue(method_exists($queryFilter, 'onBeforeFilter'));
        $this->assertTrue(method_exists($queryFilter, 'onAfterFilter'));
    }

    // =========================================================================
    // Tests for QueryFilter concrete class
    // =========================================================================

    /**
     * Test QueryFilter extends AbstractQueryFilter.
     */
    public function testQueryFilterExtendsAbstractQueryFilter(): void
    {
        $queryFilter = new QueryFilter();

        $this->assertInstanceOf(AbstractQueryFilter::class, $queryFilter);
    }

    /**
     * Test QueryFilter implements QueryFilterInterface.
     */
    public function testQueryFilterImplementsQueryFilterInterface(): void
    {
        $queryFilter = new QueryFilter();

        $this->assertInstanceOf(QueryFilterInterface::class, $queryFilter);
    }

    // =========================================================================
    // Edge case tests
    // =========================================================================

    /**
     * Test multiple setQueryParams calls.
     */
    public function testMultipleSetQueryParamsCalls(): void
    {
        $this->queryFilter
            ->setForm($this->form)
            ->setQueryFilterTable($this->table);

        $this->queryFilter->setQueryParams(['search' => 'first']);
        $this->queryFilter->setQueryParams(['search' => 'second', 'category' => 'updated']);

        $input = $this->filterSet->getInput();
        $this->assertEquals('second', $input['search']);
        $this->assertEquals('updated', $input['category']);
    }

    /**
     * Test form with empty FilterSet.
     */
    public function testFormWithEmptyFilterSet(): void
    {
        $emptyFilterSet = new FilterSet();
        $form           = new Form();
        $form->setFilterSet($emptyFilterSet);
        $form->build();

        $queryFilter = new QueryFilter($form);
        $queryFilter->setQueryFilterTable($this->table);

        // Should not throw with empty FilterSet
        $queryFilter->setQueryParams(['anything' => 'value']);

        $this->assertTrue($queryFilter->isValidated());
        $this->assertTrue($queryFilter->isSubmitted());
    }

    /**
     * Test different table instances.
     */
    public function testDifferentTableInstances(): void
    {
        $table1 = new MockQueryFilterTable('table_one');
        $table2 = new MockQueryFilterTable('table_two');

        $this->queryFilter->setQueryFilterTable($table1);
        $this->assertEquals('table_one', $this->queryFilter->getTableName());

        $this->queryFilter->setQueryFilterTable($table2);
        $this->assertEquals('table_two', $this->queryFilter->getTableName());
        $this->assertSame($table2, $this->queryFilter->getQueryFilterTable());
    }
}

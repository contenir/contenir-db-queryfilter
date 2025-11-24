<?php

declare(strict_types=1);

namespace ContenirTest\Db\QueryFilter;

use Contenir\Db\QueryFilter\FilterSet;
use PHPUnit\Framework\TestCase;

class FilterSetTest extends TestCase
{
    public function testCanCreateFilterSet(): void
    {
        $filterSet = new FilterSet();
        $this->assertInstanceOf(FilterSet::class, $filterSet);
    }

    public function testCanCreateFilterSetWithFilters(): void
    {
        $filterSet = new FilterSet([]);
        $this->assertInstanceOf(FilterSet::class, $filterSet);
        $this->assertIsArray($filterSet->getFilters());
    }

    public function testGetFiltersReturnsArray(): void
    {
        $filterSet = new FilterSet();
        $this->assertIsArray($filterSet->getFilters());
        $this->assertEmpty($filterSet->getFilters());
    }

    public function testCanSetAndGetInput(): void
    {
        $filterSet = new FilterSet();
        $input     = ['search' => 'test', 'category' => 'books'];

        $filterSet->setInput($input);

        $this->assertEquals($input, $filterSet->getInput());
    }
}

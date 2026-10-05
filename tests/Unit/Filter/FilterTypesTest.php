<?php

declare(strict_types=1);

namespace ContenirTest\Db\QueryFilter\Unit\Filter;

use ContenirTest\Db\QueryFilter\TestAsset\Filter\ActiveOnlyFilter;
use ContenirTest\Db\QueryFilter\TestAsset\Filter\CategoryFilter;
use ContenirTest\Db\QueryFilter\TestAsset\Filter\SearchFilter;
use ContenirTest\Db\QueryFilter\TestAsset\Filter\StatusFilter;
use ContenirTest\Db\QueryFilter\TestAsset\Filter\TenantFilter;
use Laminas\Filter\ToNull;
use Laminas\Form\Element\Radio;
use Laminas\Form\Element\Select;
use Laminas\Form\Element\Text;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Element and input specifications produced by each abstract filter type.
 */
#[Group('unit')]
final class FilterTypesTest extends TestCase
{
    #[Test]
    public function hiddenFilterHasNoElementOrInputSpecification(): void
    {
        $filter = new TenantFilter();

        static::assertSame(
            ['tenant', null, null],
            [$filter->getFilterParam(), $filter->getElement(), $filter->getInputFilterSpecification()],
        );
    }

    #[Test]
    public function immutableFilterHasNoParamElementOrInputSpecification(): void
    {
        $filter = new ActiveOnlyFilter();

        static::assertSame(
            [null, null, null],
            [$filter->getFilterParam(), $filter->getElement(), $filter->getInputFilterSpecification()],
        );
    }

    #[Test]
    public function radioFilterDescribesARadioElementWithItsOptions(): void
    {
        static::assertSame(
            [
                'type'       => Radio::class,
                'name'       => 'status',
                'options'    => ['label' => 'Status', 'value_options' => ['live' => 'Live', 'draft' => 'Draft']],
                'attributes' => [],
            ],
            (new StatusFilter())->getElement(),
        );
    }

    #[Test]
    public function selectFilterDescribesASelectElementWithItsOptions(): void
    {
        static::assertSame(
            [
                'type'       => Select::class,
                'name'       => 'category',
                'options'    => ['label' => 'Category', 'value_options' => ['books' => 'Books', 'music' => 'Music']],
                'attributes' => [],
            ],
            (new CategoryFilter())->getElement(),
        );
    }

    #[Test]
    public function selectFilterInputSpecificationFollowsTheRequiredFlag(): void
    {
        static::assertSame(
            [
                ['required' => false, 'filters' => [['name' => ToNull::class]]],
                ['required' => true, 'filters' => [['name' => ToNull::class]]],
            ],
            [
                (new CategoryFilter())->getInputFilterSpecification(),
                (new StatusFilter())->getInputFilterSpecification(),
            ],
        );
    }

    #[Test]
    public function textFilterDescribesATextElement(): void
    {
        static::assertSame(
            [
                'type'       => Text::class,
                'name'       => 'search',
                'options'    => ['label' => 'Search'],
                'attributes' => ['placeholder' => 'Find a product'],
            ],
            (new SearchFilter())->getElement(),
        );
    }

    #[Test]
    public function textFilterIsOptionalAndNullsEmptyInput(): void
    {
        static::assertSame(
            ['required' => false, 'filters' => [['name' => ToNull::class]]],
            (new SearchFilter())->getInputFilterSpecification(),
        );
    }
}

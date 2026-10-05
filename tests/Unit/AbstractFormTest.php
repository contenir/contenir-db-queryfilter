<?php

declare(strict_types=1);

namespace ContenirTest\Db\QueryFilter\Unit;

use Contenir\Db\QueryFilter\FilterSet;
use Contenir\Db\QueryFilter\Form;
use ContenirTest\Db\QueryFilter\TestAsset\Factory\QueryFilterFactory;
use ContenirTest\Db\QueryFilter\TestAsset\Filter\ActiveOnlyFilter;
use ContenirTest\Db\QueryFilter\TestAsset\Filter\BlankSpecFilter;
use ContenirTest\Db\QueryFilter\TestAsset\Filter\CategoryFilter;
use ContenirTest\Db\QueryFilter\TestAsset\Filter\SearchFilter;
use ContenirTest\Db\QueryFilter\TestAsset\Filter\TenantFilter;
use ContenirTest\Db\QueryFilter\TestAsset\Filter\UnnamedFilter;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;

use function array_keys;
use function array_map;
use function array_values;

#[Group('unit')]
final class AbstractFormTest extends TestCase
{
    #[Test]
    public function buildAddsAnElementForEachFilterThatDescribesOne(): void
    {
        $form = QueryFilterFactory::makeForm(
            new SearchFilter(),
            new TenantFilter(),
            new ActiveOnlyFilter(),
            new BlankSpecFilter(),
            new CategoryFilter(),
        );

        static::assertSame(
            ['search', 'category'],
            array_values(array_map(static fn($element): ?string => $element->getName(), $form->getElements())),
        );
    }

    #[Test]
    public function buildCollectsInputSpecificationsByParamName(): void
    {
        $form = QueryFilterFactory::makeForm(
            new SearchFilter(),
            new TenantFilter(),
            new ActiveOnlyFilter(),
            new BlankSpecFilter(),
            new CategoryFilter(),
        );

        static::assertSame(['search', 'category'], array_keys($form->getInputFilterSpecification()));
    }

    #[Test]
    public function buildFailsOnAFilterWithoutAParamName(): void
    {
        $form = (new Form())->setFilterSet(new FilterSet([new UnnamedFilter()]));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No param has been named for the filter');

        $form->build();
    }

    #[Test]
    public function filterSetMustBeSetBeforeUse(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('FilterSet must be set before calling this method. Use setFilterSet() first.');

        (new Form())->build();
    }

    #[Test]
    public function inputSpecificationIsEmptyBeforeBuild(): void
    {
        $form = (new Form())->setFilterSet(new FilterSet([new SearchFilter()]));

        static::assertSame([], $form->getInputFilterSpecification());
    }

    #[Test]
    public function setFilterSetStoresTheSet(): void
    {
        $set = new FilterSet();

        static::assertSame(
            $set,
            (new Form())->setFilterSet($set)
                ->getFilterSet(),
        );
    }
}

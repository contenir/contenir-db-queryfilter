<?php

declare(strict_types=1);

namespace ContenirTest\Db\QueryFilter\Unit\Filter;

use Contenir\Db\QueryFilter\FilterSet;
use ContenirTest\Db\QueryFilter\TestAsset\Filter\ActiveOnlyFilter;
use ContenirTest\Db\QueryFilter\TestAsset\Filter\SearchFilter;
use ContenirTest\Db\QueryFilter\TestAsset\Filter\StatusFilter;
use ContenirTest\Db\QueryFilter\TestAsset\Filter\TagFilter;
use ContenirTest\Db\QueryFilter\TestAsset\Filter\UnnamedFilter;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;

#[Group('unit')]
final class FilterTraitTest extends TestCase
{
    /**
     * @return array<string, array{array<string, mixed>, string|null}>
     */
    public static function valueProvider(): array
    {
        return [
            'input present' => [['status' => 'draft'], 'draft'],
            'input missing' => [[], 'live'],
            'input null'    => [['status' => null], 'live'],
        ];
    }

    #[Test]
    public function accessorsExposeTheFilterConfiguration(): void
    {
        $filter = new StatusFilter();

        static::assertSame(
            ['status', 'live', 'Status', true],
            [
                $filter->getFilterParam(),
                $filter->getFilterDefault(),
                $filter->getFilterLabel(),
                $filter->getFilterRequired(),
            ],
        );
    }

    #[Test]
    public function baseFilterHasNoElementOrInputSpecification(): void
    {
        $filter = new TagFilter();

        static::assertSame([null, null], [$filter->getElement(), $filter->getInputFilterSpecification()]);
    }

    #[Test]
    public function filterParamIsRequiredUnlessImmutable(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No param has been named for the filter ' . UnnamedFilter::class);

        (new UnnamedFilter())->getFilterParam();
    }

    #[Test]
    #[DataProvider('valueProvider')]
    public function filterValueFallsBackToTheDefault(array $input, ?string $expected): void
    {
        $filter = new StatusFilter();
        new FilterSet([$filter], $input);

        static::assertSame($expected, $filter->getFilterValue());
    }

    #[Test]
    public function immutableFilterValueIsAlwaysItsDefault(): void
    {
        $filter = new ActiveOnlyFilter();
        new FilterSet([$filter], ['' => 'from request']);

        static::assertSame('fixed', $filter->getFilterValue());
    }

    #[Test]
    public function unconfiguredFilterHasNoDefaultAndIsOptional(): void
    {
        $filter = new SearchFilter();

        static::assertSame([null, false], [$filter->getFilterDefault(), $filter->getFilterRequired()]);
    }
}

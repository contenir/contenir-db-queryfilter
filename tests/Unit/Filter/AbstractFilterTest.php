<?php

declare(strict_types=1);

namespace ContenirTest\Db\QueryFilter\Unit\Filter;

use Contenir\Db\QueryFilter\FilterSet;
use ContenirTest\Db\QueryFilter\TestAsset\Db\MockPlatform;
use ContenirTest\Db\QueryFilter\TestAsset\Filter\CategoryNameFilter;
use ContenirTest\Db\QueryFilter\TestAsset\Filter\TagFilter;
use ContenirTest\Db\QueryFilter\Trait\RecordingAdapterTrait;
use Laminas\Db\Sql\Select;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * The SQL helpers AbstractFilter gives its subclasses, exercised through
 * filters that use them.
 */
#[Group('unit')]
final class AbstractFilterTest extends TestCase
{
    use RecordingAdapterTrait;

    #[Test]
    public function getSqlBuildsSubSelectsOnTheFilterAdapter(): void
    {
        $filter = new TagFilter();
        new FilterSet([$filter], ['tag' => 'sale']);
        $select = new Select('products');

        $filter->setAdapter($this->createRecordingAdapter())->filter($select);

        static::assertSame(
            'SELECT "products".* FROM "products" WHERE "id" IN (SELECT "product_tag"."product_id" AS "product_id" '
                . 'FROM "product_tag" WHERE "tag" = \'sale\')',
            $select->getSqlString(new MockPlatform()),
        );
    }

    #[Test]
    public function hasJoinLetsAFilterJoinATableOnlyOnce(): void
    {
        $filter = new CategoryNameFilter();
        new FilterSet([$filter], ['category_name' => 'Books']);
        $select = new Select('products');

        $filter->filter($select);
        $filter->filter($select);

        static::assertSame(
            'SELECT "products".* FROM "products" INNER JOIN "category" ON "category"."code" = "products"."category" '
                . 'WHERE "category"."name" = \'Books\' AND "category"."name" = \'Books\'',
            $select->getSqlString(new MockPlatform()),
        );
    }
}

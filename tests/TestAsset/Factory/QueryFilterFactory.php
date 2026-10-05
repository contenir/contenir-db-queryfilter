<?php

declare(strict_types=1);

namespace ContenirTest\Db\QueryFilter\TestAsset\Factory;

use Contenir\Db\QueryFilter\AbstractQueryFilter;
use Contenir\Db\QueryFilter\Filter\AbstractFilter;
use Contenir\Db\QueryFilter\FilterSet;
use Contenir\Db\QueryFilter\Form;
use Contenir\Db\QueryFilter\QueryFilter;
use ContenirTest\Db\QueryFilter\TestAsset\Table\ProductTable;
use Laminas\Db\Adapter\Adapter;

/**
 * Builds fully wired query filters: form built from the filters, product
 * table attached.
 */
final class QueryFilterFactory
{
    /**
     * @template T of AbstractQueryFilter
     *
     * @param class-string<T> $class
     *
     * @return T
     */
    public static function make(
        Adapter $adapter,
        array $filters,
        string $class = QueryFilter::class,
    ): AbstractQueryFilter {
        $queryFilter = new $class(self::makeForm(...$filters));
        $queryFilter->setQueryFilterTable(new ProductTable($adapter));

        return $queryFilter;
    }

    public static function makeForm(AbstractFilter|string ...$filters): Form
    {
        $form = new Form();
        $form->setFilterSet(new FilterSet($filters));
        $form->build();

        return $form;
    }
}

<?php

declare(strict_types=1);

namespace ContenirTest\Db\QueryFilter\TestAsset\Repository;

use Contenir\Db\Model\EntityManager;
use Contenir\Db\Model\Repository;
use Contenir\Db\QueryFilter\QueryFilterTableInterface;
use ContenirTest\Db\QueryFilter\TestAsset\Entity\Product;

/**
 * A contenir-db-model 2 repository: Repository already provides
 * createSelect() and fetch(), so implementing the interface needs no code.
 *
 * @extends Repository<Product>
 */
final class ProductRepository extends Repository implements QueryFilterTableInterface
{
    public function __construct(EntityManager $em)
    {
        parent::__construct($em, Product::class);
    }
}

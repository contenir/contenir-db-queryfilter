<?php

declare(strict_types=1);

namespace ContenirTest\Db\QueryFilter\TestAsset\Entity;

use Contenir\Db\Model\Mapping\Column;
use Contenir\Db\Model\Mapping\Id;
use Contenir\Db\Model\Mapping\Table;

/**
 * contenir-db-model 2 entity over the "products" fixture table.
 */
#[Table('products')]
final class Product
{
    #[Id]
    public int $id;

    #[Column]
    public string $name;

    #[Column]
    public string $category;

    #[Column]
    public string $status;

    #[Column]
    public bool $active;
}

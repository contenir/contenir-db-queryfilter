<?php

declare(strict_types=1);

namespace ContenirTest\Db\QueryFilter\TestAsset\Entity;

use Contenir\Db\Model\Mapping\Column;
use Contenir\Db\Model\Mapping\Id;
use Contenir\Db\Model\Mapping\Table;

/**
 * contenir-db-model 2 entity whose key property is named like its column.
 *
 * @mago-expect lint:property-name The property is snake_case on purpose.
 */
#[Table('articles')]
final class LegacyArticle
{
    public function __construct(
        #[Id]
        #[Column]
        public int $article_id,
    ) {}
}

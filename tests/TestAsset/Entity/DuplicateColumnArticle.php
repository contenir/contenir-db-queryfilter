<?php

declare(strict_types=1);

namespace ContenirTest\Db\QueryFilter\TestAsset\Entity;

use Contenir\Db\Model\Mapping\Column;
use Contenir\Db\Model\Mapping\Id;
use Contenir\Db\Model\Mapping\Table;

/**
 * Carries #[Table] but maps two properties to one column, which
 * contenir-db-model rejects.
 */
#[Table('articles')]
final class DuplicateColumnArticle
{
    public function __construct(
        #[Id]
        #[Column('article_id')]
        public int $articleId,
        #[Column('article_id')]
        public int $legacyId,
    ) {}
}

<?php

declare(strict_types=1);

namespace ContenirTest\Db\QueryFilter\TestAsset\Entity;

use Contenir\Db\Model\Mapping\Column;
use Contenir\Db\Model\Mapping\Id;
use Contenir\Db\Model\Mapping\Table;

/**
 * contenir-db-model 2 entity with a camelCase key property over a snake_case
 * column, kept private as entities may.
 */
#[Table('articles')]
final class Article
{
    /**
     * Carried over from an import; not a mapped column.
     *
     * @mago-expect lint:property-name Named like the column on purpose.
     */
    public ?int $legacy_id = null;

    public function __construct(
        #[Id]
        #[Column('article_id')]
        private int $articleId,
    ) {}

    public function getArticleId(): int
    {
        return $this->articleId;
    }
}

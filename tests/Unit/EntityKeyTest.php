<?php

declare(strict_types=1);

namespace ContenirTest\Db\QueryFilter\Unit;

use Contenir\Db\Model\Exception\MappingException;
use Contenir\Db\QueryFilter\EntityKey;
use ContenirTest\Db\QueryFilter\TestAsset\Entity\Article;
use ContenirTest\Db\QueryFilter\TestAsset\Entity\DuplicateColumnArticle;
use ContenirTest\Db\QueryFilter\TestAsset\Entity\LegacyArticle;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class EntityKeyTest extends TestCase
{
    /**
     * @return array<string, array{object, string, mixed}>
     */
    public static function keyProvider(): array
    {
        $unmapped            = new Article(20);
        $unmapped->legacy_id = 7;

        return [
            'camelCase db-model property'         => [new Article(20), 'article_id', 20],
            'snake_case db-model property'        => [new LegacyArticle(30), 'article_id', 30],
            'column the db-model mapping lacks'   => [$unmapped, 'legacy_id', 7],
            'object cast from an array'           => [(object) ['resource_id' => '40'], 'resource_id', '40'],
            'object cast from an array, null key' => [(object) ['resource_id' => null], 'resource_id', null],
        ];
    }

    #[Test]
    #[DataProvider('keyProvider')]
    public function readsTheKeyOfTheColumn(object $entity, string $column, mixed $expected): void
    {
        static::assertSame($expected, EntityKey::read($entity, $column));
    }

    #[Test]
    public function rejectsAnInvalidDbModelMapping(): void
    {
        $this->expectException(MappingException::class);
        $this->expectExceptionMessage('article_id');

        EntityKey::read(new DuplicateColumnArticle(1, 2), 'article_id');
    }
}

<?php

declare(strict_types=1);

namespace ContenirTest\Db\QueryFilter\Unit\Controller\Plugin;

use ArrayIterator;
use Contenir\Db\QueryFilter\Controller\Plugin\QueryFilterPlugin;
use Contenir\Db\QueryFilter\QueryFilter;
use Laminas\ServiceManager\ServiceLocatorInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use RuntimeException;
use stdClass;

#[Group('unit')]
final class QueryFilterPluginTest extends TestCase
{
    /**
     * @return array<string, array{iterable<string, mixed>, array<string, mixed>|null}>
     */
    public static function optionsProvider(): array
    {
        return [
            'no options'          => [[], null],
            'array options'       => [['per_page' => 20], ['per_page' => 20]],
            'traversable options' => [new ArrayIterator(['per_page' => 20]), ['per_page' => 20]],
        ];
    }

    #[Test]
    public function buildingNeedsALaminasServiceLocator(): void
    {
        $plugin = new QueryFilterPlugin($this->createStub(ContainerInterface::class));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Building query filters needs a ' . ServiceLocatorInterface::class);

        $plugin(QueryFilter::class);
    }

    #[Test]
    public function builtServiceMustBeAQueryFilter(): void
    {
        $container = $this->createStub(ServiceLocatorInterface::class);
        $container->method('build')->willReturn(new stdClass());

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Service "product-filter" must implement');

        (new QueryFilterPlugin($container))('product-filter');
    }

    /**
     * @param iterable<string, mixed>   $options
     * @param array<string, mixed>|null $expected
     */
    #[Test]
    #[DataProvider('optionsProvider')]
    public function invokingWithAClassNameBuildsANewQueryFilter(iterable $options, ?array $expected): void
    {
        $queryFilter = new QueryFilter();
        $container   = $this->createMock(ServiceLocatorInterface::class);
        $container->expects($this->once())
            ->method('build')
            ->with(QueryFilter::class, $expected)
            ->willReturn($queryFilter);

        static::assertSame($queryFilter, (new QueryFilterPlugin($container))(QueryFilter::class, $options));
    }

    #[Test]
    public function invokingWithoutAClassNameReturnsThePlugin(): void
    {
        $plugin = new QueryFilterPlugin($this->createStub(ContainerInterface::class));

        static::assertSame($plugin, $plugin());
    }
}

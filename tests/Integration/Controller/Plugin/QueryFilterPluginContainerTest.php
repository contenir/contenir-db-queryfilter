<?php

declare(strict_types=1);

namespace ContenirTest\Db\QueryFilter\Integration\Controller\Plugin;

use Contenir\Db\QueryFilter\Controller\Plugin\QueryFilterPlugin;
use Contenir\Db\QueryFilter\Module;
use Contenir\Db\QueryFilter\QueryFilter;
use Contenir\Db\QueryFilter\QueryFilterInterface;
use Laminas\Mvc\Controller\PluginManager;
use Laminas\ServiceManager\Factory\InvokableFactory;
use Laminas\ServiceManager\ServiceManager;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * The plugin as laminas-mvc wires it: registered from the module config in a
 * controller plugin manager, building query filters from the application
 * service manager.
 */
#[Group('integration')]
final class QueryFilterPluginContainerTest extends TestCase
{
    #[Test]
    public function moduleConfigRegistersThePluginUnderItsAlias(): void
    {
        static::assertInstanceOf(QueryFilterPlugin::class, $this->plugins()->get('queryFilter'));
    }

    #[Test]
    public function pluginBuildsANewInstanceOnEveryCall(): void
    {
        $plugin = $this->plugins()->get('queryFilter');

        static::assertNotSame($plugin(QueryFilter::class), $plugin(QueryFilter::class));
    }

    #[Test]
    public function pluginBuildsAQueryFilterWithAnInvokableFactory(): void
    {
        $plugin = $this->plugins()->get('queryFilter');

        static::assertInstanceOf(QueryFilter::class, $plugin(QueryFilter::class));
    }

    #[Test]
    public function pluginPassesOptionsToTheFactory(): void
    {
        $received = null;
        $services = new ServiceManager([
            'factories' => [
                'product-filter' => static function ($container, string $name, ?array $options) use (
                    &$received,
                ): QueryFilterInterface {
                    $received = $options;

                    return new QueryFilter();
                },
            ],
        ]);

        $this->plugins($services)->get('queryFilter')('product-filter', ['per_page' => 20]);

        static::assertSame(['per_page' => 20], $received);
    }

    private function plugins(?ServiceManager $services = null): PluginManager
    {
        $services ??= new ServiceManager([
            'factories' => [QueryFilter::class => InvokableFactory::class],
        ]);

        return new PluginManager($services, (new Module())->getConfig()['controller_plugins']);
    }
}

<?php

declare(strict_types=1);

namespace ContenirTest\Db\QueryFilter\Unit;

use Contenir\Db\QueryFilter\ConfigProvider;
use Contenir\Db\QueryFilter\Controller\Plugin\QueryFilterPlugin;
use Contenir\Db\QueryFilter\Controller\Plugin\QueryFilterPluginFactory;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class ConfigProviderTest extends TestCase
{
    #[Test]
    public function invokingReturnsTheDependencyConfigAtTopLevel(): void
    {
        $provider = new ConfigProvider();

        static::assertSame($provider->getDependencyConfig(), $provider());
    }

    #[Test]
    public function registersNoServiceManagerServices(): void
    {
        static::assertSame(
            ['aliases' => [], 'factories' => []],
            (new ConfigProvider())()['service_manager'],
        );
    }

    #[Test]
    public function registersTheControllerPluginUnderBothAliases(): void
    {
        static::assertSame(
            [
                'aliases'   => [
                    'queryFilter' => QueryFilterPlugin::class,
                    'QueryFilter' => QueryFilterPlugin::class,
                ],
                'factories' => [
                    QueryFilterPlugin::class => QueryFilterPluginFactory::class,
                ],
            ],
            (new ConfigProvider())()['controller_plugins'],
        );
    }
}

<?php

declare(strict_types=1);

namespace ContenirTest\Db\QueryFilter\Unit\Controller\Plugin;

use Contenir\Db\QueryFilter\Controller\Plugin\QueryFilterPlugin;
use Contenir\Db\QueryFilter\Controller\Plugin\QueryFilterPluginFactory;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

#[Group('unit')]
final class QueryFilterPluginFactoryTest extends TestCase
{
    #[Test]
    public function createsAPluginForTheContainer(): void
    {
        $plugin = (new QueryFilterPluginFactory())($this->createStub(ContainerInterface::class));

        static::assertInstanceOf(QueryFilterPlugin::class, $plugin);
    }
}

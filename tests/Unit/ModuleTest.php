<?php

declare(strict_types=1);

namespace ContenirTest\Db\QueryFilter\Unit;

use Contenir\Db\QueryFilter\ConfigProvider;
use Contenir\Db\QueryFilter\Module;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class ModuleTest extends TestCase
{
    #[Test]
    public function moduleConfigMatchesTheConfigProvider(): void
    {
        static::assertSame((new ConfigProvider())(), (new Module())->getConfig());
    }
}

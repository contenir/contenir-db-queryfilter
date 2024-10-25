<?php

declare(strict_types=1);

namespace Contenir\Db\QueryFilter;

class Module
{
    public function getConfig(): array
    {
        $provider = new ConfigProvider();

        return $provider->getDependencyConfig();
    }
}

<?php

namespace Contenir\Db\QueryFilter;

class Module
{
    /**
     *
     *
     * @return array
     */
    public function getConfig(): array
    {
        $provider = new ConfigProvider();

        return $provider->getDependencyConfig();
    }
}

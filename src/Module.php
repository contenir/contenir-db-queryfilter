<?php

namespace Contenir\Db\QueryFilter;

class Module
{
    /**
     *
     *
     * @return array
     */
    public function getConfig()
    {
        $provider = new ConfigProvider();

        return $provider->getDependencyConfig();
    }
}

<?php

namespace Contenir\Db\QueryFilter\Controller\Plugin;

use Psr\Container\ContainerInterface;

class QueryFilterPluginFactory
{
    public function __invoke(ContainerInterface $container): QueryFilterPlugin
    {
        return new QueryFilterPlugin($container);
    }
}

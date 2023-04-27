<?php

namespace Contenir\Db\QueryFilter\Controller\Plugin;

use Psr\Container\ContainerInterface;
use Laminas\Mvc\Controller\Plugin\AbstractPlugin;

class QueryFilterPlugin extends AbstractPlugin
{
    protected ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function __invoke($className = null, iterable $options = [])
    {
        if ($className !== null) {
            return $this->container->build($className, $options);
        }

        return $this;
    }
}

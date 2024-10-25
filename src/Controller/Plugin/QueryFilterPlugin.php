<?php

declare(strict_types=1);

namespace Contenir\Db\QueryFilter\Controller\Plugin;

use Laminas\Mvc\Controller\Plugin\AbstractPlugin;
use Psr\Container\ContainerInterface;

class QueryFilterPlugin extends AbstractPlugin
{
    protected ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function __invoke(?string $className, iterable $options = []): self
    {
        if ($className !== null) {
            return $this->container->build($className, $options);
        }

        return $this;
    }
}

<?php

/**
 * @see       https://github.com/contenir/contenir-db-queryfilter for the canonical source repository
 */

declare(strict_types=1);

namespace Contenir\Db\QueryFilter\Controller\Plugin;

use Contenir\Db\QueryFilter\QueryFilterInterface;
use Laminas\Mvc\Controller\Plugin\AbstractPlugin;
use Psr\Container\ContainerInterface;

/**
 * Controller plugin for creating QueryFilter instances.
 *
 * Provides convenient access to QueryFilter functionality from controllers
 * via $this->queryFilter() method.
 */
class QueryFilterPlugin extends AbstractPlugin
{
    protected ContainerInterface $container;

    /**
     * @param ContainerInterface $container Service container for building instances
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * Build a QueryFilter instance, or return the plugin itself when no class
     * name is given (for fluent chaining).
     *
     * @param string|null             $className QueryFilter class name to build
     * @param iterable<string, mixed> $options   Build options
     * @return self|QueryFilterInterface Built query filter, or $this when $className is null
     */
    public function __invoke(?string $className = null, iterable $options = []): self|QueryFilterInterface
    {
        if ($className !== null) {
            return $this->container->build($className, $options);
        }

        return $this;
    }
}

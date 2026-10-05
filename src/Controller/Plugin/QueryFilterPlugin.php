<?php

/**
 * @see       https://github.com/contenir/contenir-db-queryfilter for the canonical source repository
 */

declare(strict_types=1);

namespace Contenir\Db\QueryFilter\Controller\Plugin;

use Contenir\Db\QueryFilter\QueryFilterInterface;
use Laminas\Mvc\Controller\Plugin\AbstractPlugin;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use RuntimeException;

use function get_debug_type;
use function iterator_to_array;
use function sprintf;

/**
 * Controller plugin for creating QueryFilter instances.
 *
 * Provides convenient access to QueryFilter functionality from controllers
 * via $this->queryFilter() method.
 *
 * @api
 */
class QueryFilterPlugin extends AbstractPlugin
{
    /**
     * @param ContainerInterface $container Service container for building instances; building needs a
     *                                      Laminas ServiceLocatorInterface, as laminas-mvc provides
     */
    public function __construct(
        protected ContainerInterface $container,
    ) {}

    /**
     * Build a QueryFilter instance, or return the plugin itself when no class
     * name is given (for fluent chaining).
     *
     * A new instance is built on every call, so options apply per instance.
     * Without options the factory receives null, as for a plain get().
     *
     * @param string|null             $className QueryFilter class name to build
     * @param iterable<string, mixed> $options   Build options
     * @return self|QueryFilterInterface Built query filter, or $this when $className is null
     *
     * @throws RuntimeException If the container cannot build services, or builds something that is not a query filter.
     * @throws ContainerExceptionInterface If the container fails to build the service.
     *
     * @mago-expect analysis:mixed-assignment Container services are untyped; the type is checked here.
     */
    public function __invoke(?string $className = null, iterable $options = []): self|QueryFilterInterface
    {
        if (null === $className) {
            return $this;
        }

        if (! $this->container instanceof ServiceLocatorInterface) {
            throw new RuntimeException(sprintf(
                'Building query filters needs a %s; got %s.',
                ServiceLocatorInterface::class,
                get_debug_type($this->container),
            ));
        }

        $options     = iterator_to_array($options);
        $queryFilter = $this->container->build($className, [] === $options ? null : $options);
        if (! $queryFilter instanceof QueryFilterInterface) {
            throw new RuntimeException(sprintf(
                'Service "%s" must implement %s; got %s.',
                $className,
                QueryFilterInterface::class,
                get_debug_type($queryFilter),
            ));
        }

        return $queryFilter;
    }
}

<?php

/**
 * @see       https://github.com/contenir/contenir-db-queryfilter for the canonical source repository
 */

declare(strict_types=1);

namespace Contenir\Db\QueryFilter\Controller\Plugin;

use Psr\Container\ContainerInterface;

/**
 * Factory for QueryFilterPlugin.
 *
 * Creates QueryFilterPlugin instances with container injection.
 */
class QueryFilterPluginFactory
{
    /**
     * Create QueryFilterPlugin instance.
     *
     * @param ContainerInterface $container Service container
     */
    public function __invoke(ContainerInterface $container): QueryFilterPlugin
    {
        return new QueryFilterPlugin($container);
    }
}

<?php

/**
 * @see       https://github.com/contenir/contenir-db-queryfilter for the canonical source repository
 * @copyright https://github.com/contenir/contenir-db-queryfilter/blob/master/COPYRIGHT.md
 * @license   https://github.com/contenir/contenir-db-queryfilter/blob/master/LICENSE.md New BSD License
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
     * @return QueryFilterPlugin
     */
    public function __invoke(ContainerInterface $container): QueryFilterPlugin
    {
        return new QueryFilterPlugin($container);
    }
}

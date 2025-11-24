<?php

/**
 * @see       https://github.com/contenir/contenir-db-queryfilter for the canonical source repository
 */

declare(strict_types=1);

namespace Contenir\Db\QueryFilter;

/**
 * Laminas MVC module class for QueryFilter.
 *
 * Provides module configuration for Laminas ModuleManager integration.
 */
class Module
{
    /**
     * Return module configuration.
     *
     * @return array<string, mixed>
     */
    public function getConfig(): array
    {
        $provider = new ConfigProvider();

        return $provider->getDependencyConfig();
    }
}

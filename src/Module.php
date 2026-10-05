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
 *
 * @api
 */
class Module
{
    /**
     * Return module configuration.
     *
     * @return array<string, array<string, array<string, string>>>
     */
    public function getConfig(): array
    {
        return (new ConfigProvider())->getDependencyConfig();
    }
}

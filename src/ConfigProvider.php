<?php

/**
 * @see       https://github.com/contenir/contenir-db-queryfilter for the canonical source repository
 */

declare(strict_types=1);

namespace Contenir\Db\QueryFilter;

/**
 * Configuration provider for the QueryFilter module.
 *
 * Provides dependency configuration for controller plugins and service manager.
 *
 * @api
 */
class ConfigProvider
{
    /**
     * Return dependency configuration.
     *
     * Configures controller plugin aliases and factories.
     *
     * @return array<string, array<string, array<string, string>>>
     */
    public function getDependencyConfig(): array
    {
        return [
            'controller_plugins' => [
                'aliases'   => [
                    'queryFilter' => Controller\Plugin\QueryFilterPlugin::class,
                    'QueryFilter' => Controller\Plugin\QueryFilterPlugin::class,
                ],
                'factories' => [
                    Controller\Plugin\QueryFilterPlugin::class => Controller\Plugin\QueryFilterPluginFactory::class,
                ],
            ],
            'service_manager'    => [
                'aliases'   => [],
                'factories' => [],
            ],
        ];
    }

    /**
     * Return configuration for this component.
     *
     * The keys are top-level application config keys: laminas-mvc reads
     * `controller_plugins` from the merged configuration.
     *
     * @return array<string, array<string, array<string, string>>>
     */
    public function __invoke(): array
    {
        return $this->getDependencyConfig();
    }
}

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
 */
class ConfigProvider
{
    /**
     * Return configuration for this component.
     *
     * @return array<string, mixed>
     */
    public function __invoke(): array
    {
        return [
            'dependencies' => $this->getDependencyConfig(),
        ];
    }

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
}

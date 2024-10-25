<?php

declare(strict_types=1);

namespace Contenir\Db\QueryFilter;

class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'dependencies' => $this->getDependencyConfig(),
        ];
    }

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

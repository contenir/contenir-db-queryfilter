<?php

namespace Contenir\Db\QueryFilter;

class ConfigProvider
{
    /**
     *
     * @return array
     */
    public function __invoke()
    {
        return [
            'dependencies' => $this->getDependencyConfig()
        ];
    }

    /**
     *
     * @return array
     */
    public function getDependencyConfig()
    {
        return [
            'controller_plugins' => [
                'aliases' => [
                    'queryFilter' => Controller\Plugin\QueryFilterPlugin::class,
                    'QueryFilter' => Controller\Plugin\QueryFilterPlugin::class
                ],
                'factories' => [
                    Controller\Plugin\QueryFilterPlugin::class => Controller\Plugin\QueryFilterPluginFactory::class,
                ]
            ],
            'service_manager' => [
                'aliases'   => [],
                'factories' => []
            ]
        ];
    }
}

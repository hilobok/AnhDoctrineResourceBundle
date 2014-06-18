<?php

namespace Anh\DoctrineResourceBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class AnhDoctrineResourceExtension extends Extension
{
    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');

        foreach ($config['resources'] as $name => &$resource) {
            $resource['driver'] = isset($resource['driver']) ? $resource['driver'] : $config['default_driver'];
            $resource['manager'] = isset($resource['manager']) ? $resource['manager'] : $config['default_manager'];
        }

        $container->setParameter('anh_doctrine_resource.resources', $config['resources']);
    }
}

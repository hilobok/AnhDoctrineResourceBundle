<?php

namespace Anh\DoctrineResourceBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Reference;

// there is no abstract service doctrine_mongodb.odm.configuration (at least in 1.0.0-BETA10)
class RepositoryFactoryCompilerPass implements CompilerPassInterface
{
    private $configMap = array(
        'doctrine.orm.configuration' => 'anh_doctrine_resource.orm.repository_factory',
        'doctrine_mongodb.odm.configuration' => 'anh_doctrine_resource.mongodb.repository_factory',
        'doctrine_phpcr.odm.configuration' => 'anh_doctrine_resource.phpcr.repository_factory'
    );

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        foreach ($this->configMap as $config => $factory) {
            if ($container->hasDefinition($config)) {
                $definition = $container->getDefinition($config);
                $definition->addMethodCall('setRepositoryFactory', array(
                    new Reference($factory)
                ));
            }
        }
    }
}

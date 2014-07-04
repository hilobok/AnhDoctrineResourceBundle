<?php

namespace Anh\DoctrineResourceBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\Definition;

class ResourceServicesCompilerPass implements CompilerPassInterface
{
    private $managersMap = array(
        'orm' => 'doctrine.entity_managers',
        'mongodb' => 'doctrine_mongodb.odm.document_managers',
        'phpcr' => 'doctrine_phpcr.odm.document_managers'
    );

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $resources = $container->getParameter('anh_doctrine_resource.resources');

        foreach ($resources as $name => $resource) {
            $manager = $this->getObjectManagerService($container, $resource['driver'], $resource['manager']);

            $managerService = sprintf('%s.manager', $name);
            $container->setDefinition(
                $managerService,
                $this->createManagerDefinition($name, $manager)
            );

            $repositoryService = sprintf('%s.repository', $name);
            $container->setDefinition(
                $repositoryService,
                $this->createRepositoryDefinition($managerService)
            );

            $controllerService = sprintf('%s.controller', $name);
            $container->setDefinition(
                $controllerService,
                $this->createControllerDefinition($resource['controller'], $managerService)
            );
        }
    }

    private function getObjectManagerService(ContainerBuilder $container, $driver, $manager)
    {
        if (!$container->hasParameter($this->managersMap[$driver])) {
            throw new \RuntimeException(
                sprintf("There is no managers for '%s' ('%s' not found).", $driver, $this->managersMap[$driver])
            );
        }

        $managers = $container->getParameter($this->managersMap[$driver]);

        if (!isset($managers[$manager])) {
            throw new \RuntimeException(
                sprintf("There is no manager '%s' for '%s'.", $manager, $driver)
            );
        }

        return $managers[$manager];
    }

    private function createManagerDefinition($resourceName, $manager)
    {
        $definition = new Definition();
        $definition->setFactoryService('anh_doctrine_resource.manager_factory');
        $definition->setFactoryMethod('create');
        $definition->addArgument($resourceName);
        $definition->addArgument(new Reference($manager));

        return $definition;
    }

    private function createRepositoryDefinition($managerService)
    {
        $definition = new Definition();
        $definition->setFactoryService($managerService);
        $definition->setFactoryMethod('getRepository');

        return $definition;
    }

    private function createControllerDefinition($controllerClass, $managerService)
    {
        $definition = new Definition($controllerClass);
        $definition->addArgument(new Reference($managerService));
        $definition->addArgument(new Reference('anh_doctrine_resource.controller.options_parser'));
        $definition->addArgument(new Reference('anh_doctrine_resource.controller.redirect_handler'));
        $definition->addMethodCall('setContainer', array(new Reference('service_container')));

        return $definition;
    }
}

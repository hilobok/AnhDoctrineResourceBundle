<?php

namespace Anh\DoctrineResourceBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('anh_doctrine_resource');

        $this->addResourcesSection($rootNode);

        return $treeBuilder;
    }

    /**
     * Adds `resources` section.
     *
     * @param ArrayNodeDefinition $node
     */
    private function addResourcesSection(ArrayNodeDefinition $node)
    {
        $node
            ->children()
                ->scalarNode('default_driver')
                    ->defaultValue('orm')
                    ->validate()
                    ->ifNotInArray(array('orm', 'mongodb', 'phpcr'))
                        ->thenInvalid("Invalid driver '%s'.")
                    ->end()
                ->end()
                ->scalarNode('default_manager')
                    ->defaultValue('default')
                ->end()
                ->arrayNode('resources')
                    ->useAttributeAsKey('name')
                    ->prototype('array')
                        ->children()
                            ->scalarNode('driver')
                                ->validate()
                                ->ifNotInArray(array('orm', 'mongodb', 'phpcr'))
                                    ->thenInvalid("Invalid driver '%s'.")
                                ->end()
                            ->end()
                            ->scalarNode('manager')
                                ->cannotBeEmpty()
                                ->defaultValue('default')
                            ->end()
                            ->scalarNode('model')
                                ->isRequired()
                                ->cannotBeEmpty()
                            ->end()
                            ->scalarNode('repository')
                            ->end()
                            ->scalarNode('controller')
                                ->defaultValue('Anh\DoctrineResourceBundle\Controller\ResourceController')
                            ->end()
                            ->scalarNode('interface')
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;
    }
}

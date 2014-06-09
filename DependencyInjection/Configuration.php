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
                        // ->children()
                            // ->scalarNode('driver')->isRequired()->cannotBeEmpty()->end()
                            // ->scalarNode('templates')->cannotBeEmpty()->end()
                            // ->arrayNode('classes')
                                ->children()
                                    ->scalarNode('model')
                                        ->isRequired()
                                        ->cannotBeEmpty()
                                    ->end()
                                    // ->scalarNode('controller')->defaultValue('Sylius\Bundle\ResourceBundle\Controller\ResourceController')->end()
                                    ->scalarNode('driver')
                                        // ->isRequired()
                                        // ->cannotBeEmpty()
                                        // ->defaultValue('orm')
                                        ->validate()
                                        ->ifNotInArray(array('orm', 'mongodb', 'phpcr'))
                                            ->thenInvalid("Invalid driver '%s'.")
                                        ->end()
                                    ->end()
                                    ->scalarNode('manager')
                                        // ->isRequired()
                                        ->cannotBeEmpty()
                                        ->defaultValue('default')
                                    ->end()
                                    ->scalarNode('repository')->end()
                                    ->scalarNode('interface')->end()
                                ->end()
                            // ->end()
                        // ->end()
                    ->end()
                ->end()
            ->end()
        ;
    }
}

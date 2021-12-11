<?php

declare(strict_types=1);

namespace Umanit\TwigImage\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('umanit_twig_image');

        $treeBuilder->getRootNode()
            ->children()
                ->arrayNode('lazy_load')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('class_selector')
                            ->defaultValue('lazy')
                        ->end()
                        ->scalarNode('placeholder_class_selector')
                            ->defaultValue('lazy-placeholder')
                        ->end()
                        ->scalarNode('blur_class_selector')
                            ->defaultValue('lazy-blur')
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}

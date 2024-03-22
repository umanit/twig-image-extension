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
                ->booleanNode('use_liip_default_image')
                    ->info('Use the default image defined in Liip if no image is given in functions calls')
                    ->defaultFalse()
                ->end()
                ->booleanNode('allow_fallback')
                    ->info('Allow the bundle to serve a fallback image if no file is found at a given path')
                    ->defaultFalse()
                ->end()
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

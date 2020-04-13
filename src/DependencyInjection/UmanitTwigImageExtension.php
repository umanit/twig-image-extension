<?php

declare(strict_types=1);

namespace Umanit\TwigImage\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

/**
 * Class UmanitTwigImageExtension
 */
class UmanitTwigImageExtension extends Extension
{
    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yaml');

        $configuration = new Configuration();
        $config        = $this->processConfiguration($configuration, $configs);
        $definition    = $container->getDefinition('umanit_twig_image.runtime');

        $definition->addMethodCall('setLazyLoadConfiguration', [
            $config['lazy_load']['enabled'],
            $config['lazy_load']['class_selector'],
            $config['lazy_load']['placeholder_class_selector'],
        ]);
    }
}

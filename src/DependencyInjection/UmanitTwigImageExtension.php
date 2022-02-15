<?php

declare(strict_types=1);

namespace Umanit\TwigImage\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class UmanitTwigImageExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yaml');

        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);
        $definition = $container->getDefinition('umanit_twig_image.runtime');

        $definition->addMethodCall('setLazyLoadConfiguration', [
            $config['lazy_load']['class_selector'],
            $config['lazy_load']['placeholder_class_selector'],
            $config['lazy_load']['blur_class_selector'],
        ]);

        if ($config['use_liip_default_image'] && null === $container->getParameter('liip_imagine.default_image')) {
            throw new \LogicException('You must define the parameter "liip_imagine.default_image" if you want to use the "use_liip_default_image" option.');
        }

        $container->setParameter('umanit_twig_image.use_liip_default_image', $config['use_liip_default_image']);
    }
}

<?php

namespace Clifton\ClothesBuilderBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class CliftonClothesBuilderExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');

        $container->setParameter('stripe_api_key', $config['stripe_api_key']);

        foreach ($config['soap_client'] as $key => $value) {
            $container->setParameter('phpforce.soap_client.' . $key, $value);
        }
        if (true == $config['soap_client']['logging']) {
            $builder = $container->getDefinition('phpforce.soap_client.builder');
        }
    }
}

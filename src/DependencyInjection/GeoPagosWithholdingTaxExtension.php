<?php

namespace GeoPagos\WithholdingTaxBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\Yaml\Yaml;

/**
 * This is the class that loads and manages your bundle configuration.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class GeoPagosWithholdingTaxExtension extends Extension implements PrependExtensionInterface
{
    private const EXTENSIONS = [
        'doctrine',
    ];

    /**
     * Allow an extension to prepend configurations for other extensions.
     */
    public function prepend(ContainerBuilder $container)
    {
        foreach (self::EXTENSIONS as $extension) {
            $this->prependExtension($extension, $container);
        }
    }

    private function prependExtension($name, ContainerBuilder $container): void
    {
        $file = __DIR__.'/../Resources/config/packages/'.$name.'.yaml';
        $config = Yaml::parseFile($file)[$name];

        $container->prependExtensionConfig($name, $config);
    }

    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');

        $config = $this->processConfiguration(new Configuration(), $configs);
        $this->setParameters($config, $container);
    }

    private function setParameters(array $configs, ContainerBuilder $container): void
    {
        $createParam = static function ($value, $key) use (&$container) {
            if (!is_array($value)) {
                $container->setParameter($key, $value);
            }
        };

        array_walk_recursive($configs, $createParam);
    }
}

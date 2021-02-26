<?php

namespace GeoPagos\WithholdingTaxBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $rootNode = (new TreeBuilder('geo_pagos_withholding_tax'))
            ->getRootNode()
            ->treatFalseLike(['enabled' => false])
            ->treatTrueLike(['enabled' => true])
            ->children()
            ->arrayNode('withholdingtax')
            ->addDefaultsIfNotSet()
            ->children()
            ->scalarNode('certificate_company_name')->defaultValue('')->end()
            ->scalarNode('certificate_address')->defaultValue('')->end()
            ->scalarNode('certificate_zip_code')->defaultValue('')->end()
            ->scalarNode('certificate_location')->defaultValue('')->end()
            ->scalarNode('certificate_fiscal_id')->defaultValue('')->end()
            ->scalarNode('certificate_should_show_number')->defaultValue(false)->end()
            ->scalarNode('certificate_should_show_sign')->defaultValue(false)->end()
            ->scalarNode('certificate_sign_name')->defaultValue('')->end()
            ->scalarNode('certificate_sign_fiscal_id')->defaultValue('')->end()
            ->scalarNode('withholding_tax_output_folder')->defaultValue('IIBB')->end()
            ->end()
            ->end()
            ->end();

        $this->addDecimalSection($rootNode);

        return $rootNode->end();
    }

    private function addDecimalSection(ArrayNodeDefinition $rootNode)
    {
        $rootNode->children()
            ->arrayNode('decimal')
            ->addDefaultsIfNotSet()
            ->children()
            ->scalarNode('has_decimal')->defaultValue(true)->end()
            ->end()
            ->end()
            ->end();
    }
}

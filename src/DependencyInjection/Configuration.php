<?php

namespace Mopa\Bundle\BarcodeBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('mopa_barcode');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode->children()
                     ->scalarNode('root_dir')->defaultValue('%kernel.project_dir%/public')->end()
                   ->end();

        return $treeBuilder;
    }
}

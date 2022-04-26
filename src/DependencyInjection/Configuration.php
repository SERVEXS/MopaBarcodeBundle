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
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('mopa_barcode');
        $rootNode = method_exists(TreeBuilder::class, 'getRootNode')
            ? $treeBuilder->getRootNode()
            : $treeBuilder->root('mopa_barcode');

        $rootNode->children()
                     ->scalarNode('overlay_images_path')->defaultNull()->end()
                     ->scalarNode('root_dir')->defaultValue('%kernel.root_dir%/../public')->end()
                   ->end();

        return $treeBuilder;
    }
}

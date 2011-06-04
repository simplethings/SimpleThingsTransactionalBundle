<?php

/**
 * SimpleThings TransactionalBundle
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to kontakt@beberlei.de so I can send you a copy immediately.
 */

namespace SimpleThings\TransactionalBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\DefinitionDecorator;

class SimpleThingsTransactionalExtension extends Extension
{
    /**
     * @param array $configs
     * @param ContainerBuilder $builder
     */
    public function load(array $configs, ContainerBuilder $builder)
    {
        $loader = new XmlFileLoader($builder, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.xml');

        if ($builder->hasParameter('doctrine.connections')) {
            foreach ($builder->getParameter('doctrine.connections') AS $alias => $service) {
                $builder->setDefinition(
                    'simple_things_transactional.tx.dbal.'.$alias,
                    new DefinitionDecorator('simple_things_transactional.manager.dbal')
                )->setArguments(array(new Reference($service)));
            }
        }
        
        if ($builder->hasParameter('doctrine.entity_managers')) {
            foreach ($builder->getParameter('doctrine.entity_managers') AS $alias => $service) {
                $builder->setDefinition(
                    'simple_things_transactional.tx.orm.'.$alias,
                    new DefinitionDecorator('simple_things_transactional.manager.orm')
                )->setArguments(array(new Reference('doctrine'), $alias));
            }
        }
        
        if ($builder->hasParameter('doctrine_couchdb.document_managers')) {
            foreach ($builder->getParameter('doctrine_couchdb.document_managers') AS $alias => $service) {
                $builder->setDefinition(
                    'simple_things_transactional.tx.couchdb.'.$alias,
                    new DefinitionDecorator('simple_things_transactional.manager.couchdb')
                )->setArguments(array(new Reference($service)));
            }
        }
        
        if ($builder->hasParameter('doctrine_mongodb.document_managers')) {
            foreach ($builder->getParameter('doctrine_mongodb.document_managers') AS $alias => $service) {
                $builder->setDefinition(
                    'simple_things_transactional.tx.mongodb.'.$alias,
                    new DefinitionDecorator('simple_things_transactional.manager.mongodb')
                )->setArguments(array(new Reference($service)));
            }
        }
    }
}
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
use SimpleThings\TransactionalBundle\Transactions\TransactionDefinition;

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

        $config = array();
        foreach ($configs AS $c) {
            $config = array_merge($config, $c);
        }

        if (isset($config['auto_transactional']) && $config['auto_transactional'] && isset($config['patterns']) && count($config['patterns'])) {
            throw new \InvalidArgumentException("Cannot activate auto_transactional and set patterns at the same time.");
        }

        if (!isset($config['defaults'])) {
            $config['defaults'] = array(
                'pattern' => '.*',
                'propagation' => TransactionDefinition::PROPAGATION_REQUIRED,
                'isolation' => TransactionDefinition::ISOLATION_DEFAULT,
                'noRollbackFor' => array(),
                'methods' => array('POST' => true, 'PUT' => true, 'DELETE' => true, 'PATCH' => true),
            );
        }

        if (isset($config['auto_transactional']) && $config['auto_transactional']) {
            $patterns = array( array_merge($config['defaults'], array('conn' => (array)$config['auto_transactional'])) );
        } else {
            $patterns = array();
            foreach ($config['patterns'] AS $pattern) {
                if (isset($pattern['methods'])) {
                    $pattern['methods'] = array_flip($pattern['methods']);
                }
                $patterns[] = array_merge($config['defaults'], $pattern);
            }
        }

        $def = $builder->getDefinition('simple_things_transactional.transactional_matcher');
        $def->setArguments(array($patterns));

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
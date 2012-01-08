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

namespace SimpleThings\TransactionalBundle\DependencyInjection\CompilerPass;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\DefinitionDecorator;

/**
 * Detects connections and registers the transaction manager services.
 */
class DetectConnectionsPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $builder)
    {
/*        if ($builder->hasParameter('doctrine.connections')) {
            foreach ($builder->getParameter('doctrine.connections') AS $alias => $service) {
                $builder->setDefinition(
                    'simple_things_transactional.tx.dbal.'.$alias,
                    new DefinitionDecorator('simple_things_transactional.manager.dbal')
                )->setArguments(array(new Reference($service)));
            }
        }
*/
        $connectionServices = array();

        if ($builder->hasParameter('doctrine.entity_managers')) {
            foreach ($builder->getParameter('doctrine.entity_managers') AS $alias => $service) {
                $connectionServices[] = 'doctrine.orm.' . $alias . '_entity_manager';
                $builder->setAlias(
                    'simple_things_transactional.connections.orm.' . $alias,
                    'doctrine.orm.' . $alias . '_entity_manager'
                );
                $builder->setDefinition(
                    'simple_things_transactional.tx.orm.'.$alias,
                    new DefinitionDecorator('simple_things_transactional.manager.orm')
                )->setArguments(array(new Reference('service_container')));
            }
        }

        if ($builder->hasParameter('doctrine_couchdb.document_managers')) {
            foreach ($builder->getParameter('doctrine_couchdb.document_managers') AS $alias => $service) {
                $connectionServices[] = 'doctrine_couchdb.odm.' . $alias . '_document_manager';
                $builder->setAlias('simple_things_transactional.connections.couchdb.' . $alias
                $builder->setDefinition(
                    'simple_things_transactional.tx.couchdb.'.$alias,
                    new DefinitionDecorator('simple_things_transactional.manager.object_manager')
                )->setArguments(array(new Reference('service_container')));
            }
        }

        if ($builder->hasParameter('doctrine_mongodb.document_managers')) {
            foreach ($builder->getParameter('doctrine_mongodb.document_managers') AS $alias => $service) {
                $connectionServices[] = 'doctrine_mongodb.odm.' . $alias . '_document_manager';
                $builder->setAlias('simple_things_transactional.connections.mongodb.' . $alias
                $builder->setDefinition(
                    'simple_things_transactional.tx.mongodb.'.$alias,
                    new DefinitionDecorator('simple_things_transactional.manager.object_manager')
                )->setArguments(array(new Reference('service_container')));
            }
        }
        // add tags as well for external resources (Propel, raw-PDO whatever)
        $container->setParameter('simple_things_transactional.connection_services', $connectionServices);
    }
}

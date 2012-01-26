<?php
/**
 * SimpleThings TransactionaBundle
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to kontakt@beberlei.de so I can send you a copy immediately.
 */

namespace SimpleThings\TransactionalBundle\Tests;

use Doctrine\Common\Annotations\AnnotationReader;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Bundle\DoctrineBundle\DependencyInjection\DoctrineExtension;
use Symfony\Component\DependencyInjection\Compiler\ResolveDefinitionTemplatesPass;
use SimpleThings\TransactionalBundle\DependencyInjection\SimpleThingsTransactionalExtension;
use SimpleThings\TransactionalBundle\DependencyInjection\CompilerPass\DetectConnectionPass;

class ContainerTest extends \PHPUnit_Framework_TestCase
{
    public function testContainer()
    {
        $container = $this->createTestContainer();
        $this->assertInstanceOf('SimpleThings\TransactionalBundle\Transactions\Http\HttpTransactionsListener', $container->get('simple_things_transactional.http_transactions_listener'));
        $this->assertInstanceOf('SimpleThings\TransactionalBundle\Transactions\TransactionsRegistry', $container->get('simple_things_transactional.registry'));
        $this->assertInstanceOf('SimpleThings\TransactionalBundle\Doctrine\DBALTransactionProvider', $container->get('simple_things_transactional.tx.dbal.default'));
        $this->assertInstanceOf('SimpleThings\TransactionalBundle\Doctrine\OrmTransactionProvider', $container->get('simple_things_transactional.tx.orm.default'));
    }

    public function createTestContainer()
    {
        $container = new ContainerBuilder(new ParameterBag(array(
            'kernel.debug'       => false,
            'kernel.bundles'     => array(),
            'kernel.cache_dir'   => sys_get_temp_dir(),
            'kernel.environment' => 'test',
            'kernel.root_dir'    => __DIR__
        )));
        $container->set('annotation_reader', new AnnotationReader());
        $loader = new DoctrineExtension();
        $container->registerExtension($loader);
        $loader->load(array(array(
            'dbal' => array(
                'connections' => array(
                    'default' => array(
                        'driver' => 'pdo_mysql',
                        'charset' => 'UTF8',
                        'platform-service' => 'my.platform',
                    )
                ),
                'default_connection' => 'default',
                'types' => array(
                    'test' => 'Symfony\Bundle\DoctrineBundle\Tests\DependencyInjection\TestType',
                ),
            ), 'orm' => array(
                'default_entity_manager' => 'default',
                'entity_managers' => array (
                    'default' => array('auto_mapping' => true)
                )
            ))
        ), $container);

        $container->setDefinition('my.platform', new \Symfony\Component\DependencyInjection\Definition('Doctrine\DBAL\Platforms\MySqlPlatform'));

        $loader = new SimpleThingsTransactionalExtension();
        $container->registerExtension($loader);
        $loader->load(array(array('auto_transactional' => true, 'defaults' => array('conn' => 'orm.default'))), $container);

        $container->getCompilerPassConfig()->setOptimizationPasses(array(
            new DetectConnectionPass(),
            new ResolveDefinitionTemplatesPass()
        ));
        $container->getCompilerPassConfig()->setRemovingPasses(array());
        $container->compile();

        return $container;
    }
}


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

namespace SimpleThings\TransactionalBundle\Tests\Doctrine;

use SimpleThings\TransactionalBundle\Doctrine\OrmTransactionProvider;
use SimpleThings\TransactionalBundle\Transactions\TransactionDefinition;

class OrmTransactionProviderTest extends \PHPUnit_Framework_TestCase
{
    public function testCreateTransaction()
    {
        $container = $this->getMock('Symfony\Component\DependencyInjection\ContainerInterface');
        $container->expects($this->once())
                  ->method('get')
                  ->with($this->equalTo('doctrine.orm.default_entity_manager'))
                  ->will($this->returnValue($this->getMock('Doctrine\ORM\EntityManager', array(), array(), '', false)));

        $provider = new OrmTransactionProvider($container);
        $status = $provider->createTransaction(new TransactionDefinition('orm.default'));

        $this->assertInstanceOf('SimpleThings\TransactionalBundle\Doctrine\OrmTransactionStatus', $status);
    }
}


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

namespace SimpleThingsTransactionalBundle\Tests\Functional;

use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpKernel\HttpKernel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Log\NullLogger;
use SimpleThings\TransactionalBundle\Transactions\Http\HttpTransactionsListener;
use SimpleThings\TransactionalBundle\Transactions\TransactionsRegistry;
use SimpleThings\TransactionalBundle\Transactions\TransactionDefinition;
use SimpleThings\TransactionalBundle\Transactions\Http\TransactionalMatcher;

class TransactionalKernelTest extends \PHPUnit_Framework_TestCase
{
    private $kernel;
    private $logger;

    public function setUp()
    {
        $resolver = $this->getMock('Symfony\Component\HttpKernel\Controller\ControllerResolverInterface');
        $resolver->expects($this->once())->method('getController')->will($this->returnValue(array(new TestController, 'indexAction')));
        $resolver->expects($this->once())->method('getArguments')->will($this->returnValue(array()));

        $txStatus1 =  $this->getMock('SimpleThings\TransactionalBundle\Transactions\TransactionStatus');
        $manager = $this->getMock('SimpleThings\TransactionalBundle\Transactions\TransactionManagerInterface', array(), array(), '', false);
        $manager->expects($this->at(0))->method('getTransaction')->will($this->returnValue($txStatus1));
        $manager->expects($this->at(1))->method('commit');

        $this->logger = new StackLogger;
        $container = $this->getMock('Symfony\Component\DependencyInjection\ContainerInterface');
        $container->expects($this->any())->method('has')->with($this->equalTo('simple_things_transactional.tx.dbal.default'))->will($this->returnValue(true));
        $container->expects($this->any())->method('get')->with($this->equalTo('simple_things_transactional.tx.dbal.default'))->will($this->returnValue($manager));

        $registry = new TransactionsRegistry($container);
        $matcher = new TransactionalMatcher(array(), array(
            'conn' => 'dbal.default',
            'methods' => array('POST'),
        ));
        $txListener = new HttpTransactionsListener($registry, $matcher, $this->logger);
        $dispatcher = new EventDispatcher();
        $dispatcher->addListener("kernel.controller", array($txListener, 'onCoreController'));
        $dispatcher->addListener("kernel.exception", array($txListener, 'onKernelException'));
        $dispatcher->addListener("kernel.response", array($txListener, 'onKernelResponse'));
        $this->kernel = new HttpKernel($dispatcher, $resolver);
    }

    public function testGetRequest()
    {
        $request = Request::create('/foo', 'GET');
        $this->kernel->handle($request);

        $this->assertEquals(array(
            "[TransactionBundle] Started transaction for dbal.default",
            "[TransactionBundle] Committed transaction for dbal.default"
        ), $this->logger->logs);
    }

    public function testPostRequest()
    {
        $request = Request::create('/foo', 'POST');
        $this->kernel->handle($request);

        $this->assertEquals(array(
            "[TransactionBundle] Started transaction for dbal.default",
            "[TransactionBundle] Committed transaction for dbal.default"
        ), $this->logger->logs);
    }
}

class StackLogger extends NullLogger
{
    public $logs = array();

    public function info($message, array $contexts = array())
    {
        $this->logs[] = $message;
    }
}

class TestController
{
    public function indexAction()
    {
        return new Response('data', 200);
    }

    public function subAction()
    {
        return new Response('data', 200);
    }
}


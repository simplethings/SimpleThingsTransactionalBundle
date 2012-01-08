<?php

namespace SimpleThings\TransactionalBundle\Tests\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use SimpleThings\TransactionalBundle\Controller\HttpTransactionsListener;
use SimpleThings\TransactionalBundle\Transactions\TransactionDefinition;

class HttpTransactionsListenerTest extends \PHPUnit_Framework_TestCase
{
    private $container;
    private $matcher;

    /**
     * @covers SimpleThings\TransactionalBundle\Controller\HttpTransactionsListener::onCoreController
     * @covers SimpleThings\TransactionalBundle\Controller\HttpTransactionsListener::getTransactionManager
     */
    public function testOnCoreController()
    {
        $request = Request::create('/');
        $controllerCallback = array('foo', 'bar');
        $transactionDefinition = new TransactionDefinition('default', TransactionDefinition::PROPAGATION_REQUIRED, TransactionDefinition::ISOLATION_DEFAULT);

        $event = $this->getMockBuilder('Symfony\Component\HttpKernel\Event\FilterControllerEvent')->disableOriginalConstructor()->getMock();
        $event->expects($this->once())
            ->method('getRequest')
            ->will($this->returnValue($request));
        $event->expects($this->once())
            ->method('getController')
            ->will($this->returnValue($controllerCallback));

        $this->matcher->expects($this->once())
            ->method('match')
            ->with($this->equalTo('GET'), $this->equalTo($controllerCallback))
            ->will($this->returnValue(array($transactionDefinition)));

        $this->createTransactionManagerMock('default')
            ->expects($this->once())
            ->method('getTransaction')
            ->with($this->equalTo($transactionDefinition))
            ->will($this->returnValue($status = $this->createTransactionStatusMock()));

        $listener = new HttpTransactionsListener($this->container, $this->matcher);
        $listener->onCoreController($event);

        $this->assertTrue($request->attributes->has('_transactions'));
        $this->assertSame(array('default' => $status), $request->attributes->get('_transactions'));
    }

    /**
     * @covers SimpleThings\TransactionalBundle\Controller\HttpTransactionsListener::onKernelResponse
     * @covers SimpleThings\TransactionalBundle\Controller\HttpTransactionsListener::commit
     */
    public function testOnKernelResponseShouldBeCommitTransaction()
    {
        $transactionStatus = $this->createTransactionStatusMock();

        $request = new Request();
        $request->attributes->set('_transactions', array('default' => $transactionStatus));

        $event = $this->getMockBuilder('Symfony\Component\HttpKernel\Event\FilterResponseEvent')->disableOriginalConstructor()->getMock();
        $event->expects($this->once())
            ->method('getRequest')
            ->will($this->returnValue($request));
        $event->expects($this->once())
            ->method('getResponse')
            ->will($this->returnValue(new Response()));

        $this->createTransactionManagerMock('default')
            ->expects($this->once())
            ->method('commit')
            ->with($this->equalTo($transactionStatus));

        $listener = new HttpTransactionsListener($this->container, $this->matcher);

        $listener->onKernelResponse($event);
    }

    /**
     * @covers SimpleThings\TransactionalBundle\Controller\HttpTransactionsListener::onKernelResponse
     * @covers SimpleThings\TransactionalBundle\Controller\HttpTransactionsListener::rollback
     */
    public function testOnKernelResponseShouldBeRollbackTransaction()
    {
        $transactionStatus = $this->createTransactionStatusMock();

        $request = new Request();
        $request->attributes->set('_transactions', array('default' => $transactionStatus));

        $event = $this->getMockBuilder('Symfony\Component\HttpKernel\Event\FilterResponseEvent')->disableOriginalConstructor()->getMock();
        $event->expects($this->once())
            ->method('getRequest')
            ->will($this->returnValue($request));
        $event->expects($this->once())
            ->method('getResponse')
            ->will($this->returnValue(new Response('', 500)));

        $this->createTransactionManagerMock('default')
            ->expects($this->once())
            ->method('rollback')
            ->with($this->equalTo($transactionStatus));

        $listener = new HttpTransactionsListener($this->container, $this->matcher);

        $listener->onKernelResponse($event);
    }

    /**
     * @covers SimpleThings\TransactionalBundle\Controller\HttpTransactionsListener::onKernelException
     * @covers SimpleThings\TransactionalBundle\Controller\HttpTransactionsListener::rollback
     */
    public function testOnKernelExceptionShouldBeRollbackTransaction()
    {
        $transactionStatus = $this->createTransactionStatusMock();

        $request = new Request();
        $request->attributes->set('_transactions', array('default' => $transactionStatus));

        $event = $this->getMockBuilder('Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent')->disableOriginalConstructor()->getMock();
        $event->expects($this->once())
            ->method('getRequest')
            ->will($this->returnValue($request));

        $this->createTransactionManagerMock('default')
            ->expects($this->once())
            ->method('rollback')
            ->with($this->equalTo($transactionStatus));

        $listener = new HttpTransactionsListener($this->container, $this->matcher);

        $listener->onKernelException($event);
    }

    /**
     * @covers SimpleThings\TransactionalBundle\Controller\HttpTransactionsListener::onKernelException
     */
    public function testOnKernelExceptionShouldNotBeRollbackTransaction()
    {
        $request = new Request();
        $request->attributes = $this->getMock('Symfony\Component\HttpFoundation\ParameterBag');

        $event = $this->getMockBuilder('Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent')->disableOriginalConstructor()->getMock();
        $event->expects($this->once())
            ->method('getRequest')
            ->will($this->returnValue($request));

        $request->attributes->expects($this->once())
            ->method('has')
            ->with($this->equalTo('_transactions'))
            ->will($this->returnValue(false));

        $request->attributes->expects($this->never())
            ->method('get');

        $listener = new HttpTransactionsListener($this->container, $this->matcher);

        $listener->onKernelException($event);
    }

    protected function setUp()
    {
        $this->container = $this->getMock('Symfony\Component\DependencyInjection\ContainerInterface');
        $this->matcher = $this->getMockBuilder('SimpleThings\TransactionalBundle\Transactions\TransactionalMatcher')->disableOriginalConstructor()->getMock();
    }

    private function createTransactionManagerMock($name)
    {
        $manager = $this->getMock('SimpleThings\TransactionalBundle\Transactions\TransactionManagerInterface');

        $this->container->expects($this->any())
            ->method('has')
            ->with($this->equalTo('simple_things_transactional.tx.' . $name))
            ->will($this->returnValue(true));
        $this->container->expects($this->any())
            ->method('get')
            ->with($this->equalTo('simple_things_transactional.tx.' . $name))
            ->will($this->returnValue($manager));

        return $manager;
    }

    private function createTransactionStatusMock()
    {
        return $this->getMock('SimpleThings\TransactionalBundle\Transactions\TransactionStatus');
    }
}
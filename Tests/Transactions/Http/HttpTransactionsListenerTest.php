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

namespace SimpleThings\TransactionalBundle\Tests\Transactions\Http;

use SimpleThings\TransactionalBundle\Transactions\Http\HttpTransactionsListener;
use SimpleThings\TransactionalBundle\Transactions\TransactionDefinition;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;

class HttpTransactionsListenerTest extends \PHPUnit_Framework_TestCase
{
    private $matcher;
    private $registry;
    private $listener;

    public function setUp()
    {
        $this->matcher = $this->getMock('SimpleThings\TransactionalBundle\Transactions\Http\TransactionalMatcher', array(), array(), '', false);
        $this->registry = $this->getMock('SimpleThings\TransactionalBundle\Transactions\TransactionsRegistry', array(), array(), '', false);
        $this->listener = new HttpTransactionsListener($this->registry, $this->matcher);
    }

    public function testOnCoreController()
    {
        $kernel = $this->getMock('Symfony\Component\HttpKernel\HttpKernelInterface');
        $request = Request::create('/foo', 'POST');
        $event = new FilterControllerEvent($kernel, array(__CLASS__, 'testOnCoreController'), $request, null);

        $txStatus = $this->getMock('SimpleThings\TransactionalBundle\Transactions\TransactionStatus');
        $def = new TransactionDefinition('name', 1, 1, false, array());

        $this->matcher->expects($this->once())->method('match')->will($this->returnValue($def));
        $this->registry->expects($this->once())->method('getTransaction')->with($this->equalTo($def))->will($this->returnValue($txStatus));

        $this->listener->onCoreController($event);

        $this->assertSame($txStatus, $request->attributes->get('_transaction'));
    }
}


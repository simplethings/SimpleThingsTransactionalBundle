<?php

namespace SimpleThings\TransactionalBundle\Tests\Transactions\Http;

use Symfony\Component\HttpFoundation\Request;
use SimpleThings\TransactionalBundle\Transactions\Annotations\Transactional;
use SimpleThings\TransactionalBundle\Transactions\Http\TransactionalMatcher;
use SimpleThings\TransactionalBundle\Transactions\TransactionDefinition;

class TransactionalMatcherTest extends \PHPUnit_Framework_TestCase
{
    private $reader;

    /**
     * @dataProvider getPatterns
     */
    public function testMatchPattern($pattern, $method, $matched, $readOnly)
    {
        $pattern = array(
            'pattern' => $pattern,
            'methods' => array('POST', 'PUT'),
            'conn' => 'orm.default',
            'noRollbackFor' => array(),
        );

        $matcher = new TransactionalMatcher(array($pattern), array(), $this->reader);
        $controller = new TestController();

        $definition = $matcher->match($method, array($controller, 'fooAction'));

        if ($matched) {
            $this->assertInstanceOf('SimpleThings\TransactionalBundle\Transactions\TransactionDefinition', $definition);
            $this->assertEquals('orm.default', $definition->getConnectionName());
            $this->assertEquals($readOnly, $definition->isReadOnly());
        } else {
            $this->assertFalse($definition);
        }
    }

    public function testMatchClassAnnotation()
    {
        $defaults = array(
            'noRollbackFor' => array(),
        );

        $matcher = new TransactionalMatcher(array(), $defaults, $this->reader);
        $controller = new TestController();

        $this->reader->expects($this->once())
            ->method('getClassAnnotation')
            ->will($this->returnValue(
                new Transactional(array(
                    'methods' => array('GET'),
                    'conn' => 'orm.default',
                ))
            ));

        $definition = $matcher->match('GET', array($controller, 'fooAction'));

        $expectedDefinition = new TransactionDefinition(
            'orm.default',
            false,
            array()
        );
        $this->assertEquals($expectedDefinition, $definition);
    }

    public function testMatchMethodAnnotation()
    {
        $defaults = array(
            'noRollbackFor' => array(),
        );

        $matcher = new TransactionalMatcher(array(), $defaults, $this->reader);
        $controller = new TestController();

        $this->reader->expects($this->once())
            ->method('getClassAnnotation');
        $this->reader->expects($this->once())
            ->method('getMethodAnnotation')
            ->will($this->returnValue(
                new Transactional(array(
                    'methods' => array('GET'),
                    'conn' => 'orm.default',
                ))
            ));

        $definition = $matcher->match('GET', array($controller, 'fooAction'));

        $expectedDefinition = new TransactionDefinition(
            'orm.default',
            false,
            array()
        );
        $this->assertEquals($expectedDefinition, $definition);
    }

    public function getPatterns()
    {
        return array(
            array('.*', 'POST', true, false),
            array('SimpleThings\\\\(.+)Controller::(.+)Action', 'POST', true, false),
            array('SimpleThings\\\\TransactionalBundle\\\\Tests\\\\Transactions\\\\Http\\\\TestController::fooAction', 'POST', true, false),
            array('.*', 'GET', true, true),
            array('SimpleThings\\\\(.+)Controller::barAction', 'POST', false, false),
        );
    }

    protected function setUp()
    {
        $this->reader = $this->getMock('Doctrine\Common\Annotations\Reader');
    }
}

class TestController
{
    public function fooAction() {}
}

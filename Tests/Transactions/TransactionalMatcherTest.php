<?php

namespace SimpleThings\TransactionalBundle\Tests\Transactions;

use Symfony\Component\HttpFoundation\Request;
use SimpleThings\TransactionalBundle\Annotations\Transactional;
use SimpleThings\TransactionalBundle\Transactions\TransactionalMatcher;
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
            'conn' => array('orm.default'),
            'isolation' => TransactionDefinition::ISOLATION_DEFAULT,
            'propagation' => TransactionDefinition::PROPAGATION_REQUIRED,
            'noRollbackFor' => array(),
            'subrequest' => false,
        );

        $matcher = new TransactionalMatcher(array($pattern), array(), $this->reader);
        $controller = new TestController();

        $definitions = $matcher->match($method, array($controller, 'fooAction'));

        if ($matched) {
            $this->assertInternalType('array', $definitions);
            $this->assertCount(1, $definitions);
            $this->assertEquals('orm.default', $definitions[0]->getManagerName());
            $this->assertEquals($readOnly, $definitions[0]->getReadOnly());
        } else {
            $this->assertCount(0, $definitions);
        }
    }

    public function testMatchClassAnnotation()
    {
        $defaults = array(
            'isolation' => TransactionDefinition::ISOLATION_DEFAULT,
            'propagation' => TransactionDefinition::PROPAGATION_REQUIRED,
            'noRollbackFor' => array(),
        );

        $matcher = new TransactionalMatcher(array(), $defaults, $this->reader);
        $controller = new TestController();

        $this->reader->expects($this->once())
            ->method('getClassAnnotation')
            ->will($this->returnValue(
                new Transactional(array(
                    'methods' => array('GET'),
                    'conn' => array('orm.default'),
                ))
            ));

        $definition = $matcher->match('GET', array($controller, 'fooAction'));

        $expectedDefinition = new TransactionDefinition(
            'orm.default',
            TransactionDefinition::PROPAGATION_REQUIRED,
            TransactionDefinition::ISOLATION_DEFAULT,
            false,
            array()
        );
        $this->assertEquals($expectedDefinition, $definition[0]);
    }

    public function testMatchMethodAnnotation()
    {
        $defaults = array(
            'isolation' => TransactionDefinition::ISOLATION_DEFAULT,
            'propagation' => TransactionDefinition::PROPAGATION_REQUIRED,
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
                    'conn' => array('orm.default'),
                ))
            ));

        $definition = $matcher->match('GET', array($controller, 'fooAction'));

        $expectedDefinition = new TransactionDefinition(
            'orm.default',
            TransactionDefinition::PROPAGATION_REQUIRED,
            TransactionDefinition::ISOLATION_DEFAULT,
            false,
            array()
        );
        $this->assertEquals($expectedDefinition, $definition[0]);
    }

    /**
     * @expectedException \SimpleThings\TransactionalBundle\TransactionException
     */
    public function testThrowExceptionWhenStoreDuplicateConnectionMatch()
    {
        $pattern = array(
            'pattern' => '.*',
            'methods' => array('GET'),
            'conn' => array('orm.default'),
            'isolation' => TransactionDefinition::ISOLATION_DEFAULT,
            'propagation' => TransactionDefinition::PROPAGATION_REQUIRED,
            'noRollbackFor' => array(),
            'subrequest' => false,
        );
        $this->reader->expects($this->once())
            ->method('getClassAnnotation')
            ->will($this->returnValue(
                new Transactional(array(
                    'methods' => array('GET'),
                    'subrequest' => true,
                    'conn' => array('orm.default'),
                ))
            ));

        $matcher = new TransactionalMatcher(array($pattern), array(), $this->reader);
        $request = Request::create('/foo', 'GET');

        $matcher->match($request, array(new TestController(), 'fooAction'));
    }

    public function getPatterns()
    {
        return array(
            array('.*', 'POST', true, false),
            array('SimpleThings\\\\(.+)Controller::(.+)Action', 'POST', true, false),
            array('SimpleThings\\\\TransactionalBundle\\\\Tests\\\\Transactions\\\\TestController::fooAction', 'POST', true, false),
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

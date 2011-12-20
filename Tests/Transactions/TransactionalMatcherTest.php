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
    public function testMatchPattern($pattern, $method, $matched)
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
        $request = Request::create('/foo', $method);
        $controller = new TestController();

        $definition = $matcher->match($request, array($controller, 'fooAction'));

        if ($matched) {
            $expectedDefinition = new TransactionDefinition(array(
                'orm.default' => array(
                    'isolation' => TransactionDefinition::ISOLATION_DEFAULT,
                    'propagation' => TransactionDefinition::PROPAGATION_REQUIRED,
                    'noRollbackFor' => array(),
                    'subrequest' => false,
                )
            ));
            $this->assertEquals($expectedDefinition, $definition);
        } else {
            $this->assertFalse($definition);
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
        $request = Request::create('/foo', 'GET');
        $controller = new TestController();

        $this->reader->expects($this->once())
            ->method('getClassAnnotation')
            ->will($this->returnValue(
                new Transactional(array(
                    'methods' => array('GET'),
                    'subrequest' => true,
                    'conn' => array('orm.default'),
                ))
            ));

        $definition = $matcher->match($request, array($controller, 'fooAction'));

        $expectedDefinition = new TransactionDefinition(array(
            'orm.default' => array(
                'isolation' => TransactionDefinition::ISOLATION_DEFAULT,
                'propagation' => TransactionDefinition::PROPAGATION_REQUIRED,
                'noRollbackFor' => array(),
                'subrequest' => true,
            )
        ));
        $this->assertEquals($expectedDefinition, $definition);
    }

    public function testMatchMethodAnnotation()
    {
        $defaults = array(
            'isolation' => TransactionDefinition::ISOLATION_DEFAULT,
            'propagation' => TransactionDefinition::PROPAGATION_REQUIRED,
            'noRollbackFor' => array(),
        );

        $matcher = new TransactionalMatcher(array(), $defaults, $this->reader);
        $request = Request::create('/foo', 'GET');
        $controller = new TestController();

        $this->reader->expects($this->once())
            ->method('getClassAnnotation');
        $this->reader->expects($this->once())
            ->method('getMethodAnnotation')
            ->will($this->returnValue(
                new Transactional(array(
                    'methods' => array('GET'),
                    'subrequest' => true,
                    'conn' => array('orm.default'),
                ))
            ));

        $definition = $matcher->match($request, array($controller, 'fooAction'));

        $expectedDefinition = new TransactionDefinition(array(
            'orm.default' => array(
                'isolation' => TransactionDefinition::ISOLATION_DEFAULT,
                'propagation' => TransactionDefinition::PROPAGATION_REQUIRED,
                'noRollbackFor' => array(),
                'subrequest' => true,
            )
        ));
        $this->assertEquals($expectedDefinition, $definition);
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
            array('.*', 'POST', true),
            array('SimpleThings\\\\(.+)Controller::(.+)Action', 'POST', true),
            array('SimpleThings\\\\TransactionalBundle\\\\Tests\\\\Transactions\\\\TestController::fooAction', 'POST', true),
            array('.*', 'GET', false),
            array('SimpleThings\\\\(.+)Controller::barAction', 'POST', false),
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
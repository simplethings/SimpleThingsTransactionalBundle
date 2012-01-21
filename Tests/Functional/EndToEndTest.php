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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Log\NullLogger;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Scope;
use Symfony\Bundle\FrameworkBundle\HttpKernel;
use SimpleThings\TransactionalBundle\Transactions\Http\HttpTransactionsListener;
use SimpleThings\TransactionalBundle\Transactions\TransactionsRegistry;
use SimpleThings\TransactionalBundle\Transactions\TransactionDefinition;
use SimpleThings\TransactionalBundle\Transactions\Http\TransactionalMatcher;
use SimpleThings\TransactionalBundle\Transactions\ScopeHandler;
use SimpleThings\TransactionalBundle\Doctrine\DBALTransactionManager;
use Doctrine\DBAL\DriverManager;

class EndToEndTest extends \PHPUnit_Framework_TestCase
{
    private $kernel;
    private $logger;
    private $conn;

    public function setUp()
    {
        $conn = $this->conn = DriverManager::getConnection(array('driver' => 'pdo_sqlite', 'memory' => true));
        $table = new \Doctrine\DBAL\Schema\Table("testdata");
        $table->addColumn('id', 'integer', array('auto_increment' => true));
        $table->addColumn('val', 'string');
        $table->setPrimaryKey(array('id'));

        $conn->getSchemaManager()->createTable($table);

        $this->logger = new FunctionalStackLogger;

        $container = new ContainerBuilder();
        $container->addScope(new Scope('transactional'));
        $container->addScope(new Scope('request'));

        $container->set('doctrine.dbal.default_connection', $conn);
        $container->set('simple_things_transactional.connections.dbal.default', $conn);
        $scope = new ScopeHandler($container);

        $txManager = new DBALTransactionManager($container, $scope);
        $container->set('simple_things_transactional.tx.dbal.default', $txManager);

        $resolver = $this->getMock('Symfony\Component\HttpKernel\Controller\ControllerResolverInterface');
        $resolver->expects($this->at(0))->method('getController')->will($this->returnValue(array(new DBALTestController($container), 'firstAction')));
        $resolver->expects($this->at(2))->method('getController')->will($this->returnValue(array(new DBALTestController($container), 'secondAction')));
        $resolver->expects($this->any())->method('getArguments')->will($this->returnValue(array()));

        $registry = new TransactionsRegistry($container);
        $matcher = new TransactionalMatcher(array(), array(
            'conn' => 'dbal.default',
            'methods' => array('POST'),
            'propagation' => TransactionDefinition::PROPAGATION_ISOLATED,
            'isolation' => TransactionDefinition::ISOLATION_DEFAULT,
        ));

        $txListener = new HttpTransactionsListener($registry, $matcher, $this->logger);
        $dispatcher = new EventDispatcher();
        $dispatcher->addListener("kernel.controller", array($txListener, 'onCoreController'));
        $dispatcher->addListener("kernel.exception", array($txListener, 'onKernelException'));
        $dispatcher->addListener("kernel.response", array($txListener, 'onKernelResponse'));
        $this->kernel = new HttpKernel($dispatcher, $container, $resolver);
        $container->set('http_kernel', $this->kernel);
    }

    public function testGetRequest()
    {
        $request = Request::create('/foo', 'GET');
        $this->kernel->handle($request);

        $this->assertEquals(array(
            "[TransactionBundle] Started transaction for dbal.default",
            "[TransactionBundle] Committed transaction for dbal.default"
        ), $this->logger->logs);

        var_dump($this->conn->fetchAll("SELECT * FROM testdata"));
    }

    public function testPostRequest()
    {
        $request = Request::create('/foo', 'POST');
        $this->kernel->handle($request);

        $this->assertEquals(array(
            "[TransactionBundle] Started transaction for dbal.default",
            "[TransactionBundle] Committed transaction for dbal.default"
        ), $this->logger->logs);
        var_dump($this->conn->fetchAll("SELECT * FROM testdata"));
    }
}

class FunctionalStackLogger extends NullLogger
{
    public $logs = array();

    public function info($message, array $contexts = array())
    {
        $this->logs[] = $message;
    }
}

class DBALTestController
{
    private $container;

    public function __construct($container)
    {
        $this->container = $container;
    }

    public function firstAction()
    {
        $conn = $this->container->get('doctrine.dbal.default_connection');
        $conn->insert("testdata", array("val" => "foo"));

        try {
            $this->container->get('http_kernel')->forward('DBALTestController:secondAxtion');
        } catch(\Exception $e) {}

        return new Response('data', 200);
    }

    public function secondAction()
    {
        $conn = $this->container->get('doctrine.dbal.default_connection');
        $conn->insert("testdata", array("val" => "foo"));

        throw new \InvalidArgumentException("blablabla!");
    }
}


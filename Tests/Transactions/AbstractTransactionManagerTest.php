<?php
/**
 * SimpleThings TransactionBundle
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to kontakt@beberlei.de so I can send you a copy immediately.
 */

namespace SimpleThings\TransactionalBundle\Tests\Transactions;

use SimpleThings\TransactionalBundle\Transactions\TransactionDefinition;

class AbstractTransactionManagerTest extends \PHPUnit_Framework_TestCase
{
    private $manager;

    public function setUp()
    {
        $this->manager = $this->getMock(
            'SimpleThings\TransactionalBundle\Transactions\AbstractTransactionManager',
            array('doBeginTransaction', 'doCommit', 'doRollBack'),
            array($this->getMock('SimpleThings\TransactionalBundle\Transactions\ScopeHandler', array(), array(), '', false))
        );
    }

    private function getTxStatusMock()
    {
        return $this->getMock('SimpleThings\TransactionalBundle\Transactions\TransactionStatus');
    }

    public function testGetTransaction()
    {
        $txStatus = $this->getTxStatusMock();
        $this->manager->expects($this->once())->method('doBeginTransaction')->will($this->returnValue($txStatus));
        $def = $this->createDefinition(TransactionDefinition::PROPAGATION_JOINED, TransactionDefinition::ISOLATION_DEFAULT);
        $actualStatus = $this->manager->getTransaction($def);

        $this->assertSame($txStatus, $actualStatus);
    }

    public function testGetNeverTransaction()
    {
        $def = $this->createDefinition(TransactionDefinition::PROPAGATION_MANUAL, TransactionDefinition::ISOLATION_DEFAULT);
        $actualStatus = $this->manager->getTransaction($def);

        $this->assertNull($actualStatus);
    }

    public function testGetTransactionNeverButOpen()
    {
        $txStatus = $this->getTxStatusMock();
        $this->manager->expects($this->once())->method('doBeginTransaction')->will($this->returnValue($txStatus));

        $def1 = $this->createDefinition(TransactionDefinition::PROPAGATION_JOINED, TransactionDefinition::ISOLATION_DEFAULT);
        $def2 = $this->createDefinition(TransactionDefinition::PROPAGATION_MANUAL, TransactionDefinition::ISOLATION_DEFAULT);

        $actualStatus1 = $this->manager->getTransaction($def1);
        $this->setExpectedException("SimpleThings\TransactionalBundle\TransactionException");
        $actualStatus2 = $this->manager->getTransaction($def2);
    }

    public function testGetRequireTransactionTwice()
    {
        $txStatus = $this->getTxStatusMock();
        $this->manager->expects($this->once())->method('doBeginTransaction')->will($this->returnValue($txStatus));

        $def = $this->createDefinition(TransactionDefinition::PROPAGATION_JOINED, TransactionDefinition::ISOLATION_DEFAULT);
        $actualStatus1 = $this->manager->getTransaction($def);
        $actualStatus2 = $this->manager->getTransaction($def);

        $this->assertSame($actualStatus1, $actualStatus2);
    }

    public function testGetRequiredIsolationLevelMissmatch()
    {
        $txStatus = $this->getTxStatusMock();
        $this->manager->expects($this->once())->method('doBeginTransaction')->will($this->returnValue($txStatus));

        $def1 = $this->createDefinition(TransactionDefinition::PROPAGATION_JOINED, TransactionDefinition::ISOLATION_DEFAULT);
        $def2 = $this->createDefinition(TransactionDefinition::PROPAGATION_JOINED, TransactionDefinition::ISOLATION_REPEATABLE_READ);

        $actualStatus1 = $this->manager->getTransaction($def1);

        $this->setExpectedException("SimpleThings\TransactionalBundle\TransactionException");
        $actualStatus2 = $this->manager->getTransaction($def2);
    }

    public function testGetReadOnlyMissMatch()
    {
        $txStatus = $this->getTxStatusMock();
        $txStatus->expects($this->once())->method('isReadOnly')->will($this->returnValue(true));
        $this->manager->expects($this->once())->method('doBeginTransaction')->will($this->returnValue($txStatus));

        $def1 = $this->createDefinition(TransactionDefinition::PROPAGATION_JOINED, TransactionDefinition::ISOLATION_DEFAULT);
        $def2 = $this->createDefinition(TransactionDefinition::PROPAGATION_JOINED, TransactionDefinition::ISOLATION_DEFAULT);

        $actualStatus1 = $this->manager->getTransaction($def1);

        $this->setExpectedException("SimpleThings\TransactionalBundle\TransactionException");
        $actualStatus2 = $this->manager->getTransaction($def2);
    }

    public function testGetTransactionPropagationSupports()
    {
        $this->manager->expects($this->never())->method('doBeginTransaction');

        $def = $this->createDefinition(TransactionDefinition::PROPAGATION_SUPPORTS, TransactionDefinition::ISOLATION_DEFAULT);

        $status = $this->manager->getTransaction($def);
        $this->assertNull($status);
    }

    public function testGetTransactionPropagationSupportsNestedInRequired()
    {
        $txStatus = $this->getTxStatusMock();
        $this->manager->expects($this->once())->method('doBeginTransaction')->will($this->returnValue($txStatus));

        $def1 = $this->createDefinition(TransactionDefinition::PROPAGATION_JOINED, TransactionDefinition::ISOLATION_DEFAULT);
        $def2 = $this->createDefinition(TransactionDefinition::PROPAGATION_SUPPORTS, TransactionDefinition::ISOLATION_DEFAULT);

        $actualStatus1 = $this->manager->getTransaction($def1);
        $actualStatus2 = $this->manager->getTransaction($def2);

        $this->assertSame($actualStatus1, $actualStatus2);
    }

    public function testGetTransactionRequiresNew()
    {
        $txStatus1 = $this->getTxStatusMock();
        $txStatus2 = $this->getTxStatusMock();
        $this->manager->expects($this->at(0))->method('doBeginTransaction')->will($this->returnValue($txStatus1));
        $this->manager->expects($this->at(1))->method('doBeginTransaction')->will($this->returnValue($txStatus2));

        $def1 = $this->createDefinition(TransactionDefinition::PROPAGATION_JOINED, TransactionDefinition::ISOLATION_DEFAULT);
        $def2 = $this->createDefinition(TransactionDefinition::PROPAGATION_ISOLATED, TransactionDefinition::ISOLATION_DEFAULT);

        $actualStatus1 = $this->manager->getTransaction($def1);
        $actualStatus2 = $this->manager->getTransaction($def2);

        $this->assertNotSame($actualStatus1, $actualStatus2);
    }

    public function testCommit()
    {
        $txStatus1 = $this->getTxStatusMock();
        $this->manager->expects($this->at(0))->method('doBeginTransaction')->will($this->returnValue($txStatus1));
        $this->manager->expects($this->at(1))->method('doCommit');
        $def1 = $this->createDefinition(TransactionDefinition::PROPAGATION_JOINED, TransactionDefinition::ISOLATION_DEFAULT);

        $actualStatus1 = $this->manager->getTransaction($def1);

        $this->manager->commit($actualStatus1);
    }

    public function testCommitRecommitException()
    {
        $txStatus1 = $this->getTxStatusMock();
        $this->manager->expects($this->at(0))->method('doBeginTransaction')->will($this->returnValue($txStatus1));
        $this->manager->expects($this->at(1))->method('doCommit');
        $def1 = $this->createDefinition(TransactionDefinition::PROPAGATION_JOINED, TransactionDefinition::ISOLATION_DEFAULT);

        $actualStatus1 = $this->manager->getTransaction($def1);

        $this->manager->commit($actualStatus1);

        $this->setExpectedException("SimpleThings\TransactionalBundle\TransactionException");
        $this->manager->commit($actualStatus1);
    }

    public function testCommitRollbackOnly()
    {
        $txStatus1 = $this->getTxStatusMock();
        $txStatus1->expects($this->once())->method('isRollBackOnly')->will($this->returnValue(true));

        $this->manager->expects($this->at(0))->method('doBeginTransaction')->will($this->returnValue($txStatus1));
        $this->manager->expects($this->at(1))->method('doRollBack');
        $def1 = $this->createDefinition(TransactionDefinition::PROPAGATION_JOINED, TransactionDefinition::ISOLATION_DEFAULT);

        $actualStatus1 = $this->manager->getTransaction($def1);
        $this->manager->commit($actualStatus1);
    }

    public function testCommitRequiresNew()
    {
        $txStatus1 = $this->getTxStatusMock();
        $txStatus1->expects($this->once())->method('isRollBackOnly')->will($this->returnValue(true));
        $txStatus2 = $this->getTxStatusMock();

        $this->manager->expects($this->at(0))->method('doBeginTransaction')->will($this->returnValue($txStatus1));
        $this->manager->expects($this->at(1))->method('doBeginTransaction')->will($this->returnValue($txStatus2));
        $this->manager->expects($this->at(2))->method('doCommit')->with($this->equalTo($txStatus2));
        $this->manager->expects($this->at(2))->method('doCommit')->with($this->equalTo($txStatus1));

        $def1 = $this->createDefinition(TransactionDefinition::PROPAGATION_JOINED, TransactionDefinition::ISOLATION_DEFAULT);
        $def2 = $this->createDefinition(TransactionDefinition::PROPAGATION_ISOLATED, TransactionDefinition::ISOLATION_DEFAULT);

        $actualStatus1 = $this->manager->getTransaction($def1);

        $actualStatus2 = $this->manager->getTransaction($def2);
        $this->manager->commit($actualStatus2);
        $this->manager->commit($actualStatus1);
    }

    private function createDefinition($propagation, $isolation, $readOnly = false)
    {
        return new TransactionDefinition("test", $propagation, $isolation, $readOnly, array());
    }
}


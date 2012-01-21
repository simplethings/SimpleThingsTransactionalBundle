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

namespace SimpleThings\TransactionalBundle\Transactions;

use SimpleThings\TransactionalBundle\TransactionException;

abstract class AbstractTransactionManager implements TransactionManagerInterface
{
    /**
     * @var array
     */
    private $transactions = array();

    /**
     * @var TrannsactionStatus
     */
    private $currentTransaction = null;

    /**
     * @var ScopeHandler
     */
    private $scope;

    public function __construct(ScopeHandler $scope)
    {
        $this->scope = $scope;
    }

    abstract protected function doBeginTransaction(TransactionDefinition $def);

    abstract protected function doCommit(TransactionStatus $def);

    abstract protected function doRollBack(TransactionStatus $def);

    protected function beginTransaction(TransactionDefinition $def)
    {
        $status = $this->doBeginTransaction($def);
        $this->transactions[] = $this->currentTransaction = $status;
        return $status;
    }

    public function getTransaction(TransactionDefinition $def)
    {
        switch ($def->getPropagation()) {
            case TransactionDefinition::PROPAGATION_ISOLATED:
                $this->scope->enterScope();
                $status = $this->beginTransaction($def);
                break;
            case TransactionDefinition::PROPAGATION_MANUAL:
                if (count($this->transactions)) {
                    throw new TransactionException("Controller does not want to run inside any transaction, but there is one open.");
                }
                return null;
            case TransactionDefinition::PROPAGATION_JOINED:
                $this->scope->enterScope();
                $this->scope->increaseNestingLevel();
                if ($this->currentTransaction) {
                    if ($def->getIsolationLevel() != $this->currentTransaction->getIsolationLevel()) {
                        throw new TransactionException("Trying to re-use transaction that has different isolation level than the already active one.");
                    }

                    if ($this->currentTransaction->isReadOnly() && ! $def->getReadOnly()) {
                        throw new TransactionException("Cannot reuse readonly transaction when requesting a read/write transaction.");
                    }
                    $status = $this->currentTransaction;
                } else {
                    $status = $this->beginTransaction($def);
                }

                break;
            case TransactionDefinition::PROPAGATION_SUPPORTS:
            default:
                if ($this->currentTransaction) {
                    $this->scope->increaseNestingLevel();
                }
                $status = $this->currentTransaction;
                break;
        }
        return $status;
    }

    public function commit(TransactionStatus $status)
    {
        if ($status->isRollBackOnly()) {
            return $this->rollBack($status);
        }

        if ($status->isCompleted()) {
            throw new TransactionException("Cannot commit an already completed transaction.");
        } else if ($this->currentTransaction !== $status) {
            throw new TransactionException("Cannot commit transaction that is not the currently active. The order of your transaction was messed up.");
        }

        $this->scope->decreaseNestingLevel();
        if ($this->scope->getNestingLevel() > 0) {
            return;
        }

        $this->cleanupAfterTransaction($status);
        if ($status->isReadOnly()) {
            return;
        }

        $this->doCommit($status);
    }

    public function rollBack(TransactionStatus $status)
    {
        if ($status->isCompleted()) {
            throw new TransactionException("Cannot rollback an already completed transaction.");
        } else if ($this->currentTransaction !== $status) {
            throw new TransactionException("Cannot commit transaction that is not the currently active. The order of your transaction was messed up.");
        }

        $this->scope->decreaseNestingLevel();
        if ($this->scope->getNestingLevel() > 0) {
            return;
        }

        $this->cleanupAfterTransaction($status);
        if ($status->isReadOnly()) {
            return;
        }

        $this->doRollBack($status);
    }

    private function cleanupAfterTransaction($status)
    {
        $this->scope->leaveScope();
        array_pop($this->transactions);
        $this->currentTransaction = end($this->transactions);
    }
}



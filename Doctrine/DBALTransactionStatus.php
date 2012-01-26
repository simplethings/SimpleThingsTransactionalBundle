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

namespace SimpleThings\TransactionalBundle\Doctrine;

use SimpleThings\TransactionalBundle\Transactions\TransactionStatus;
use SimpleThings\TransactionalBundle\Transactions\TransactionDefinition;
use Doctrine\DBAL\Connection;

class DBALTransactionStatus implements TransactionStatus
{
    private $def;
    private $conn;
    private $completed = false;

    public function __construct(Connection $conn, TransactionDefinition $def)
    {
        $this->conn = $conn;
        $this->def = $def;
    }

    /**
     * Checks if the transaction is read-only.
     *
     * A read-only transaction does not commit changes to the database when
     * commit is called. It allows the underlying transaction manager to
     * perform optimizations to this regard if possible.
     *
     * @return bool
     */
    public function isReadOnly()
    {
        return $this->def->isReadOnly();
    }

    /**
     * Check if transaction is broken and has to be rolled back at this point.
     *
     * @return bool
     */
    public function isRollBackOnly()
    {
        $this->conn->isRollBackOnly();
    }

    /**
     * Mark the transaction as rollback-only
     *
     * @return void
     */
    public function setRollBackOnly()
    {
        $this->conn->setRollBackOnly(true);
    }

    /**
     * Check if this transaction was committed already.
     *
     * @return bool
     */
    public function isCompleted()
    {
        return $this->completed;
    }

    public function getWrappedConnection()
    {
        return $this->conn;
    }

    /**
     * Begin the transaction
     */
    public function beginTransaction()
    {
        $this->conn->beginTransaction();
    }

    /**
     * Commit the transaction
     *
     * Depending on the Transaction#isRollBackOnly status this method commits
     * or rollbacks the transaction wrapped inside the status. If an error
     * happens during commit the original exception of the underlying
     * connection is thrown from this method.
     *
     * @throws Exception
     * @return void
     */
    public function commit()
    {
        if ($this->isReadOnly()) {
            return $this->rollBack();
        }

        $this->conn->commit();
        if (0 === $this->conn->getTransactionNestingLevel()) {
            $this->completed = true;
        }
    }

    /**
     * Rollback the transaction inside the status object.
     *
     * @return void
     */
    public function rollBack()
    {
        $this->conn->rollBack();
        if (0 === $this->conn->getTransactionNestingLevel()) {
            $this->completed = true;
        }
    }
}


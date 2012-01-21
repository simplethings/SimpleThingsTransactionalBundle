<?php
/**
 * SImpleThings TransactionalBundle
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

    public function getIsolationLevel()
    {
        return $this->def->getIsolationLevel();
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
        return $this->def->getReadOnly();
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

    /**
     * Check if this transaction has savepoints.
     *
     * @return bool
     */
    public function hasSavepoint()
    {
        return false;
    }

    public function getWrappedConnection()
    {
        return $this->conn;
    }

    public function markCompleted()
    {
        $this->completed = true;
    }
}


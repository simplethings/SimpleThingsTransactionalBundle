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

/**
 * Contains information about the current state of a transaction.
 */
interface TransactionStatus
{
    /**
     * Checks if the transaction is read-only.
     *
     * A read-only transaction does not commit changes to the database when
     * commit is called. It allows the underlying transaction manager to
     * perform optimizations to this regard if possible.
     *
     * @return bool
     */
    function isReadOnly();

    /**
     * Check if transaction is broken and has to be rolled back at this point.
     *
     * @return bool
     */
    function isRollBackOnly();

    /**
     * Mark the transaction as rollback-only
     *
     * @return void
     */
    function setRollBackOnly();

    /**
     * Check if this transaction was committed already.
     *
     * @return bool
     */
    function isCompleted();

    /**
     * Return the connection object that is wrapped in this status.
     *
     * @return object
     */
    function getWrappedConnection();

    /**
     * Begin the transaction
     */
    function beginTransaction();

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
    function commit();

    /**
     * Rollback the transaction inside the status object.
     *
     * @return void
     */
    function rollBack();
}


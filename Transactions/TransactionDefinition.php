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
 * Describes the properties of a transaction
 *
 * @author Benjamin Eberlei <kontakt@beberlei.de>
 */
class TransactionDefinition
{
    /**
     * A transaction definition of this kind doesnt mind if its nested inside
     * another transaction or not and does not start a transaction on its own.
     *
     * @var int
     */
    const PROPAGATION_SUPPORTS = 1;

    /**
     * Joins into an existing transaction or opens a new one.
     *
     * This is the default behavior.
     *
     * @var int
     */
    const PROPAGATION_JOINED = 2;

    /**
     * A NEW transaction is required. When the transaction is finished the old
     * higher level transaction will be restored. This mode may open new
     * database connection leading to additional resources being used by your
     * script.
     *
     * @var int
     */
    const PROPAGATION_ISOLATED = 3;

    /**
     * Throws an exception if a transaction is open. Doesn't open a transaction
     * itself. Leaves transaction management to the user.
     *
     * @var int
     */
    const PROPAGATION_MANUAL = 4;

    const ISOLATION_DEFAULT = 0;
    const ISOLATION_READ_UNCOMMITTED = 1;
    const ISOLATION_READ_COMMITTED = 2;
    const ISOLATION_REPEATABLE_READ = 3;
    const ISOLATION_SERIALIZABLE = 4;

    /**
     * @var string
     */
    private $managerName;

    /**
     * @var int
     */
    private $isolationLevel;

    /**
     * @var bool
     */
    private $readOnly;

    /**
     * @var int
     */
    private $propagation;

    /**
     * @var array
     */
    private $noRollbackFor = array();

    public function __construct($managerName, $propagation, $isolationLevel, $readOnly = false, $noRollbackFor = array())
    {
        $this->managerName = $managerName;
        $this->propagation = $propagation;
        $this->isolationLevel = $isolationLevel;
        $this->readOnly = $readOnly;
        $this->noRollbackFor = $noRollbackFor;
    }

    /**
     * Get propagation.
     *
     * @return propagation.
     */
    public function getPropagation()
    {
        return $this->propagation;
    }

    /**
     * Get readOnly.
     *
     * @return readOnly.
     */
    public function getReadOnly()
    {
        return $this->readOnly;
    }

    /**
     * Get isolationLevel.
     *
     * @return isolationLevel.
     */
    public function getIsolationLevel()
    {
        return $this->isolationLevel;
    }

    /**
     * Get managerName.
     *
     * @return managerName.
     */
    public function getManagerName()
    {
        return $this->managerName;
    }
    
    /**
     * Get noRollbackFor.
     *
     * @return noRollbackFor.
     */
    public function getNoRollbackFor()
    {
        return $this->noRollbackFor;
    }
}

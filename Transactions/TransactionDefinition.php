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
     * @var string
     */
    private $connectionName;

    /**
     * @var bool
     */
    private $readOnly;

    /**
     * @var array
     */
    private $noRollbackFor = array();

    public function __construct($connectionName, $readOnly = false, $noRollbackFor = array())
    {
        $this->connectionName = $connectionName;
        $this->readOnly = $readOnly;
        $this->noRollbackFor = $noRollbackFor;
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
     * Get connectionName.
     *
     * @return string
     */
    public function getConnectionName()
    {
        return $this->connectionName;
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

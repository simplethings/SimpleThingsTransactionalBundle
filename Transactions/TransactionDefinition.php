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

class TransactionDefinition
{
    const PROPAGATION_SUPPORTS = 1;
    const PROPAGATION_REQUIRED = 2;
    const PROPAGATION_REQUIRES_NEW = 3;
    const PROPAGATION_NEVER = 4;

    const ISOLATION_DEFAULT = 0;
    const ISOLATION_READ_UNCOMMITTED = 1;
    const ISOLATION_READ_COMMITTED = 2;
    const ISOLATION_REPEATABLE_READ = 3;
    const ISOLATION_SERIALIZABLE = 4;

    private $connections = array();

    public function __construct(array $connections)
    {
        return $this->connections = $connections;
    }

    public function getConnections()
    {
        return array_keys($this->connections);
    }

    public function getIsolationLevel($connection)
    {
        return $this->connections[$connection]['isolation'];
    }

    public function getPropagation($connection)
    {
        return $this->connections[$connection]['propagation'];
    }

    public function getNoRollbackFor($connection)
    {
        return $this->connections[$connection]['noRollbackFor'];
    }

    public function isInvokedOnSubrequest($connection)
    {
        return $this->connections[$connection]['subrequest'];
    }
}
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

namespace SimpleThings\TransactionalBundle\Transactions\Doctrine;

use Doctrine\DBAL\Connection;
use SimpleThings\TransactionalBundle\Transactions\TransactionManagerInterface;

/**
 * Wraps a Doctrine DBAL Connection into a transaction manager
 * 
 * @author Benjamin Eberlei
 */
class DoctrineDBALTransactionManager implements TransactionManagerInterface
{
    private $conn;
    
    public function __construct(Connection $conn)
    {
        $this->conn = $conn;
    }
    
    public function beginTransaction()
    {
        $this->conn->beginTransaction();
    }
    
    public function commit()
    {
        $this->conn->commit();
    }
    
    public function rollBack()
    {
        $this->conn->rollBack();
    }
}
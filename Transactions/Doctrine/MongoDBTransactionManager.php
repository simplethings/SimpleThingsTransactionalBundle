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

use Doctrine\ODM\MongoDB\DocumentManager;
use SimpleThings\TransactionalBundle\Transactions\TransactionManagerInterface;

class MongoDBTransactionManager implements TransactionManagerInterface
{
    private $dm;
    
    public function __construct(DocumentManager $dm)
    {
        $this->dm = $dm;
    }

    public function beginTransaction()
    {
        
    }

    public function commit()
    {
        $this->dm->flush();
    }

    public function rollBack()
    {
        
    }

}
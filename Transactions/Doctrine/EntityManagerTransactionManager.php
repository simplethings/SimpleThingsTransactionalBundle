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

use Doctrine\Bundle\DoctrineBundle\Registry;
use SimpleThings\TransactionalBundle\Transactions\TransactionManagerInterface;

class EntityManagerTransactionManager implements TransactionManagerInterface
{
    private $doctrineRegistry;
    private $name;

    public function __construct(Registry $doctrineRegistry, $name)
    {
        $this->doctrineRegistry = $doctrineRegistry;
        $this->name = $name;
    }

    public function beginTransaction()
    {
        $this->doctrineRegistry->getManager($this->name)->beginTransaction();
    }

    public function commit()
    {
        $em = $this->doctrineRegistry->getManager($this->name);
        $em->flush();
        $em->commit();
    }

    public function rollBack()
    {
        $this->doctrineRegistry->getManager($this->name)->rollback();
        $this->doctrineRegistry->resetManager($this->name);
    }
}

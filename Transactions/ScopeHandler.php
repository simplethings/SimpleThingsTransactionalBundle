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
 * Wrapper for the Transaction scope
 */
class ScopeHandler
{
    private $container;
    private $levels = array();
    private $currentLevel = 0;

    public function __construct($container)
    {
        $this->container = $container;
    }

    public function enterScope()
    {
        $this->levels[] = $this->currentLevel;
        $this->currentLevel = 0;
        $this->container->enterScope('transactional');
    }

    public function leaveScope()
    {
        if ($this->currentLevel > 0) {
            throw new TransactionException("Cannot leave transaction scope that still has levels.");
        }
        $this->currentLevel = array_pop($this->levels);
        $this->container->leaveScope('transactional');
    }

    public function increaseNestingLevel()
    {
        $this->currentLevel++;
    }

    public function decreaseNestingLevel()
    {
        $this->currentLevel--;
    }

    public function getNestingLevel()
    {
        return $this->level;
    }
}


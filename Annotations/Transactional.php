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

namespace SimpleThings\TransactionalBundle\Annotations;

use Doctrine\Common\Annotations\Annotation;
use SimpleThings\TransactionalBundle\Transactions\TransactionDefinition;

/**
 * @Annotation
 */
class Transactional extends Annotation
{
    /**
     * @var array
     */
    public $conn = null;
    /**
     * @var int
     */
    public $propagation = null;
    /**
     * @var int
     */
    public $isolation = null;
    /**
     * @var array
     */
    public $noRollbackFor = null;
    /**
     * @var array
     */
    public $methods = null;

    /**
     * @var bool
     */
    public $subrequest = null;
}
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

namespace SimpleThingsTransactionalBundle\Transactions\Form;

use Symfony\Component\Form\FormValidatorInterface;
use Symfony\Component\Form\Form;

/**
 * "Missusing" the FormValidator to set transactions to rollback only when the validation failed.
 *
 * The POST_BIND event should be AFTER validation imho.
 *
 * @author Benjamin Eberlei <kontakt@beberlei.de>
 */
class RollbackInvalidFormValidator implements FormValidatorInterface
{
    private $container;

    public function __construct($container)
    {
        $this->container = $container;
    }

    public function validate(FormInterface $form)
    {
        if (!$this->container->has('request')) {
            return;
        }

        $request = $this->container->get('request');
        if (!$form->isValid()) {
            foreach ($request->attributes->get('_transactions') as $tx) {
                $tx->setRollbackOnly();
            }
        }
    }
}


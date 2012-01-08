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

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilder;

class RollbackInvalidFormExtension extends FormExtension
{
    public function buildForm(FormBuilder $builder, array $options)
    {
        $builder->addValidator(new RollbackInvalidFormValidator());
    }

    public function getExtendedType()
    {
        return 'form';
    }
}


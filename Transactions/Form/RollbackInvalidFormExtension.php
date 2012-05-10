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

namespace SimpleThings\TransactionalBundle\Transactions\Form;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilder;

class RollbackInvalidFormExtension extends AbstractTypeExtension
{
    private $validator;

    public function __construct(RollbackInvalidFormValidator $rollbackValidator)
    {
        $this->validator = $rollbackValidator;
    }

    public function buildForm(FormBuilder $builder, array $options)
    {
        $builder->addValidator($this->validator);
    }

    public function getExtendedType()
    {
        return 'form';
    }
}


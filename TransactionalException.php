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

namespace SimpleThings\TransactionalBundle;

class TransactionException extends \RuntimeException
{
    static public function duplicateConnectionMatch($connName, $secondPattern)
    {
        return new self(
            "The connection '" . $connName . "' matches a second time with pattern " . $secondPattern . ". " .
            "Each connection is only allowed to match once, conflict resolution is currently not possible."
        );
    }
}
<?php

namespace SimpleThings\TransactionalBundle\Tests\Transactions;

class TransactionalMatcherTest extends \PHPUnit_Framework_TestCase
{
    private $reader;
    private $matcher;

    public function setUp()
    {
        $this->reader = $this->getMock('Doctrine\Common\Annotations\Reader');
    }

    public function testMatch()
    {
        $this->matcher = new TransactionalMatcher(array('/foo/bar'), array(), $this->reader);
    }
}


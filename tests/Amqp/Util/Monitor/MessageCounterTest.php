<?php

namespace Test\Amqp\Util\Monitor;

use \Amqp\Util\Monitor\MessageCounter;

class MessageCounterTest extends \PHPUnit_Framework_TestCase
{
    /** @var \Amqp\Util\Listener\Simple */
    protected $listener;

    /**
     * set up
     *
     * @author Thiago Carvalho <thiago.carvalho@westwing.de>
     */
    public function setUp()
    {
        $this->listener = $this->getMockBuilder('\Amqp\Util\Listener\Simple')
            ->getMock();
    }

    /**
     * test counter check with limit
     *
     * @author Thiago Carvalho <thiago.carvalho@westwing.de>
     */
    public function testCheck()
    {
        $counter = new MessageCounter();
        $counter->setLimit(2);

        $this->assertTrue($counter->check($this->listener));
        $this->assertTrue($counter->check($this->listener));
        $this->assertFalse($counter->check($this->listener));
    }

    /**
     * test counter check unlimited
     *
     * @author Thiago Carvalho <thiago.carvalho@westwing.de>
     */
    public function testCheckUnlimited()
    {
        $counter = new MessageCounter();
        $counter->setLimit(0);

        // we can not test forever, 10 should be enough in this case
        for ($i = 0; $i < 10; $i++) {
            $this->assertTrue($counter->check($this->listener));
        }
    }
}

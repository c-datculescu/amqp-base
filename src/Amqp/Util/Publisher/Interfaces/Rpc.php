<?php
namespace Amqp\Util\Publisher\Interfaces;

use Amqp\Util\Interfaces\TimeoutProcessor;

interface Rpc extends Publisher
{
    /**
     * In case the rpc call times out, this method notifies all processors that a timeout has been reached and
     * execution has been stopped
     */
    public function timeout();

    /**
     * Attaches a processor to the list of processors to be notified about received messages
     *
     * @param TimeoutProcessor $processor The processor to be notified
     * @return $this
     */
    public function attach(TimeoutProcessor $processor);
}
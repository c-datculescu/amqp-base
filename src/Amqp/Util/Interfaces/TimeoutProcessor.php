<?php
namespace Amqp\Util\Interfaces;

interface TimeoutProcessor extends Processor
{
    /**
     * This method will get called when a timeout for listening for an rpc response is exceeded
     *
     * @return mixed
     */
    public function timeout();
}
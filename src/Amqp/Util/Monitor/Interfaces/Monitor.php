<?php
namespace Amqp\Util\Monitor\Interfaces;

use Amqp\Util\Listener\Interfaces\Listener;

interface Monitor
{
    /**
     * Returns the result of the check. If the check is false, than the processing in the listener has to stop
     *
     * @param Listener $listener The listener we are currently watching
     *
     * @return bool
     */
    public function check(Listener $listener);
}
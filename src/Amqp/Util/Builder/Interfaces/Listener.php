<?php
namespace Amqp\Util\Builder\Interfaces;

interface Listener
{
    /**
     * Returns a listeners by its name as it is found in the configuration
     * Performs all the needed operations, including attaching monitors
     *
     * @param string $name The name of the listener as defined in the configuration
     *
     * @return \Amqp\Util\Listener\Interfaces\Listener
     *
     * @throws \Amqp\Util\Builder\Exception if the listener definition could not be located
     */
    public function listener($name);
}
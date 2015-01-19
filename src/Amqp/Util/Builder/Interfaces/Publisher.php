<?php
namespace Amqp\Util\Builder\Interfaces;

interface Publisher
{
    /**
     * Returns a publisher as defined in the configuration
     *
     * @param string $name The name of the publisher as used in the configuration
     *
     * @return \Amqp\Util\Publisher\Interfaces\Publisher|\Amqp\Util\Publisher\Interfaces\Rpc
     *
     * @throws \Amqp\Util\Builder\Exception If the definition cannot be located
     */
    public function publisher($name);
}
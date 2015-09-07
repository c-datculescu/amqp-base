<?php

namespace Amqp\Publisher;

use Amqp\Util\Publisher\Interfaces\Publisher;

interface PublisherInterface extends Publisher
{

    /**
     * @return string
     */
    public function getExchangeName();
}
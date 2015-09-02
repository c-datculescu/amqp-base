<?php

namespace Amqp;
use Amqp\Publisher\Lazy;

/**
 * Class Builder
 * @package Amqp
 * @todo Merge Base\Builder\Amqp to this class, and remove old
 */
class Builder extends Base\Builder\Amqp
{
    /**
     * Get publisher
     *
     * @param string $exchangeName Exchange internal (configuration key) value
     * @param array  $options      Publisher options
     *
     * @return Lazy Lazy publisher
     */
    public function publisher($exchangeName, $options = [])
    {
        return new Lazy($this, $exchangeName, $options);
    }

    /**
     * Release connection and related objects (channel, exchange)
     *
     * @param string $exchangeName Exchange internal name
     *
     * @return void
     */
    public function releaseConnAndDeps($exchangeName)
    {
        $channelName = $this->amqpConfiguration['exchange'][$exchangeName]['channel'];
        $connectionName = $this->amqpConfiguration['channel'][$channelName]['connection'];

        unset($this->connections[$connectionName], $this->channels[$channelName], $this->exchanges[$exchangeName]);
    }
}
<?php
namespace Amqp\Base\Builder\Interfaces;

interface Amqp
{
    /**
     * Returns a specified amqp connection
     *
     * @param string $connectionName The name of the connection defined in the configuration
     *
     * @return \AMQPConnection
     *
     * @throws \Amqp\Base\Builder\Exception if the connection cannot be located in the configuration file
     */
    public function connection($connectionName);

    /**
     * Returns a specified amqp channel
     *
     * @param string $channelName The name of the channel from the configuration
     *
     * @return \AMQPChannel
     *
     * @throws \Amqp\Base\Builder\Exception if the configuration for the channel cannot be found
     */
    public function channel($channelName);

    /**
     * Returns a specified queue
     *
     * @param string $queueName        The name of the queue from the configuration
     * @param bool   $initDependencies Specifies whether we should initialize the dependencies as well
     *
     * @return \AMQPQueue
     *
     * @throws \Amqp\Base\Builder\Exception If the configuration for the queue cannot be located
     */
    public function queue($queueName, $initDependencies = true);

    /**
     * Returns a specified exchange
     *
     * @param string $exchangeName     The name of the exchange from the configuration
     * @param bool   $initDependencies Specifies whether we should initialize the dependencies or not
     *
     * @return \AMQPExchange
     *
     * @throws \Amqp\Base\Builder\Exception if the exchange definition cannot be located
     */
    public function exchange($exchangeName, $initDependencies = true);
}
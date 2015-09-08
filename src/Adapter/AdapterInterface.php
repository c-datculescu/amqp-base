<?php
namespace Amqp\Adapter;

interface AdapterInterface
{
    /**
     * Publish a message onto an exchange
     *
     * @param string $exchangeName The name of the exchange to publish to
     * @param string $message      The message to be published
     * @param string $routingKey   The routing key to publish the message
     *
     * @return boolean
     */
    public function publish($exchangeName, $message, $routingKey = null);

    /**
     * Listen for incoming messages in a blocking fashion.
     *
     * @param string   $queue    The queue on which we want to listen
     * @param callable $callable The callable to apply upon receiving messages
     * @param array    $options  The additional options to be applied when consuming messages [autoack => bool]
     *
     * @return void
     */
    public function listen($queue, callable $callable, array $options = array());

    /**
     * Returns a single message from the top of the queue
     *
     * @param string $queue The queue name
     *
     * @return mixed
     */
    public function getMessage($queue);
}
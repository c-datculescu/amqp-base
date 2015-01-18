<?php
namespace Amqp\Util\Listener\Interfaces;

use Amqp\Util\Interfaces\Monitor;
use Amqp\Util\Interfaces\Processor;
use \AMQPEnvelope;

interface Listener
{
    /**
     * Performs the listening process, attaching itself to a queue and processing messages
     * Depending on the semantics of the implementing class, the consumption is either blocking or non blocking.
     */
    public function listen();

    /**
     * Attaches a processor for the incoming messages
     *
     * @param Processor $processor The processor on which the message gets dispatched once received
     *
     * @return $this
     */
    public function attachProcessor(Processor $processor);

    /**
     * Attaches a monitor to the current listener. The monitors are special handlers that can stp the execution of
     * a listener if conditions are met: memory consumption, total number of messages processed, files changed, etc.
     *
     * @param Monitor $monitor
     *
     * @return $this
     */
    public function attachMonitor(Monitor $monitor);

    /**
     * The method that does the actual message processing. This method is made public so that it can get dispatched as
     * a callback when a message gets received.
     *
     * This method is responsible for dispatching the messages to the processors as well as calling the monitors after
     * every message received
     *
     * @param AMQPEnvelope $message The message received
     * @return bool When the method returns false, the consuming stops
     */
    public function consume(AMQPEnvelope $message);
}
<?php
namespace Amqp\Util\Publisher;

use Amqp\Util\Interfaces\TimeoutProcessor;
use \AMQPExchange,
    \AMQPQueue,
    \AMQPEnvelope;

class Rpc implements Interfaces\Rpc
{
    /**
     * @var array
     */
    protected $configuration;

    /**
     * @var \AMQPExchange
     */
    protected $exchange;

    /**
     * The timeout base-non-di
     * @var resource
     */
    protected $timeoutBase;

    /**
     * List of current events attached to the current base-non-di
     * @var resource[]
     */
    protected $timeoutEvents = array();

    /**
     * The list of processors for the current rpc implementation
     *
     * @var \Amqp\Util\Interfaces\TimeoutProcessor[]
     */
    protected $processors = array();

    /**
     * @var bool
     */
    protected $waitingForAnswer = false;

    /**
     * @var AMQPQueue
     */
    protected $replyQueue;

    public function __construct(AMQPExchange $exchange)
    {
        $this->exchange = $exchange;
    }

    /**
     * {@inheritdoc}
     */
    public function publish($message, $routingKey = '', array $properties = array())
    {
        $this->replyQueue = $this->declareAnonQueue();

        // retrieve the queue name
        $queueName = $this->replyQueue->getName();

        $properties['reply_to'] = $queueName;
        if (!isset($properties['timestamp'])) {
            $properties['timestamp'] = microtime(true);
        }

        $response = $this->exchange->publish($message, $routingKey, AMQP_NOPARAM, $properties);

        if ($response == false) {
            throw new Exception('Message not published!');
        }

        // loop
        if (isset($this->configuration['timeout'])) {
            $this->initTimeout();
        }

        $this->waitingForAnswer = true;
        $message = $this->getMessage($this->replyQueue);

        $this->notify($message);
    }

    /**
     * Function that gets called when a timeout occurs. Notifies all the processor of the failed status and stops
     * listening for incoming messages
     */
    public function timeout()
    {
        foreach ($this->processors as $processor) {
            $processor->timeout();
        }

        $this->waitingForAnswer = false;
    }

    /**
     * Attach a processor to be notified when message succeeds or fails
     *
     * @param TimeoutProcessor $processor
     *
     * @return bool
     */
    public function attach(TimeoutProcessor $processor)
    {
        $this->processors[] = $processor;
        return true;
    }

    /**
     * Notify all the processors of message arriving
     * We do not care what the processor answers
     *
     * @param \AMQPEnvelope $message
     */
    protected function notify(\AMQPEnvelope $message)
    {
        foreach ($this->processors as $processor) {
            $processor->process($message);
        }
    }

    /**
     * Initialize the timeout for the current listener.
     * When the event gets triggered, the method `timeout` from the current instance gets called
     *
     * @return bool
     */
    protected function initTimeout()
    {
        if (!isset($this->configuration['timeout']['timeout'])) {
            return false;
        }

        // use libevent to trigger the timeout
        $base = event_base_new();
        $event = event_new();

        // call timeout method on current object
        event_set($event, 0, EV_TIMEOUT, array($this, 'timeout'));
        event_base_set($event, $base);

        event_add($event, $this->configuration['timeout']['timeout']);
        event_base_loop($base);

        return true;
    }

    /**
     * Cancels all the events and the base-non-di for the events in case we received a message before the events get called
     *
     * @return bool
     */
    protected function disableTimeout()
    {
        foreach ($this->timeoutEvents as $place => $event) {
            event_del($event);
            unset($this->timeoutEvents[$place]);
        }

        // cancel the base-non-di as well
        if (!is_null($this->timeoutBase)) {
            event_base_free($this->timeoutBase);
            $this->timeoutBase = null;
        }

        return true;
    }

    /**
     * @param $queue
     *
     * @return mixed
     */
    protected function getMessage(AMQPQueue $queue)
    {
        $receivedMessage = false;
        while ($this->waitingForAnswer) {
            $receivedMessage = $queue->get();
            if ($receivedMessage == false) {
                continue;
            } else {
                break;
            }
        }

        $this->disableTimeout();

        return $receivedMessage;
    }

    /**
     * @return AMQPQueue
     */
    protected function declareAnonQueue()
    {
        // first declare the queue that we need for reply
        $queue = new AMQPQueue($this->exchange->getChannel());

        // declare an anon queue
        $queue->setFlags(AMQP_EXCLUSIVE);
        $queue->declareQueue();

        return $queue;
    }

    /**
     * {@inheritdoc}
     */
    public function setConfiguration(array $configuration)
    {
        $this->configuration = $configuration;

        return $this;
    }
}
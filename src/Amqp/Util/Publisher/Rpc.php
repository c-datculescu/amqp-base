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
     * The processor responsible for the current operation
     *
     * @var \Amqp\Util\Interfaces\TimeoutProcessor
     */
    protected $processor;

    /**
     * @var bool
     */
    protected $waitingForAnswer = false;

    /**
     * @var AMQPQueue
     */
    protected $replyQueue;

    /**
     * When did the operation begin
     * @var int
     */
    protected $startTime = 0;

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
            $this->startTime = time();
        }

        $this->waitingForAnswer = true;
        $message = $this->getMessage($this->replyQueue);

        if ($message instanceof AMQPEnvelope) {
            $this->notify($message);
        }
    }

    /**
     * Function that gets called when a timeout occurs. Notifies all the processor of the failed status and stops
     * listening for incoming messages
     */
    public function timeout()
    {
        $this->processor->timeout();

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
        $this->processor = $processor;
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
        $this->processor->process($message);
    }

    /**
     * Attempts to consume a message from the reply queue
     *
     * @param AMQPQueue $queue The reply queue
     *
     * @return bool|AMQPEnvelope
     */
    protected function getMessage(AMQPQueue $queue)
    {
        $receivedMessage = false;
        while ($this->waitingForAnswer) {

            // check if a timeout is defined
            if (isset($this->configuration['timeout']['timeout'])) {
                // do we need to stop listening?
                $difference = time() - $this->startTime;

                if ($difference > $this->configuration['timeout']['timeout']) {
                    $this->timeout();
                    return $receivedMessage;
                }
            }

            $receivedMessage = $queue->get();
            if ($receivedMessage == false) {
                continue;
            } else {
                break;
            }
        }

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

    /**
     * {@inheritdoc}
     */
    public function setExchange(\AMQPExchange $exchange)
    {
        $this->exchange = $exchange;

        return $this;
    }
}
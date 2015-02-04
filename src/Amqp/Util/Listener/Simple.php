<?php
namespace Amqp\Util\Listener;

use Amqp\Util\Monitor\Interfaces\Monitor;
use Amqp\Util\Listener\Interfaces\Listener;
use Amqp\Util\Interfaces\Processor;

use \AMQPQueue,
    \AMQPEnvelope;

class Simple implements Listener
{
    /**
     * @var array
     */
    protected $configuration = array();

    /**
     * @var Processor
     */
    protected $processor = array();

    /**
     * @var Monitor[]
     */
    protected $monitors = array();

    /**
     * @var AMQPQueue
     */
    protected $queue;

    /**
     * @var int
     */
    protected $nackCounter = 0;

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
    public function setQueue(\AMQPQueue $queue)
    {
        $this->queue = $queue;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function listen()
    {
        // start listening on the queue
        $this->queue->consume(array($this, 'consume'));
    }

    /**
     * {@inheritdoc}
     */
    public function setProcessor(Processor $processor)
    {
        $this->processor = $processor;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function attachMonitor(Monitor $monitor)
    {
        $this->monitors[] = $monitor;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function consume(AMQPEnvelope $message)
    {
        $stopOnError = $this->configuration['onProcessError'];
        $bulkAck = $this->configuration['bulkAck'];

        $stopListener = false;
        $isProcessed = false;

        if ($bulkAck > 0) {
            $this->nackCounter++;
        }

        // message received, notify all the processors about it
        $result = $this->processor->process($message);
        if ($result === false && $stopOnError != 'continue') {
            switch ($stopOnError) {
                case 'requeue':
                    // nack the message and requeue it
                    // @TODO check how to deal with successive requeues until we hit the limit imposed
                    $this->queue->nack($message->getDeliveryTag(), AMQP_REQUEUE);
                    break;
                case 'error':
                    // nack the message, most likely should go to an error queue
                    if ($bulkAck != 0) {
                        $this->queue->nack($message->getDeliveryTag(), AMQP_MULTIPLE);
                        $this->nackCounter = 0;
                    } else {
                        $this->queue->nack($message->getDeliveryTag());
                    }

                    break;
                case 'stop':
                    // ack the message
                    if ($bulkAck != 0) {
                        $this->queue->ack($message->getDeliveryTag(), AMQP_MULTIPLE);
                        $this->nackCounter = 0;
                    } else {
                        $this->queue->ack($message->getDeliveryTag());
                    }
                    $stopListener = true;
                    break;
            }

            $isProcessed = true;
        }


        if ($isProcessed === false) {
            if ($bulkAck != 0 && $this->nackCounter === $bulkAck) {
                $this->queue->ack($message->getDeliveryTag(), AMQP_MULTIPLE);
                $this->nackCounter = 0;
            } else if ($bulkAck == 0) {
                $this->queue->ack($message->getDeliveryTag());
            }
        }

        // notify all the watchers
        foreach ($this->monitors as $monitor) {
            $res = $monitor->check($this);

            // if the monitor returns false, we need to stop the current listener
            if ($res == false) {
                $stopListener = true;
            }
        }

        // stop the listening process
        if ($stopListener == true) {
            return false;
        }

        return true;
    }
}
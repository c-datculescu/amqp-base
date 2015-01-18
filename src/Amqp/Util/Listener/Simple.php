<?php
namespace Amqp\Util\Listener;

use Amqp\Util\Interfaces\Monitor;
use Amqp\Util\Listener\Interfaces\Listener;
use Amqp\Base\Builder\Amqp;
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
     * @var Amqp
     */
    protected $builder;

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

    public function __construct(array $configuration, Amqp $builder)
    {
        $this->configuration = $configuration;
        $this->builder = $builder;
    }

    /**
     * {@inheritdoc}
     */
    public function listen()
    {
        if (!isset($this->configuration['queue'])) {
            throw new \Exception("No queue defined for listening on!");
        }

        $this->queue = $this->builder->queue($this->configuration['queue']);

        // start listening on the queue
        $this->queue->consume(array($this, 'consume'));
    }

    /**
     * {@inheritdoc}
     */
    public function attachProcessor(Processor $processor)
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

        $stopListener = false;
        $isProcessed = false;

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
                    $this->queue->nack($message->getDeliveryTag());
                    break;
                case 'stop':
                    // ack the message
                    $this->queue->ack($message->getDeliveryTag());
                    $stopListener = true;
                    break;
            }

            $isProcessed = true;
        }

        if ($isProcessed === false) {
            $this->queue->ack($message->getDeliveryTag());
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
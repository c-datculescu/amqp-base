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
        $bulkAck = $this->configuration['bulkAck'];

        $stopListener = false;
        $isProcessed = false;

        if ($bulkAck > 0) {
            $this->nackCounter++;
        }

        // message received, notify all the processors about it
        $result = $this->processor->process($message);

        if ($result !== Processor::OK && $result !== true) {
            $this->processError($message, $result);
            $isProcessed = true;
        }

        if ($isProcessed === false) {
            if ($bulkAck != 0 && $this->nackCounter >= $bulkAck) {
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

    /**
     * Implements all the needed behaviors for processing messages in case of errors
     * This method will nack all the provious messages or ack all the previous messages in the case of a failed message
     * The results can be surprising.
     *
     * @param AMQPEnvelope $message The message to be processed
     * @param bool|int     $result  The reply from the processor
     *
     * @return void
     */
    protected function processError(\AMQPEnvelope $message, $result)
    {
        if (isset($this->configuration['onProcessError'])) {
            $stopOnError = $this->configuration['onProcessError'];

            // set up a sensible default
            $action = 'error';

            switch ($result) {
                case Processor::CRIT_INTERNAL_SERVER_ERROR:
                    if (isset($stopOnError['crit_internal_server_error'])) {
                        $action = $stopOnError['crit_internal_server_error'];
                    }
                    break;
                case Processor::CRIT_NOT_IMPLEMENTED:
                    if (isset($stopOnError['crit_not_implemented'])) {
                        $action = $stopOnError['crit_not_implemented'];
                    }
                    break;
                case Processor::ERR_BAD_REQUEST:
                    if (isset($stopOnError['err_bad_request'])) {
                        $action = $stopOnError['err_bad_request'];
                    }
                    break;
                case Processor::ERR_NOT_FOUND:
                    if (isset($stopOnError['err_not_found'])) {
                        $action = $stopOnError['err_not_found'];
                    }
                    break;
            }

            switch ($action) {
                case 'error':
                    $this->queue->nack($message->getDeliveryTag(), AMQP_MULTIPLE);
                    $this->nackCounter = 0;
                    break;
                case 'requeue':
                    $this->queue->nack($message->getDeliveryTag(), AMQP_REQUEUE | AMQP_MULTIPLE);
                    $this->nackCounter = 0;
                    break;
                case 'stop':
                    $this->queue->nack($message->getDeliveryTag(), AMQP_MULTIPLE);
                    exit(1);
                case 'continue':
                default:
                    $this->queue->ack($message->getDeliveryTag(), AMQP_MULTIPLE);
                    // reset the nack counter
                    $this->nackCounter = 0;
                    break;
            }
        } else {
            $this->queue->nack($message->getDeliveryTag());
        }
    }
}

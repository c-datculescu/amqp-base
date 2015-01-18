<?php
namespace Amqp\Util\Listener;

use \AMQPEnvelope;

class Rpc extends Simple
{
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

        $this->publishResponse($message, $result);

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

    /**
     * Responds to an rpc call. The response is broadcasted on the default exchange of the incoming queue, in order to
     * avoid publishing on the wrong exchange and never receiving the answer
     *
     * @param AMQPEnvelope $message  The message to be processed
     * @param bool         $response The response that was retrieved from the processor
     *
     * @throws \Exception If the response cannot be republished
     */
    protected function publishResponse(AMQPEnvelope $message, $response = false)
    {
        // get the queue's channel
        $channel = $this->queue->getChannel();
        $exchange = new \AMQPExchange($channel);

        // publish on exchange the response message
        $result = $exchange->publish($response, $message->getReplyTo());

        if ($result === false) {
            throw new \Exception('Cannot publish response on reply queue!');
        }
    }
}
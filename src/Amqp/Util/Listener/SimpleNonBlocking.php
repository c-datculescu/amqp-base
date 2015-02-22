<?php

namespace Amqp\Util\Listener;

use Amqp\Util\Interfaces\Processor;

class SimpleNonBlocking extends Simple
{
    /**
     * {@inheritdoc}
     */
    public function listen()
    {
        while(true) {
            $message = $this->queue->get();
            if ($message instanceof \AMQPEnvelope) {
                $this->consume($message);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function consume(\AMQPEnvelope $message)
    {
        // notify the processor of the incoming message
        $result = $this->processor->process($message);

        if ($result !== Processor::OK && $result !== true) {
            $this->processError($message, $result);
        }
    }
}
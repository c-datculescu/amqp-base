<?php
namespace Amqp\Util\Interfaces;

use \AMQPEnvelope;

interface Processor
{
    /**
     * The current method gets called when a new message arrives
     *
     * @param \AMQPEnvelope $message The message that arrived
     *
     * @return bool
     */
    public function process(AMQPEnvelope $message);
}
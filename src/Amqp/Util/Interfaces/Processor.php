<?php
namespace Amqp\Util\Interfaces;

use \AMQPEnvelope;

interface Processor
{
    // the processing on the application side was ok
    const OK                            = 200;

    // errors happened during the transformation layer (before the message is even
    // parsed by the application)
    const ERR_BAD_REQUEST               = 400;

    // there is no registered parser from the application side for this type
    // of message
    const ERR_NOT_FOUND                 = 404;

    // the message reached application level, but there was an error in processing it
    const CRIT_INTERNAL_SERVER_ERROR    = 500;

    // the message reached the application level, but the application does not know
    // how to process the message
    const CRIT_NOT_IMPLEMENTED          = 501;

    /**
     * The current method gets called when a new message arrives
     *
     * @param \AMQPEnvelope $message The message that arrived
     *
     * @return bool|int
     */
    public function process(AMQPEnvelope $message);
}
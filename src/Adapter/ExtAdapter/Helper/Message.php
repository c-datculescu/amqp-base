<?php

namespace Amqp\Adapter\ExtAdapter\Helper;

use Amqp\Message\Message as BaseMessage;

class Message
{
    /**
     * Convert AMQP message to internal message format
     *
     * @param \AMQPEnvelope $envelope
     *
     * @return BaseMessage
     */
    public static function convert(\AMQPEnvelope $envelope)
    {
        $message = new BaseMessage();
        $message->setPayload($envelope->getBody())
            ->setDeliveryMode($envelope->getDeliveryMode())
            ->setHeaders($envelope->getHeaders())
            ->setProperties([
                'content_type'     => $envelope->getContentType(),
                'content_encoding' => $envelope->getContentEncoding(),
                'app_id'           => $envelope->getAppId(),
                'correlation_id'   => $envelope->getCorrelationId(),
                'delivery_tag'     => $envelope->getDeliveryTag(),
                'message_id'       => $envelope->getMessageId(),
                'priority'         => $envelope->getPriority(),
                'reply_to'         => $envelope->getReplyTo(),
                'routing_key'      => $envelope->getRoutingKey(),
                'exchange_name'    => $envelope->getExchangeName(),
                'timestamp'        => $envelope->getTimeStamp(),
                'type'             => $envelope->getType(),
                'user_id'          => $envelope->getUserId()
            ])
        ;

        return $message;
    }
}
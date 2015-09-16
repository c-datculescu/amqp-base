<?php

namespace Amqp\Adapter\Amqplib\Helper;

use Amqp\Message\MessageInterface;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;

class Message
{
    /**
     * @param MessageInterface $message
     * @return AMQPMessage
     */
    public static function toAMQPMessage(MessageInterface $message)
    {
        $deliveryMode = $message->getDeliveryMode();
        $properties = $message->getProperties();
        $properties['application_headers'] = new AMQPTable($message->getHeaders());
        $properties['delivery_mode'] =  $deliveryMode ?: MessageInterface::DELIVERY_MODE_PERSISTENT; // default: durable

        return new AMQPMessage($message->getPayload(), $properties);
    }

    /**
     * @param AMQPMessage $amqpMessage The message to convert
     *
     * @return MessageInterface
     */
    public static function toMessage(AMQPMessage $amqpMessage)
    {
        $properties = array(
            'content_type',
            'content_encoding',
            'app_id',
            'correlation_id',
            'delivery_tag',
            'message_id',
            'priority',
            'reply_to',
            'routing_key',
            'exchange_name',
            'timestamp',
            'type',
            'user_id'
        );

        $propertyValues = array_map(
            function ($propertyName) use ($amqpMessage) {
                if ($amqpMessage->has($propertyName)) {
                    return $amqpMessage->get($propertyName);
                }

                return false;
            },
            $properties
        );

        $headers = $amqpMessage->has('application_headers') ? $amqpMessage->get('application_headers')->getNativeData() : array();

        $message = new \Amqp\Message();
        $message->setPayload($amqpMessage->body)
            ->setDeliveryMode($amqpMessage->get('delivery_mode'))
            ->setHeaders($headers)
            ->setProperties(array_combine($properties, $propertyValues));

        return $message;
    }
}
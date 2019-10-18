<?php
namespace Amqp\Util\Publisher\Interfaces;

interface Publisher
{
    /**
     * Publishes a message to an AMQP broker along with all the properties and headers needed
     * The properties can contain one of the following keys:
     *  - content_type
     *  - content_encoding
     *  - priority
     *  - correlation_id
     *  - reply_to
     *  - expiration
     *  - message_id
     *  - timestamp
     *  - type
     *  - user_id
     *  - app_id
     *  - cluster_id
     *  - headers
     * The headers can contain any custom properties, but the properties ar fixed to the current specified ones
     * If the timestamp the publisher will fill them in
     *
     * @param string $message    The message to be broadcasted
     * @param string $routingKey The routing key for routing the message
     * @param array  $properties The properties of the message
     *
     * @return bool
     */
    public function publish($message, $routingKey = '', array $properties = array());

    /**
     * Sets the configuration for the current publisher
     *
     * @param array $configuration The configuration for the current publisher
     *
     * @return $this
     */
    public function setConfiguration(array $configuration);

    /**
     * Sets the current exchange to publish to
     *
     * @param \AMQPExchange $exchange Exchange
     *
     * @return mixed
     */
    public function setExchange(\AMQPExchange $exchange);
    
    /**
     * Gets the current exchange to publish to
     *
     * @return \AMQPExchange
     */
    public function getExchange(): \AMQPExchange;
}

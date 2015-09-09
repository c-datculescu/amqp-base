<?php

namespace Amqp;

use Amqp\Message\MessageInterface;

class Message implements MessageInterface
{
    /**
     * The message payload
     * @var string
     */
    protected $payload;

    /**
     * The message properties
     * @var array
     */
    protected $properties = [];

    /**
     * The message delivery mode
     * @var int
     */
    protected $deliveryMode = self::DELIVERY_MODE_PERSISTENT;

    /**
     * The message headers
     * @var array
     */
    protected $headers = [];

    /**
     * Sets the payload for the current message
     *
     * @param string $payload Payload for the current message
     *
     * @return $this
     */
    public function setPayload($payload)
    {
        $this->payload = $payload;

        return $this;
    }

    /**
     * Returns the payload for the current message
     *
     * @return string
     */
    public function getPayload()
    {
        return $this->payload;
    }

    /**
     * Sets the properties for the current message
     *
     * @param array $properties The properties for the current message
     *
     * @return $this
     */
    public function setProperties(array $properties = [])
    {
        $this->properties = $properties;

        return $this;
    }

    /**
     * Returns the properties for the current message
     *
     * @return array
     */
    public function getProperties()
    {
        return $this->properties;
    }

    /**
     * Sets the delivery mode for the current message
     *
     * @param int $deliveryMode The delivery mode for the current message
     *
     * @return $this
     */
    public function setDeliveryMode($deliveryMode)
    {
        $this->deliveryMode = $deliveryMode;

        return $this;
    }

    /**
     * Get the delivery mode for the current message
     *
     * @return int
     */
    public function getDeliveryMode()
    {
        return $this->deliveryMode;
    }

    /**
     * Sets the headers for the current message
     *
     * @param array $headers The headers for the current message
     *
     * @return $this
     */
    public function setHeaders(array $headers = array())
    {
        $this->headers = $headers;

        return $this;
    }

    /**
     * Retrieves the headers from the current message
     *
     * @return array
     */
    public function getHeaders()
    {
        return $this->headers;
    }
}
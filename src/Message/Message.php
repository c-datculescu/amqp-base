<?php
/**
 * Created by PhpStorm.
 * User: b.begameri
 * Date: 10/09/15
 * Time: 17:33
 */

namespace Amqp\Message;


class Message implements MessageInterface
{

    protected $payload ='';
    protected $headers = [];
    protected $properties = [];
    protected $deliverymode = 2;

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
        $this->deliverymode = $deliveryMode;
        return $this;
    }

    /**
     * Get the delivery mode for the current message
     *
     * @return int
     */
    public function getDeliveryMode()
    {
        return $this->deliverymode;
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
<?php
namespace Amqp\Message;

interface MessageInterface
{
    /**
     * Non persistent delivery mode
     */
    const DELIVERY_MODE_NONPERSISTENT = 1;

    /**
     * Persistent delivery mode
     */
    const DELIVERY_MODE_PERSISTENT = 2;

    /**
     * Sets the payload for the current message
     *
     * @param string $payload Payload for the current message
     *
     * @return $this
     */
    public function setPayload($payload);

    /**
     * Returns the payload for the current message
     *
     * @return string
     */
    public function getPayload();

    /**
     * Sets the properties for the current message
     *
     * @param array $properties The properties for the current message
     *
     * @return $this
     */
    public function setProperties(array $properties = []);

    /**
     * Returns the properties for the current message
     *
     * @return array
     */
    public function getProperties();

    /**
     * Sets the delivery mode for the current message
     *
     * @param int $deliveryMode The delivery mode for the current message
     *
     * @return $this
     */
    public function setDeliveryMode($deliveryMode);

    /**
     * Get the delivery mode for the current message
     *
     * @return int
     */
    public function getDeliveryMode();

    /**
     * Sets the headers for the current message
     *
     * @param array $headers The headers for the current message
     *
     * @return $this
     */
    public function setHeaders(array $headers = array());

    /**
     * Retrieves the headers from the current message
     *
     * @return array
     */
    public function getHeaders();
}
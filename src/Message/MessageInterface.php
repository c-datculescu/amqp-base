<?php
namespace Amqp\Message;

interface MessageInterface
{
    /**
     * Sets the payload for the current message
     *
     * @param string $payload Payload for the current message
     *
     * @return $this
     */
    public function setPayload($payload = '');

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
    public function setProperties(array $properties = array());

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
    public function setDeliveryMode($deliveryMode = 2);

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
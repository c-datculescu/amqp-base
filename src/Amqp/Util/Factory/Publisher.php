<?php
namespace Amqp\Util\Factory;

use Amqp\Base\Config\Processor;
use Amqp\Util\Publisher\Interfaces\Publisher as PublisherInterface;

class Publisher
{
    /**
     * Configuration
     * @var array
     */
    protected $configuration = array();

    /**
     * @param Processor $processor The processor for the configuration
     */
    public function __construct(Processor $processor)
    {
        $this->configuration = $processor->getDefinition(new \Amqp\Util\Config\Publisher());
    }

    /**
     * Sets the configuration on the delivered publisher and returns it to the caller ready to be used
     *
     * @param PublisherInterface $publisherType An instance of publisher to be used
     * @param string             $name          The name of the publisher from the configuration
     *
     * @return PublisherInterface
     */
    public function publisher(PublisherInterface $publisherType, $name)
    {
        if (!isset($this->configuration['publisher'][$name])) {
            throw new \UnexpectedValueException("Cannot locate publisher definition for " . $name . "!");
        }

        $publisherType->setConfiguration($this->configuration['publisher'][$name]);

        return $publisherType;
    }
}
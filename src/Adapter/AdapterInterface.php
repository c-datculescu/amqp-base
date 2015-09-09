<?php
namespace Amqp\Adapter;

use Amqp\Consumer\ConsumerInterface;
use Amqp\Publisher\PublisherInterface;

interface AdapterInterface extends ConsumerInterface, PublisherInterface
{

}
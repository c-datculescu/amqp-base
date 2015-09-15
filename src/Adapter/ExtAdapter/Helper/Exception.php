<?php

namespace Amqp\Adapter\ExtAdapter\Helper;

use Amqp\Exception as BaseException;
use Amqp\Exception\ChannelException;
use Amqp\Exception\ConnectionException;
use Amqp\Exception\ExchangeException;
use Amqp\Exception\QueueException;

class Exception
{
    /**
     * Convert AMQP extension to internal exception
     * @param \Exception $e
     *
     * @return Exception|ChannelException|ConnectionException|ExchangeException|QueueException|\Exception
     */
    public static function convert(\Exception $e)
    {
        switch(get_class($e)) {
            case 'AMQPException':
                return new BaseException($e->getMessage(), $e->getCode(), $e);
            case 'AMQPConnectionException':
                return new ConnectionException($e->getMessage(), $e->getCode(), $e);
            case 'AMQPChannelException':
                return new ChannelException($e->getMessage(), $e->getCode(), $e);
            case 'AMQPExchangeException':
                return new ExchangeException($e->getMessage(), $e->getCode(), $e);
            case 'AMQPQueueException':
                return new QueueException($e->getMessage(), $e->getCode(), $e);
            default:
                return $e;
        }
    }
}
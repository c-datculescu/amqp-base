<?php

namespace Amqp\Adapter\Amqplib\Helper;

use Amqp\Exception as BaseException;
use Amqp\Exception\ConnectionException;
use Amqp\Exception\ChannelException;
use Amqp\Exception\ExchangeException;

class Exception
{
    /**
     * @param \Exception $e
     * @return \Exception|ChannelException|ConnectionException|ExchangeException|\Exception
     */
    public static function convert(\Exception $e)
    {
        switch(get_class($e)) {
            case 'PhpAmqpLib\Exception\AMQPException':
                return new BaseException($e->getMessage(), $e->getCode(), $e);
            case 'PhpAmqpLib\Exception\AMQPRuntimeException':
            case 'PhpAmqpLib\Exception\AMQPProtocolConnectionException':
                return new ConnectionException($e->getMessage(), $e->getCode(), $e);
            case 'PhpAmqpLib\Exception\AMQPProtocolChannelException':
                return new ChannelException($e->getMessage(), $e->getCode(), $e);
            default:
                return $e;
        }
    }
}
<?php

namespace Amqp\Adapter\ExtAdapter\Helper;

class Options
{
    /**
     * Options <-> flags mapping
     * @var array
     */
    protected static $map = [
        'durable'     => AMQP_DURABLE,
        'passive'     => AMQP_PASSIVE,
        'exclusive'   => AMQP_EXCLUSIVE,
        'auto_delete' => AMQP_AUTODELETE,
        'auto_ack'    => AMQP_AUTOACK,
        'multi_ack'   => AMQP_MULTIPLE
    ];

    /**
     * Convert options to flags
     *
     * @param array $options Options
     *
     * @return int
     */
    public static function toFlags(array $options = [])
    {
        return array_sum(array_values(array_intersect_key(self::$map, array_filter($options))));
    }
}
<?php
namespace Amqp\Util\Interfaces;

interface LimitMonitor extends Monitor
{
    /**
     * Sets the current limit we need to watch against when checking
     *
     * @param mixed $limit The limit to be applied when calling this type of monitor
     */
    public function setLimit($limit);
}
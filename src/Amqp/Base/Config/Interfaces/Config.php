<?php
namespace Amqp\Base\Config\Interfaces;

interface Config
{
    /**
     * Returns the confgiguration encoded by the current class in the form of an aray
     *
     * @return array
     */
    public function getConfig();
}
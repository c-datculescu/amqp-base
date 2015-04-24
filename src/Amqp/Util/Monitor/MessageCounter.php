<?php
namespace Amqp\Util\Monitor;

use Amqp\Util\Monitor\Interfaces\LimitMonitor;
use Amqp\Util\Listener\Interfaces\Listener;

class MessageCounter implements LimitMonitor
{
    protected $limit = 0;

    protected $counter = 0;

    /**
     * {@inheritdoc}
     */
    public function setLimit($limit)
    {
        $this->limit = (int) $limit;
    }

    /**
     * {@inheritdoc}
     */
    public function check(Listener $listener)
    {
        // disable limit for lower than or equal to 0
        if ($this->limit <= 0) {
            return true;
        }

        // we just received another message, increase the internal counter
        $this->counter++;

        if ($this->counter > $this->limit) {
            return false;
        }

        return true;
    }
}
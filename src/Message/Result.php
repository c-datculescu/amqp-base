<?php

namespace Amqp\Message;

class Result
{
    protected $status = false;

    protected $stop = false;

    protected $requeue = false;

    public function ack()
    {
        $this->status = true;
        return $this;
    }

    public function nack()
    {
        $this->status = false;
        return $this;
    }

    public function requeue()
    {
        $this->requeue = true;
        return $this;
    }

    public function stop()
    {
        $this->stop = true;
        return $this;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function isStop()
    {
        return $this->stop;
    }

    public function isRequeue()
    {
        return $this->requeue;
    }
}
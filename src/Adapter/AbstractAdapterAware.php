<?php

namespace Amqp\Adapter;

abstract class AbstractAdapterAware implements AdapterAwareInterface
{
    /**
     * @var AdapterInterface
     */
    protected $adapter = null;

    /**
     * @param AdapterInterface $adapter
     * @return $this
     */
    public function setAdapter(AdapterInterface $adapter)
    {
        $this->adapter = $adapter;
        return $this;
    }

    /**
     * @return AdapterInterface
     */
    public function getAdapter()
    {
        return $this->adapter;
    }
}
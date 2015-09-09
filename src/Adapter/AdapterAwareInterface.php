<?php

namespace Amqp\Adapter;

interface AdapterAwareInterface
{
    /**
     * @param AdapterInterface $adapter
     */
    public function setAdapter(AdapterInterface $adapter);

    /**
     * @return AdapterInterface
     */
    public function getAdapter();
}
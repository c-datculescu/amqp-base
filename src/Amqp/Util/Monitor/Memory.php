<?php
namespace Amqp\Util\Monitor;

use Amqp\Util\Monitor\Interfaces\LimitMonitor;
use Amqp\Util\Listener\Interfaces\Listener;

class Memory implements LimitMonitor
{
    const SIZE_B = 1;
    const SIZE_K = 1024;
    const SIZE_M = 1048576;
    const SIZE_G = 1073741824;

    /**
     * @var int
     */
    protected $memoryLimit;

    /**
     * {@inheritdoc}
     */
    public function setLimit($limit)
    {
        // the limit should be in the format 10G, 10M or 10K
        $memoryMultiplier = substr($limit, -1);
        $totalLimit = substr($limit, 0, strlen($limit) - 1);

        $allowedExtensions = array('B', 'K', 'M', 'G');
        if (!in_array($memoryMultiplier, $allowedExtensions)) {
            throw new \Exception('Invalid memory value for memory limit!');
        }

        // get the value of the constant
        $constantName = __CLASS__ . "::SIZE_" . $memoryMultiplier;
        $constantValue = constant($constantName);

        $this->memoryLimit = $totalLimit * $constantValue;
    }

    /**
     * {@inheritdoc}
     */
    public function check(Listener $listener)
    {
        $currentMemory = memory_get_usage(true);

        if ($currentMemory > $this->memoryLimit) {
            return false;
        }

        return true;
    }
}
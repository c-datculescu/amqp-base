<?php
namespace Amqp\Util\Monitor;

use Amqp\Util\Monitor\Interfaces\Monitor;
use Amqp\Util\Listener\Interfaces\Listener;

class FileChange implements Monitor
{
    /**
     * The file path for the control file
     * @var string
     */
    protected $filePath = '';

    /**
     * The old modified time of the control file
     * @var int
     */
    protected $oldMtime = 0;

    /**
     * Sets the current file to be used as control file
     *
     * @param string $file The file used as control file
     */
    public function setFile($file)
    {
        $this->filePath = $file;
    }

    /**
     * {@inheritdoc}
     */
    public function check(Listener $listener)
    {
        if ($this->filePath == '') {
            return true;
        }

        if ($this->oldMtime === 0) {
            $this->oldMtime = $this->readMtime();
            // value was not loaded, we load it. On next call, we check whether the value matches or not
            return true;
        }

        $time = $this->readMtime();
        if ($time !== $this->oldMtime) {
            return false;
        }

        return true;
    }

    /**
     * @return int
     *
     * @throws \Exception If the control file cannot be read or does not exist
     */
    protected function readMtime()
    {
        if (!file_exists($this->filePath) || !is_readable($this->filePath)) {
            throw new Exception("Cannot load control file for checking changes!");
        }

        $modifiedTime = filemtime($this->filePath);

        return $modifiedTime;
    }
}
<?php

namespace ScpCrawler\Updater\Processes;

abstract class BaseTask extends \Spatie\Async\Task
{
    protected $success;
    protected $protocol;
    protected $tempPath;

    public function __construct($protocol, $tempPath)
    {
        $this->protocol = $protocol;
        $this->tempPath = $tempPath;
    }

    protected function saveToFile()
    {
        $filename = uniqid($this->tempPath, true);
        file_put_contents($filename, serialize($this));
        return $filename;
    }

    public function configure()
    {
        $this->success = false;
        \ScpCrawler\Wikidot\Utils::$protocol = $this->protocol;
    }

    public function isSuccess()
    {
        return $this->success;
    }

}
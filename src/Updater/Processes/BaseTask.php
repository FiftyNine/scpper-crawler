<?php

namespace ScpCrawler\Updater\Processes;

abstract class BaseTask extends \Spatie\Async\Task
{
    protected $success;
    protected $protocol;

    public function __construct($protocol = 'http')
    {
        $this->protocol = $protocol;
    }

    protected function saveToFile()
    {
        $filename = uniqid("./shared/", true);
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
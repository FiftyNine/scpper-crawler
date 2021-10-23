<?php

namespace ScpCrawler\Updater\Threads;

class BaseWork extends \Threaded
{
    protected $complete;
    protected $success;

    public function __construct()
    {
        $this->complete = false;
        $this->success = false;
    }

    public function isComplete()
    {
        return $this->complete;
    }

    public function isSuccess()
    {
        return $this->success;
    }
}
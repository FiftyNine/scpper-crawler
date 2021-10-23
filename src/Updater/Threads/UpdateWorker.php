<?php

namespace ScpCrawler\Updater\Threads;

use ScpCrawler\Logger\Logger;

class UpdateWorker extends \Worker
{
    protected $logger;

    public function __construct(Logger $logger) {
        $this->logger = $logger;
    }

    public function getLogger()
    {
        return $this->logger;
    }    
}

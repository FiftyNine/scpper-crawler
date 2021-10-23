<?php

namespace ScpCrawler\Updater\Threads;

use ScpCrawler\Logger\Logger;
use ScpCrawler\Scp\DbUtils\KeepAliveMysqli;

class UsersUpdater extends \ScpCrawler\Updater\UsersUpdater
{
    protected $maxThreads;
    
    public function __construct(KeepAliveMysqli $link, $siteName, \ScpCrawler\Scp\UserList $users, $maxThreads, Logger $logger = null)
    {
        parent::__construct($link, $siteName, $users, $logger);
        $this->maxThreads = $maxThreads;
    }    
    
    // Retrieve all the users
    protected function retrieveUsers()
    {
        $pool = new \Pool($this->maxThreads, UpdateWorker::class, [$this->logger]);
        for ($i = 1; $i <= $this->pageCount; $i++) {
            $pool->submit(new UsersWork($this->siteName, $i));
        }
        $left = $this->pageCount;
        $failed = false;
        while ($left > 0 && !$failed) {
            $pool->collect(
                function(UsersWork $task) use (&$left, &$failed)
                {
                    if ($task->isComplete()) {
                        if ($task->isSuccess()) {
                            $loaded = $this->webList->addMembersFromListPage($task->getPageHtml(), $this->logger);
                            if (intdiv($this->total + $loaded, 1000) > intdiv($this->total, 1000)) {
                                Logger::logFormat(
                                    $this->logger,
                                    "%d members retrieved [%d kb used]...",
                                    [intdiv($this->total + $loaded, 1000)*1000, round(memory_get_usage()/1024)]
                                );
                            }
                            $this->total += $loaded;
                        } else {
                            $failed = true;
                        }
                        $left--;
                        return true;
                    } else {
                        return false;
                    }
                }
            );
        }
        $this->failed = $this->failed || $failed;
    }
}

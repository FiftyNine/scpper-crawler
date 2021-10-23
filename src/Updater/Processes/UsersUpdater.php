<?php

namespace ScpCrawler\Updater\Processes;

use ScpCrawler\Logger\Logger;
use ScpCrawler\Scp\DbUtils\KeepAliveMysqli;

class UsersUpdater extends \ScpCrawler\Updater\UsersUpdater
{
    protected $tempPath;
    protected $maxProcesses;    
    
    public function __construct(KeepAliveMysqli $link, $siteName, \ScpCrawler\Scp\UserList $users, $tempPath, $maxProcesses, Logger $logger = null)
    {
        parent::__construct($link, $siteName, $users, $logger);
        $this->tempPath = $tempPath;
        $this->maxProcesses = $maxProcesses;
    }      
    
    // Retrieve all the users
    protected function retrieveUsers()
    {
        $pool = \Spatie\Async\Pool::create();
        $pool->concurrency($this->maxProcesses);
        for ($i = 1; $i <= $this->pageCount; $i++) {
            $pool->add(new UsersTask($this->siteName, $i, \ScpCrawler\Wikidot\Utils::$protocol, $this->tempPath))
                ->then(function ($filename) {
                    $task = unserialize(file_get_contents($filename));
                    unlink($filename);
                    if (!$task->isSuccess()) {
                        $this->failed = true;
                        return;
                    }
                    $loaded = $this->webList->addMembersFromListPage($task->getPageHtml(), $this->logger);
                    if (intdiv($this->total + $loaded, 1000) > intdiv($this->total, 1000)) {
                        Logger::logFormat(
                            $this->logger,
                            "%d members retrieved [%d kb used]...",
                            [intdiv($this->total + $loaded, 1000)*1000, round(memory_get_usage()/1024)]
                        );
                    }
                    $this->total += $loaded;})
                ->catch(function ($e) {
                    Logger::log($this->logger, 'catch');
                    $this->failed = true;
                    throw $e;})
                ->timeout(function () {
                    Logger::log($this->logger, 'timeout');
                    $this->failed = true;});
        }
        $pool->wait();
    }
}

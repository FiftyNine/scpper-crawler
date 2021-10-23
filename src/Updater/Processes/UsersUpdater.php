<?php

namespace ScpCrawler\Updater\Processes;

use ScpCrawler\Logger\Logger;

class UsersUpdater extends \ScpCrawler\Updater\UsersUpdater
{
    // Retrieve all the users
    protected function retrieveUsers()
    {
        $pool = \Spatie\Async\Pool::create();
        $pool->concurrency(SCP_THREADS);
        for ($i = 1; $i <= $this->pageCount; $i++) {
            $pool->add(new UsersTask($this->siteName, $i, \ScpCrawler\Wikidot\Utils::$protocol))
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

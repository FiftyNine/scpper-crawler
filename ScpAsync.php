<?php

require_once 'ScpUpdater.php';

class ScpAsyncUsersUpdater extends ScpUsersUpdater
{
    // Retrieve all the users
    protected function retrieveUsers()
    {
        $pool = \Spatie\Async\Pool::create();
        $pool->concurrency(SCP_THREADS);
        for ($i = 1; $i <= $this->pageCount; $i++) {
            $pool->add(new ScpAsyncMemberListPageTask($this->siteName, $i, WikidotUtils::$protocol))
                ->then(function ($filename) {
                    $task = unserialize(file_get_contents($filename));
                    unlink($filename);
                    if (!$task->isSuccess()) {
                        $this->failed = true;
                        return;
                    }
                    $loaded = $this->webList->addMembersFromListPage($task->getPageHtml(), $this->logger);
                    if (intdiv($this->total + $loaded, 1000) > intdiv($this->total, 1000)) {
                        WikidotLogger::logFormat(
                            $this->logger,
                            "%d members retrieved [%d kb used]...",
                            [intdiv($this->total + $loaded, 1000)*1000, round(memory_get_usage()/1024)]
                        );
                    }
                    $this->total += $loaded;})
                ->catch(function ($e) {
                    WikidotLogger::log($this->logger, 'catch');
                    $this->failed = true;
                    throw $e;})
                ->timeout(function () {
                    WikidotLogger::log($this->logger, 'timeout');
                    $this->failed = true;});
        }
        $pool->wait();
    }
}

class ScpAsyncPagesUpdater extends ScpPagesUpdater
{
    // Process all the pages
    protected function processPages()
    {
        $pagesByName = [];
        foreach ($this->pages->iteratePages() as $page) {
            $pagesByName[$page->getPageName()] = $page;
        }
        $pool = \Spatie\Async\Pool::create();
        $pool->concurrency(SCP_THREADS);
        // Iterate through all pages and process them one by one
        $prevId = -1;
        $prevRevision = -1;
        for ($i = count($this->sitePages)-1; $i>=0; $i--) {
            $page = $this->sitePages[$i];
            // Optimization: if, after retrieving page information,
            // we see that it's indeed the same page (ids match)
            // and it's the same revision, skip retrieving source and history
            if (isset($pagesByName[$page->getPageName()])) {
                $oldPage = $pagesByName[$page->getPageName()];
                $prevId = $oldPage->getId();
                $prevRevision = $oldPage->getLastRevision();
            }
            $pool->add(new ScpAsyncPageTask($page, $prevId, $prevRevision, WikidotUtils::$protocol))
                ->then(function ($filename) {
                   $task = unserialize(file_get_contents($filename));
                   unlink($filename);
                   $this->processPage($task->getPage(), $task->isSuccess());
                })
                ->catch(function ($e) {
                    $parts = explode("\n\n", $e->getMessage());
                    WikidotLogger::log($this->logger, $parts[0]);})
                ->timeout(function () {
                    WikidotLogger::log($this->logger, 'timeout');
                    $this->failed = true;});
        }
        // Release references to pages so a page would be freed as soon as it's been prcessed
        unset($pagesByName);
        unset($this->sitePages);
        $pool->wait();
    }
}

class ScpAsyncSiteUpdater extends ScpSiteUpdater
{
    protected function getPagesUpdaterClass()
    {
        return 'ScpAsyncPagesUpdater';
    }

    protected function getUsersUpdaterClass()
    {
        return 'ScpAsyncUsersUpdater';
    }
}
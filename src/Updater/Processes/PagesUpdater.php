<?php

namespace ScpCrawler\Updater\Processes;

use ScpCrawler\Logger\Logger;

class PagesUpdater extends \ScpCrawler\Updater\PagesUpdater
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
            $pool->add(new PageTask($page, $prevId, $prevRevision, \ScpCrawler\Wikidot\Utils::$protocol))
                ->then(function ($filename) {
                   $task = unserialize(file_get_contents($filename));
                   unlink($filename);
                   $this->processPage($task->getPage(), $task->isSuccess());
                })
                ->catch(function ($e) {
                    $parts = explode("\n\n", $e->getMessage());
                    Logger::log($this->logger, $parts[0]);})
                ->timeout(function () {
                    Logger::log($this->logger, 'timeout');
                    $this->failed = true;});
        }
        // Release references to pages so a page would be freed as soon as it's been prcessed
        unset($pagesByName);
        unset($this->sitePages);
        $pool->wait();
    }
}
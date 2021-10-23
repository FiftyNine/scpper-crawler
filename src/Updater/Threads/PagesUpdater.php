<?php

namespace ScpCrawler\Updater\Threads;

class PagesUpdater extends \ScpCrawler\Updater\PagesUpdater
{
    // Process all the pages
    protected function processPages()
    {
        $pagesByName = [];
        foreach ($this->pages->iteratePages() as $page) {
            $pagesByName[$page->getPageName()] = $page;
        }               
        $pool = new \Pool(SCP_THREADS, UpdateWorker::class, [$this->logger]);
        // Iterate through all pages and process them one by one
        for ($i = count($this->sitePages)-1; $i>=0; $i--) {
            $prevId = -1;
            $prevRevision = -1;            
            $page = $this->sitePages[$i];
            // Optimization: if, after retrieving page information,
            // we see that it's indeed the same page (ids match)
            // and it's the same revision, skip retrieving source and history
            if (isset($pagesByName[$page->getPageName()])) {
                $oldPage = $pagesByName[$page->getPageName()];
                $prevId = $oldPage->getId();
                $prevRevision = $oldPage->getLastRevision();
            }            
            $pool->submit(new PageWork($page, $prevId, $prevRevision));
        }
        $left = count($this->sitePages);
        unset($pagesByName);
        unset($this->sitePages);
        while ($left > 0) {
            $pool->collect(
                function(PageWork $task) use (&$left)
                {
                    if ($task->isComplete()) {
                        $this->processPage($task->getPage(), $task->isSuccess());
                        $left--;
                        return true;
                    } else {
                        return false;
                    }
                }
            );
        }
    }
}
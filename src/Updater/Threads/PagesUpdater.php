<?php

namespace ScpCrawler\Updater\Threads;

use ScpCrawler\Logger\Logger;
use ScpCrawler\Scp\DbUtils\KeepAliveMysqli;

class PagesUpdater extends \ScpCrawler\Updater\PagesUpdater
{
    protected $maxThreads;
    
    public function __construct(KeepAliveMysqli $link, $siteId, \ScpCrawler\Scp\PageList $pages, $maxThreads, Logger $logger = null, \ScpCrawler\Scp\UserList $users = null)
    {
        parent::__construct($link, $siteId, $pages, $logger, $users);
        $this->maxThreads = $maxThreads;
    }    
    
    // Process all the pages
    protected function processPages()
    {
        $pagesByName = [];
        foreach ($this->pages->iteratePages() as $page) {
            $pagesByName[$page->getPageName()] = $page;
        }               
        $pool = new \Pool($this->maxThreads, UpdateWorker::class, [$this->logger]);
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
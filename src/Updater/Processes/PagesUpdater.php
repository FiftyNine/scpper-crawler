<?php

namespace ScpCrawler\Updater\Processes;

use ScpCrawler\Logger\Logger;
use ScpCrawler\Scp\DbUtils\KeepAliveMysqli;

class PagesUpdater extends \ScpCrawler\Updater\PagesUpdater
{
    protected $tempPath;
    protected $maxProcesses;
    
    public function __construct(KeepAliveMysqli $link, $siteId, \ScpCrawler\Scp\PageList $pages, $tempPath, $maxProcesses, Logger $logger = null, \ScpCrawler\Scp\UserList $users = null)
    {
        parent::__construct($link, $siteId, $pages, $logger, $users);
        $this->tempPath = $tempPath;
        $this->maxProcesses = $maxProcesses;
    }  
    
    // Process all the pages
    protected function processPages()
    {
        $pagesByName = [];
        foreach ($this->pages->iteratePages() as $page) {
            $pagesByName[$page->getPageName()] = $page;
        }
        $pool = \Spatie\Async\Pool::create();
        $pool->concurrency($this->maxProcesses);
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
            $pool->add(new PageTask($page, $prevId, $prevRevision, \ScpCrawler\Wikidot\Utils::$protocol, $this->tempPath))
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
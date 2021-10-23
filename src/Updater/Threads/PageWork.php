<?php

namespace ScpCrawler\Updater\Threads;

class PageWork extends BaseWork
{
    protected $page;    
    protected $prevId;
    protected $prevRevision;

    public function __construct(\ScpCrawler\Scp\Page $page, $prevId, $prevRevision)
    {
        parent::__construct();
        $this->page = $page;
        $this->prevId = $prevId;
        $this->prevRevision = $prevRevision;
    }

    public function run()
    {
        $logger = $this->worker->getLogger();
        $page = $this->page;
        if (!$page->retrievePageInfo($logger)) {
            $this->complete = true;
            return;
        }
        $this->success = $page->retrievePageVotes($logger);
        if (($page->getId() != $this->prevId) || ($page->getLastRevision() != $this->prevRevision)) {
            $this->success = $this->success
                && $page->retrievePageHistory($logger) 
                && $page->retrievePageSource($logger);
        }
        $this->page = $page;
        $this->complete = true;
    }

    public function getPage()
    {
        return $this->page;
    }
}
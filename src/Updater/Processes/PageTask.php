<?php

namespace ScpCrawler\Updater\Processes;

class PageTask extends BaseTask
{
    protected $page;
    protected $prevId;
    protected $prevRevision;

    public function __construct(\ScpCrawler\Scp\Page $page, $prevId, $prevRevision, $protocol = 'http')
    {
        parent::__construct($protocol);
        $this->page = $page;
        $this->prevId = $prevId;
        $this->prevRevision = $prevRevision;
    }

    public function run()
    {
        $logger = new \ScpCrawler\Logger\ExceptionLogger();
        $page = $this->page;
        if ($page->retrievePageInfo($logger)) {
            $this->success = $page->retrievePageVotes($logger);
            if (($page->getId() != $this->prevId) || ($page->getLastRevision() != $this->prevRevision)) {
                $this->success = $this->success
                    && $page->retrievePageHistory($logger)
                    && $page->retrievePageSource($logger);
            }
        }
        return $this->saveToFile();
    }

    public function getPage()
    {
        return $this->page;
    }
}
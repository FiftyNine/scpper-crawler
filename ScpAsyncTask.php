<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

require_once "ScpCrawler.php";

abstract class ScpAsyncTask extends \Spatie\Async\Task
{
    protected $success;
    protected $protocol;

    public function __construct($protocol = 'http')
    {
        $this->protocol = $protocol;
    }

    protected function saveToFile()
    {
        $filename = uniqid("./shared/", true);
        file_put_contents($filename, serialize($this));
        return $filename;
    }

    public function configure()
    {
        $this->success = false;
        WikidotUtils::$protocol = $this->protocol;
    }

    public function isSuccess()
    {
        return $this->success;
    }

}

class ScpAsyncPageTask extends ScpAsyncTask
{
    protected $page;
    protected $prevId;
    protected $prevRevision;

    public function __construct(ScpPage $page, $prevId, $prevRevision, $protocol = 'http')
    {
        parent::__construct($protocol);
        $this->page = $page;
        $this->prevId = $prevId;
        $this->prevRevision = $prevRevision;
    }

    public function run()
    {
        $logger = new WikidotExceptionLogger();
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

class ScpAsyncMemberListPageTask extends ScpAsyncTask
{
    protected $siteName;
    protected $pageIndex;
    protected $pageHtml;

    public function __construct($siteName, $pageIndex, $protocol = 'http')
    {
        parent::__construct($protocol);
        $this->siteName = $siteName;
        $this->pageIndex = $pageIndex;
    }

    public function run()
    {
        $logger = new WikidotExceptionLogger();
        $args = ['page' => $this->pageIndex];
        $html = null;
        $status = WikidotUtils::requestModule($this->siteName, 'membership/MembersListModule', 0, $args, $html, $logger);
        if ($status === WikidotStatus::OK) {
            $this->pageHtml = $html;
            $this->success = true;
        }
        return $this->saveToFile();
    }

    public function getPageHtml()
    {
        return $this->pageHtml;
    }
}

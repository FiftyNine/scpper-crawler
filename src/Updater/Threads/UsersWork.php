<?php

namespace ScpCrawler\Updater\Threads;

class UsersWork extends BaseWork
{
    protected $siteName;
    protected $pageIndex;
    protected $pageHtml;

    public function __construct($siteName, $pageIndex)
    {
        parent::__construct();
        $this->siteName = $siteName;
        $this->pageIndex = $pageIndex;
    }

    public function run()
    {
        $logger = $this->worker->getLogger();
        $args = ['page' => $this->pageIndex];
        $html = null;
        try {
            $status = \ScpCrawler\Wikidot\Utils::requestModule($this->siteName, 'membership/MembersListModule', 0, $args, $html, $logger);
            if ($status === \ScpCrawler\Wikidot\PageStatus::OK) {
                $this->pageHtml = $html;
                $this->success = true;
            }
        } finally {
            $this->complete = true;
        }
    }

    public function getPageHtml()
    {
        return $this->pageHtml;
    }
}
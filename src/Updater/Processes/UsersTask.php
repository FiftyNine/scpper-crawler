<?php

namespace ScpCrawler\Updater\Processes;

class UsersTask extends BaseTask
{
    protected $siteName;
    protected $pageIndex;
    protected $pageHtml;

    public function __construct($siteName, $pageIndex, $protocol, $tempPath)
    {
        parent::__construct($protocol, $tempPath);
        $this->siteName = $siteName;
        $this->pageIndex = $pageIndex;
    }

    public function run()
    {
        $logger = new \ScpCrawler\Logger\ExceptionLogger();
        $args = ['page' => $this->pageIndex];
        $html = null;
        $status = \ScpCrawler\Wikidot\Utils::requestModule($this->siteName, 'membership/MembersListModule', 0, $args, $html, $logger);
        if ($status === \ScpCrawler\Wikidot\PageStatus::OK) {
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
<?php

namespace ScpCrawler\Updater\Threads;

use ScpCrawler\Logger\Logger;
use ScpCrawler\Scp\DbUtils\KeepAliveMysqli;

class SiteUpdater extends \ScpCrawler\Updater\SiteUpdater
{        
    protected $maxThreads;
    
    protected function createUsersUpdater(KeepAliveMysqli $link, $siteName, \ScpCrawler\Scp\UserList $users, Logger $logger = null)
    {
        return new \ScpCrawler\Updater\Threads\UsersUpdater($link, $siteName, $users, $this->maxThreads, $logger);
    }

    protected function createPagesUpdater(KeepAliveMysqli $link, $siteId, \ScpCrawler\Scp\PageList $pages, Logger $logger = null, \ScpCrawler\Scp\UserList $users = null)
    {
        return new \ScpCrawler\Updater\Threads\PagesUpdater($link, $siteId, $pages, $this->maxThreads, $logger, $users);
    }
    
    public function __construct($maxThreads = 16)
    {
        $this->maxThreads = $maxThreads;
    }
}
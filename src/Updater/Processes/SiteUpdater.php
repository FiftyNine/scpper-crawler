<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace ScpCrawler\Updater\Processes;

use ScpCrawler\Logger\Logger;
use ScpCrawler\Scp\DbUtils\KeepAliveMysqli;

class SiteUpdater extends \ScpCrawler\Updater\SiteUpdater
{
    protected $tempPath;
    protected $maxProcesses;
    
    protected function createUsersUpdater(KeepAliveMysqli $link, $siteName, \ScpCrawler\Scp\UserList $users, Logger $logger = null)
    {
        return new \ScpCrawler\Updater\Processes\UsersUpdater($link, $siteName, $users, $this->tempPath, $this->maxProcesses, $logger);
    }

    protected function createPagesUpdater(KeepAliveMysqli $link, $siteId, \ScpCrawler\Scp\PageList $pages, Logger $logger = null, \ScpCrawler\Scp\UserList $users = null)
    {
        return new \ScpCrawler\Updater\Processes\PagesUpdater($link, $siteId, $pages, $this->tempPath, $this->maxProcesses, $logger, $users);
    }
    
    public function __construct($tempPath, $maxProcesses = 16)
    {
        $this->tempPath = $tempPath;
        $this->maxProcesses = $maxProcesses;
    }
}
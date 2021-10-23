<?php

namespace ScpCrawler\Updater\Threads;

class SiteUpdater extends \ScpCrawler\Updater\SiteUpdater
{        
    protected $maxThreads;
    
    protected function getPagesUpdaterClass()
    {
        return '\ScpCrawler\Updater\Threads\PagesUpdater';
    }

    protected function getUsersUpdaterClass()
    {
        return '\ScpCrawler\Updater\Threads\UsersUpdater';
    }
    
    public function __construct($maxThreads = 16)
    {
        $this->maxThreads = $maxThreads;
    }
}
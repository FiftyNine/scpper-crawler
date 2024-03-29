<?php

namespace ScpCrawler\Updater;

use ScpCrawler\Logger\Logger;
use ScpCrawler\Scp\DbUtils\KeepAliveMysqli;

class UsersUpdater
{
    // Database link
    protected $link;
    // Logger
    protected $logger;
    // Site
    protected $siteName;
    // List of users to update
    protected $users;
    // List of users retrieved from site
    protected $webList;
    // Number of pages in the list of members
    protected $pageCount = 0;
    // Number of users
    protected $total = 0;
    // Couldn't load entire list or just some pages. Do not save
    protected $failed = false;

    public function __construct(KeepAliveMysqli $link, $siteName, \ScpCrawler\Scp\UserList $users, Logger $logger = null)
    {
        $this->link = $link;
        $this->siteName = $siteName;
        $this->users = $users;
        $this->logger = $logger;
    }

    protected function prepareUpdate()
    {
        if (!$this->users) {
            // Prepare list of users. We need it to add users retrieved along with pages.
            $this->users = new \ScpCrawler\Scp\UserList($this->siteName);
            $this->users->loadFromDB($this->link, $this->logger);
        }
        $membersHtml = null;
        //$this->failed = (\ScpCrawler\Wikidot\Utils::requestPage($this->siteName, 'system:members', $membersHtml, $this->logger) !== \ScpCrawler\Wikidot\PageStatus::OK);
        $this->failed = (\ScpCrawler\Wikidot\Utils::requestModule($this->siteName, 'membership/MembersListModule', 0, [], $membersHtml, $this->logger) !== \ScpCrawler\Wikidot\PageStatus::OK);
        // Get a list of pages from the site (only names)
        $this->pageCount = 0;
        $this->total = 0;        
        if (!$this->failed) {
            if ($membersHtml) {
                $this->pageCount = \ScpCrawler\Wikidot\Utils::extractPageCount($membersHtml);
            }
            $this->webList = new \ScpCrawler\Scp\UserList($this->siteName);
            $this->users->clearMembership();
        }
    }

    // Retrieve all the users
    protected function retrieveUsers()
    {
        $memberList = \ScpCrawler\Wikidot\Utils::iteratePagedModule($this->siteName, 'membership/MembersListModule', 0, [], range(1, $this->pageCount), $this->logger);
        foreach ($memberList as $mlPage) {
            if ($mlPage) {
                $this->total += $this->webList->addMembersFromListPage($mlPage, $this->logger);
                if ($this->total % 1000 == 0) {
                    Logger::logFormat(
                        $this->logger,
                        "%d members retrieved [%d kb used]...",
                        array($this->total, round(memory_get_usage()/1024))
                    );
                }
            } else {
                $this->failed = true;
                return;
            }
        }
    }

    // Finish updating
    protected function finishUpdate()
    {
        if (!$this->failed) {
            $users = $this->webList->getUsers();
            foreach ($users as $userId => $usr) {
                $this->users->addUser($usr['User'], $usr['Date']);
            }
            $this->users->saveToDB($this->link, $this->logger);
        } else {
            Logger::log($this->logger, "ERROR: Failed to update list of members!");
        }
    }

    // Load list from DB, update it from website and save changes back to DB
    public function go()
    {
        $this->prepareUpdate();
        if (!$this->failed) {
            $this->retrieveUsers();            
        }
        // retrieveUsers can change $this->failed flag
        $this->finishUpdate();
        return !$this->failed;
    }
}
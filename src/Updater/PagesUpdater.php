<?php

namespace ScpCrawler\Updater;

use ScpCrawler\Logger\Logger;
use ScpCrawler\Scp\DbUtils\KeepAliveMysqli;

class PagesUpdater
{
    // Database link
    protected $link;
    // Id of the wiki
    protected $siteId;
    // Logger    
    protected $logger;
    // List of pages from the database
    protected $pages;
    // List of users on the site
    protected $users;
    // Total number of pages on the site
    protected $total;
    // Number of succesfully processed pages
    protected $updated = 0;
    // Number of saved pages
    protected $saved = 0;
    // Number of pages changed since the last updated
    protected $changed = 0;
    // List of pages retrieved from the site
    protected $sitePages;
    // Array to detect duplicating pages (redirects from several urls to a single page)
    protected $done = array();
    // Array of names to keep track of pages we failed to load from the site
    protected $failedPages = array();

    public function __construct(KeepAliveMysqli $link, $siteId, \ScpCrawler\Scp\PageList $pages, Logger $logger = null, \ScpCrawler\Scp\UserList $users = null)
    {
        $this->link = $link;
        $this->siteId = $siteId;
        $this->pages = $pages;
        $this->users = $users;
        $this->logger = $logger;
    }

    // Helper function
    protected function saveUpdatingPage(\ScpCrawler\Scp\Page $page)
    {
        // But first, we need to add to DB users that aren't there yet
        foreach ($page->getRetrievedUsers() as $userId => $user) {
            $this->users->addUser($user);
            $listUser = $this->users->getUserById($userId);
            if ($listUser->getModified()) {
                $listUser->saveToDB($this->link, $this->logger);
            }
        }
        // Now save the page
        return $page->saveToDB($this->link, $this->logger);
    }

    //
    protected function prepareUpdate()
    {
        if (!$this->users) {
            // Prepare list of users. We need it to add users retrieved along with pages.
            $this->users = new \ScpCrawler\Scp\UserList($this->pages->siteName);
            $this->users->loadFromDB($this->link, $this->logger);
        }
        Logger::logFormat($this->logger, "Before loading from DB: %d", array(memory_get_usage()));
        // Let's retrieve all pages from DB
        $this->pages->loadFromDB($this->link, $this->logger);
        Logger::logFormat($this->logger, "After loading from DB: %d", array(memory_get_usage()));
        $this->pages->retrieveCategories($this->logger);
        foreach ($this->pages->getCategories() as $id => $cat) {
            \ScpCrawler\Scp\DbUtils\Category::insert($this->link, $this->siteId, $id, $cat->getName(), $this->logger);
        }                
        Logger::logFormat($this->logger, "Before retrieving list: %d", array(memory_get_usage()));
        // Get a list of pages from the site (only names)
        $this->sitePages = $this->pages->fetchListOfPages(null, $this->logger);
        Logger::logFormat($this->logger, "After retrieving list: %d", array(memory_get_usage()));
        $this->total = count($this->sitePages);
        $this->updated = 0;
        $this->saved = 0;
        $this->changed = 0;
        $this->failedPages = array();
        $this->done = array();
    }

    protected function finishUpdate()
    {
        $toDelete = [];
        // At this point our list will contain only failed pages
        // and pages that aren't on the site anymore
        // One last try to save pages that failed the first time - there shouldn't be many of them
        $this->pages->retrieveList($this->failedPages, true, $this->logger);
        foreach ($this->pages->iteratePages() as $page) {
            $id = $page->getId();
            if ($page->getStatus() == \ScpCrawler\Wikidot\PageStatus::OK) {
                if (!isset($this->done[$id])) {
                    $this->done[$id] = true;
                    if ($page->getModified()) {
                        $this->changed++;
                        if ($this->saveUpdatingPage($page)) {
                            $this->saved++;
                        }
                    }
                } else {
                    $this->total--;
                }
            } else if ($page->getStatus() == \ScpCrawler\Wikidot\PageStatus::NOT_FOUND && $id) {
                $toDelete[$id] = $page;
            } else if ($page->getStatus() == \ScpCrawler\Wikidot\PageStatus::UNKNOWN && $id) {
                $page->retrievePageInfo();
                if ($page->getStatus() === \ScpCrawler\Wikidot\PageStatus::NOT_FOUND || $page->getStatus() === \ScpCrawler\Wikidot\PageStatus::OK && $page->getId() !== $id) {
                    $toDelete[$id] = $page;
                }
            }
            $this->updated++;
        }
        $deleted = count($toDelete);
        // Lastly delete pages that are not on site anymore
        foreach ($toDelete as $pageId => $page) {
            \ScpCrawler\Scp\DbUtils\Page::delete($this->link, $pageId, $this->logger);
            Logger::logFormat($this->logger, "::: Deleting page %s (%d) :::", array($page->getPageName(), $pageId));
        }
        Logger::logFormat($this->logger, "::: Saved %d pages (%d changed, %d unique) :::", array($this->saved, $this->changed, $this->total));
        Logger::logFormat($this->logger, "::: Deleted %d pages :::", array($deleted));
    }

    protected function processPage(\ScpCrawler\Scp\Page $page, $success)
    {
        $id = $page->getId();
        if ($id) {
            if (!isset($this->done[$id])) {
                // If we retrieved everything successfully, add page to the list or copy information to the existing page on list
                if ($success) {
                    $this->pages->addPage($page);
                    $page = $this->pages->getPageById($id);
                    // Then save this page to DB
                    if ($page->getModified()) {
                        $this->changed++;
                        if ($this->saveUpdatingPage($page)) {
                            $this->saved++;
                        }
                    }
                    $this->updated++;
                    $this->done[$id] = true;
                } else {
                    // Otherwise, to the failed pages we go
                    $this->failedPages[] = $page->getPageName();
                }
            } else {
                $this->total--;
            }
            // Null all references to the page and free memory, unless it's in the failed list
            $this->pages->removePage($id);
        } else {
            // Otherwise, to the failed pages we go
            $this->failedPages[] = $page->getPageName();
        }
        // Logging our progress
        if ($this->updated % 100 == 0) {
            Logger::logFormat(
                $this->logger,
                "%d pages updated [%d kb used]...",
                array($this->updated, round(memory_get_usage()/1024))
            );
        }
    }

    // Process all the pages
    protected function processPages()
    {
        // Iterate through all pages and process them one by one
        for ($i = count($this->sitePages)-1; $i>=0; $i--) {
            $page = $this->sitePages[$i];
            // Maintain a list of pages we failed to retrieve so we could try again later
            if (!$page->retrievePageInfo($this->logger)) {
                $this->processPage($page, false);
                continue;
            }
            $good = true;
            if (!isset($this->done[$page->getId()])) {
                // Let's see if this page already exists in the database
                $oldPage = $this->pages->getPageById($page->getId());
                // Always have to retrieve votes because it's impossible to tell without it if they have changed
                if (!$page->retrievePageVotes($logger)) {
                    $this->processPage($page, false);
                    continue;
                }
                // If it's a new page or it was edited, we have to retrieve source and list of revisions
                if (!$oldPage || $page->getLastRevision() != $oldPage->getLastRevision() || $oldPage->getSource() == null || strlen($oldPage->getSource() < 10)) {
                    $good = $page->retrievePageHistory($this->logger) && $page->retrievePageSource($this->logger);
                }
            }
            $this->processPage($page, $good);
            if ($good) {
                unset($page);
                unset($this->sitePages[$i]);
            }
        }
    }

    // Load list from DB, update it from website and save changes back to DB
    public function go()
    {
        $this->prepareUpdate();
        $this->processPages();
        $this->finishUpdate();
    }
}

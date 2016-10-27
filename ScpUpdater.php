<?php

require_once "ScpCrawler.php";

class ScpSiteUtils
{
    const ROLE_AUTHOR = 1;
    const ROLE_REWRITER = 2;
    const ROLE_TRANSLATOR = 3;

    public static function setContributors(KeepAliveMysqli $link, ScpPage $page, $role, $users)
    {
        $userIds = array();
        foreach ($users as $user) {
            $userIds[] = (string)($user->getId());
        }
        $link->query(vsprintf("CALL SET_CONTRIBUTORS(%d, %d, '%s')", array($page->getId(), $role, implode(',', $userIds))));
    }

    // Get information about authorship overrides from Alexandra's override page and write it to DB
    public static function updateStatusOverridesEn_Old(
        KeepAliveMysqli $link, 
        ScpPageList $pages = null, 
        ScpUserList $users = null, 
        WikidotLogger $logger = null
    )
    {
        $html = null;
        WikidotUtils::requestPage('05command', 'alexandra-rewrite', $html, $logger);
        if (!$html) {
            return;
        }
        $doc = phpQuery::newDocument($html);
        $table = pq('div#page-content table.wiki-content-table', $doc);
        if (!$table) {
            return;
        }
        $list = array();
        $i = 0;
        foreach (pq('tr', $table) as $row) {
            if ($i > 0) {
                $pgName = strtolower(pq('td:first', $row)->text());
                $list[$pgName] = pq('td:last', $row)->text();
            }
            $i++;
        }
        $doc->unloadDocument();
        if (!$pages) {
            $pages = new ScpPageList('scp-wiki');
            $pages->loadFromDB($link, $logger);
        }
        if (!$users) {
            $users = new ScpUserList('scp-wiki');
            $users->loadFromDB($link, $logger);
        }
        $saved = 0;
        foreach ($list as $pageName => $override) {
            $ovStr = explode(':', $override);
            $page = $pages->getPageByName($pageName);
            $userName = ($ovStr[0] == '') ? $ovStr[2] : $ovStr[0];
            if ($userName == 'Unknown Author') {
                $user = $users->getUserById(-1);
            } else {
                $user = $users->getUserByDisplayName($userName);
            }
            $status = ($ovStr[0] == '') ? $ovStr[1] : 'rewrite';
            if ($page && $user) {
                if ($status == 'rewrite') {
                    self::setContributors($link, $page, self::ROLE_REWRITER, array($user));
                } else {
                    self::setContributors($link, $page, self::ROLE_AUTHOR, array($user));
                }
                $saved++;
            } else {
                if (!$page) {
                    WikidotLogger::logFormat($logger, 'Overriden page "%s" not found', array($pageName));
                }
                if (!$user) {
                    WikidotLogger::logFormat($logger, 'Overriden author "%s" not found', array($userName));
                }                
            }
        }
        WikidotLogger::logFormat($logger, "::: Author overrides updates, %d entries saved (%d total) :::", array($saved, count($list)));
    }

    // Get information about authorship overrides from attribution page and write it to DB
    public static function updateStatusOverridesEn(
        KeepAliveMysqli $link, 
        ScpPageList $pages = null, 
        ScpUserList $users = null, 
        WikidotLogger $logger = null
    )
    {
        $html = null;
        WikidotUtils::requestPage('scp-wiki', 'attribution-metadata', $html, $logger);
        if (!$html) {
            return;
        }
        $doc = phpQuery::newDocument($html);
        $table = pq('div#page-content table.wiki-content-table', $doc);
        if (!$table) {
            return;
        }
        $list = array();
        $i = 0;
        foreach (pq('tr', $table) as $row) {
            if ($i > 0) {
                $pgName = strtolower(pq('td:first-child', $row)->text());
                $type = pq('td:nth-child(3)', $row)->text();
                if (!array_key_exists($pgName, $list)) {
                    $list[$pgName] = array();
                }
                if (!array_key_exists($type, $list[$pgName])) {
                    $list[$pgName][$type] = array();
                }
                $list[$pgName][$type][] = array(
                    'user' => pq('td:nth-child(2)', $row)->text(),
                    'date' => pq('td:last-child', $row)->text()
                );
            }
            $i++;
        }
        $doc->unloadDocument();
        if (!$pages) {
            $pages = new ScpPageList('scp-wiki');
            $pages->loadFromDB($link, $logger);
        }
        if (!$users) {
            $users = new ScpUserList('scp-wiki');
            $users->loadFromDB($link, $logger);
        }
        $saved = 0;
        foreach ($list as $pageName => $overrideTypes) {            
            $page = $pages->getPageByName($pageName);            
            if (!$page) {
                WikidotLogger::logFormat($logger, 'Overriden page "%s" not found', array($pageName));
                continue;
            }
            foreach ($overrideTypes as $type => $overrides) {
                $ovUsers = array();
                foreach ($overrides as $override) {
                    if ($override['user'] == 'Unknown Author') {
                        $user = $users->getUserById(-1);
                    } else {
                        $user = $users->getUserByDisplayName($override['user']);
                    }
                    if (!$user) {
                        WikidotLogger::logFormat($logger, 'Overriden author "%s" not found', array($override['user']));
                        continue;
                    } else {
                        $ovUsers[] = $user;
                    }                    
                }
                if (count($ovUsers) == 0) {
                    continue;
                }
                switch ($type) {
                    case 'rewrite':
                        self::setContributors($link, $page, self::ROLE_REWRITER, $ovUsers);
                        break;
                    case 'translator':
                        self::setContributors($link, $page, self::ROLE_TRANSLATOR, $ovUsers);
                        break;
                    case 'author':
                        self::setContributors($link, $page, self::ROLE_AUTHOR, $ovUsers);
                        break;                        
                    default: 
                        WikidotLogger::logFormat($logger, 'Unknown role "%s" for page "%s"', array($type, $pageName));
                }
                $saved++;
            }
        }
        WikidotLogger::logFormat($logger, "::: Author overrides updates, %d entries saved (%d total) :::", array($saved, count($list)));
    }
}

class ScpPagesUpdater
{   
    // Database link
    protected $link;
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
    
    public function __construct(KeepAliveMysqli $link, ScpPageList $pages, WikidotLogger $logger = null, ScpUserList $users = null)
    {
        $this->link = $link;
        $this->pages = $pages;
        $this->users = $users;
        $this->logger = $logger;
    }

    // Helper function
    protected function saveUpdatingPage(ScpPage $page) 
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
            $this->users = new ScpUserList($this->pages->siteName);
            $this->users->loadFromDB($this->link, $this->logger);
        }
        WikidotLogger::logFormat($this->logger, "Before loading from DB: %d", array(memory_get_usage()));
        // Let's retrieve all pages from DB
        $this->pages->loadFromDB($this->link, $this->logger);
        WikidotLogger::logFormat($this->logger, "After loading from DB: %d", array(memory_get_usage()));        
        WikidotLogger::logFormat($this->logger, "Before retrieving list: %d", array(memory_get_usage()));
        // Get a list of pages from the site (only names)
        $this->sitePages = $this->pages->fetchListOfPages(null, $this->logger);
        WikidotLogger::logFormat($this->logger, "After retrieving list: %d", array(memory_get_usage()));
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
            if ($page->getStatus() == WikidotStatus::OK) {
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
            } else if ($page->getStatus() == WikidotStatus::NOT_FOUND && $id) {
                $toDelete[$id] = $page;
            } else if ($page->getStatus() == WikidotStatus::UNKNOWN && $id) {                
                $page->retrievePageInfo();                
                if ($page->getStatus() === WikidotStatus::NOT_FOUND || $page->getStatus() === WikidotStatus::OK && $page->getId() !== $id) {
                    $toDelete[$id] = $page;                    
                }
            }            
            $this->updated++;
        }
        $deleted = count($toDelete);
        // Lastly delete pages that are not on site anymore
        foreach ($toDelete as $pageId => $page) {            
            ScpPageDbUtils::delete($this->link, $pageId, $this->logger);
            WikidotLogger::logFormat($this->logger, "::: Deleting page %s (%d) :::", array($page->getPageName(), $pageId));
        }        
        WikidotLogger::logFormat($this->logger, "::: Saved %d pages (%d changed, %d unique) :::", array($this->saved, $this->changed, $this->total));
        WikidotLogger::logFormat($this->logger, "::: Deleted %d pages :::", array($deleted));        
    }    
    
    protected function processPage(ScpPage $page, $success)
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
            WikidotLogger::logFormat(
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

class ScpSiteUpdater
{
    protected function getPagesUpdaterClass()
    {
        return 'ScpPagesUpdater';
    }
    
    protected function updateStatusOverrides($siteName, KeepAliveMysqli $link, ScpPageList $pages = null, ScpUserList $users = null, WikidotLogger $logger = null)
    {
        if ($siteName == 'scp-wiki') {
            ScpSiteUtils::updateStatusOverridesEn($link, $pages, $users, $logger);
            WikidotLogger::log($logger, "Updating page kinds...");
            $link->query("CALL FILL_PAGE_KINDS_EN()");        
        } else if ($siteName == 'scp-ru') {
            WikidotLogger::log($logger, "Updating page kinds...");
            $link->query("CALL FILL_PAGE_KINDS_RU()");                
        }
    }   
        
    // Load all data from site and save it to DB
    public function loadSiteData($siteName, KeepAliveMysqli $link, WikidotLogger $logger)
    {
        WikidotLogger::log($logger, "\n");
        WikidotLogger::logFormat($logger, "======= Starting the first indexation of %s.wikidot.com =======", array($siteName));
        $ul = new ScpUserList($siteName);
        $ul->retrieveSiteMembers($logger);
        $pl = new ScpPageList($siteName);
        $pl->retrievePages(null, 0, $logger);
        $i = 0;
        foreach($pl->iteratePages() as $page) {
            $page->retrievePageModules($logger);
            $ul->addUsersFromPage($page);
            $i++;
            if ($i % 100 == 0) {
               WikidotLogger::logFormat($logger, "%d pages done...", array($i)); 
            }
        }
        $ul->saveToDB($link, $logger);
        $pl->saveToDB($link, $logger);
        WikidotLogger::logFormat($logger, "======= The first indexation of %s.wikidot.com has finished =======", array($siteName));
    }

    // Update data for a site from web
    public function updateSiteData($siteName, KeepAliveMysqli $link, WikidotLogger $logger)
    {
        WikidotLogger::log($logger, "\n");
        WikidotLogger::logFormat($logger, "======= Updating data for %s.wikidot.com =======", array($siteName));        
        if ($dataset = $link->query("SELECT WikidotId FROM sites WHERE WikidotName='$siteName'")) {
            if ($row = $dataset->fetch_assoc()) {
                $siteId = (int) $row['WikidotId'];
            }
        }  
        if (!isset($siteId)) {
            WikidotLogger::log($logger, "Error: Failed to retrieve site id from database.");
            return;
        }    
        $ul = new ScpUserList($siteName);
        $ul->loadFromDB($link, $logger);    
        $ul->updateFromSite($logger);
        $ul->saveToDB($link, $logger);
        $pl = new ScpPageList($siteName);
        $updaterClass = $this->getPagesUpdaterClass();
        $updater = new $updaterClass($link, $pl, $logger, $ul);
        $updater->go();
        unset($updater);
        $this->updateStatusOverrides($siteName, $link, null, $ul, $logger);
        $link->query("UPDATE sites SET LastUpdate = Now() WHERE WikidotId = '$siteId'");
        WikidotLogger::log($logger, "Updating user activity...");
        $link->query("CALL UPDATE_USER_ACTIVITY('$siteId')");
        WikidotLogger::log($logger, "Updating page summaries...");
        $link->query("CALL UPDATE_PAGE_SUMMARY('$siteId')");
        WikidotLogger::log($logger, "Updating site stats...");
        $link->query("CALL UPDATE_SITE_STATS('$siteId')");	
        WikidotLogger::logFormat($logger, "Peak memory usage: %d kb", array(round(memory_get_peak_usage()/1024)));
        WikidotLogger::logFormat($logger, "======= Update %s.wikidot.com has finished =======", array($siteName));
    }        
}

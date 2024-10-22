<?php

namespace ScpCrawler\Updater;

use ScpCrawler\Logger\Logger;
use ScpCrawler\Scp\DbUtils\KeepAliveMysqli;

class SiteUpdater
{
    protected function createUsersUpdater(KeepAliveMysqli $link, $siteName, \ScpCrawler\Scp\UserList $users, Logger $logger = null)
    {
        return new \ScpCrawler\Updater\UsersUpdater($link, $siteName, $users, $logger);
    }

    protected function createPagesUpdater(KeepAliveMysqli $link, $siteId, \ScpCrawler\Scp\PageList $pages, Logger $logger = null, \ScpCrawler\Scp\UserList $users = null)
    {
        return new \ScpCrawler\Updater\PagesUpdater($link, $siteId, $pages, $logger, $users);
    }
    
    protected function updateStatusOverrides($siteName, KeepAliveMysqli $link, \ScpCrawler\Scp\PageList $pages = null, \ScpCrawler\Scp\UserList $users = null, Logger $logger = null)
    {
        if ($siteName == 'scp-wiki') {
            SiteUtils::updateStatusOverridesEn($link, $pages, $users, $logger);
        }        
        Logger::log($logger, "Updating page kinds...");
        if ($siteName == 'scp-wiki') {
            $link->query("CALL FILL_PAGE_KINDS_EN()");
        } else if ($siteName == 'scp-ru') {
            $link->query("CALL FILL_PAGE_KINDS_RU()");
        } else if ($siteName == 'fondationscp') {
            $link->query("CALL FILL_PAGE_KINDS_FR()");
        } else if ($siteName == 'scp-wiki-de') {
            $link->query("CALL FILL_PAGE_KINDS_DE()");
        } else if ($siteName == 'scp-kr') {
            $link->query("CALL FILL_PAGE_KINDS_KO()");
        }        
    }

    protected function updateAlternativeTitles($siteName, KeepAliveMysqli $link, \ScpCrawler\Scp\PageList $pages = null, Logger $logger = null)
    {
        switch ($siteName) {
            case 'scp-wiki':
                SiteUtils::updateAltTitlesEn($link, $pages, $logger);
                break;
            case 'scp-ru':
                // Alt title is a part of title
                // SiteUtils::updateAltTitlesRu($link, $pages, $logger);
                break;            
            case 'scp-kr':
                SiteUtils::updateAltTitlesKr($link, $pages, $logger);
                break;            
            case 'scp-jp':
                SiteUtils::updateAltTitlesJp($link, $pages, $logger);
                break;            
            case 'fondazionescp':
                SiteUtils::updateAltTitlesIt($link, $pages, $logger);
                break;            
            case 'fondationscp':
                SiteUtils::updateAltTitlesFr($link, $pages, $logger);
                break;            
            case 'lafundacionscp':
                SiteUtils::updateAltTitlesEs($link, $pages, $logger);
                break;            
            case 'scp-th':
                SiteUtils::updateAltTitlesTh($link, $pages, $logger);
                break;            
            case 'scp-pl':
                // Doesn't work due to formatting
                // SiteUtils::updateAltTitlesPl($link, $pages, $logger);
                break;
            case 'scp-wiki-de':
                SiteUtils::updateAltTitlesDe($link, $pages, $logger);
                break;            
            case 'scp-wiki-cn':
                SiteUtils::updateAltTitlesCn($link, $pages, $logger);
                break;            
            case 'scp-ukrainian':
                SiteUtils::updateAltTitlesUa($link, $pages, $logger);
                break;            
            case 'scp-pt-br':
                // Doesn't work due to formatting
                // SiteUtils::updateAltTitlesPt($link, $pages, $logger);
                break;            
        }
    }

    // Load all data from site and save it to DB
    public function loadSiteData($siteName, KeepAliveMysqli $link, Logger $logger)
    {
        Logger::log($logger, "\n");
        Logger::logFormat($logger, "======= Starting the first indexation of %s.wikidot.com =======", array($siteName));
        $ul = new \ScpCrawler\Scp\UserList($siteName);
        $ul->retrieveSiteMembers($logger);
        $pl = new \ScpCrawler\Scp\PageList($siteName);
        $pl->retrievePages(null, 0, $logger);
        $i = 0;
        foreach($pl->iteratePages() as $page) {
            $page->retrievePageModules($logger);
            $ul->addUsersFromPage($page);
            $i++;
            if ($i % 100 == 0) {
               Logger::logFormat($logger, "%d pages done...", array($i));
            }
        }
        $ul->saveToDB($link, $logger);
        $pl->saveToDB($link, $logger);
        Logger::logFormat($logger, "======= The first indexation of %s.wikidot.com has finished =======", array($siteName));
    }

    // Update data for a site from web
    public function updateSiteData($siteName, KeepAliveMysqli $link, Logger $logger)
    {
        Logger::log($logger, "\n");
        Logger::logFormat($logger, "======= Updating data for %s.wikidot.com =======", array($siteName));
        if ($dataset = $link->query("SELECT WikidotId FROM sites WHERE WikidotName='$siteName'")) {
            if ($row = $dataset->fetch_assoc()) {
                $siteId = (int) $row['WikidotId'];
            }
        }
        if (!isset($siteId)) {
            Logger::log($logger, "Error: Failed to retrieve site id from database.");
            return;
        }
        $startTime = date("Y-m-d H:i:s");
        \ScpCrawler\Wikidot\Utils::selectProtocol($siteName, $logger);
        $ul = new \ScpCrawler\Scp\UserList($siteName);
        $ul->loadFromDB($link, $logger);       
        $usersUpdater = $this->createUsersUpdater($link, $siteName, $ul, $logger);
        $usersUpdated = $usersUpdater->go();
        unset($usersUpdater);        
        $pl = new \ScpCrawler\Scp\PageList($siteName);
        $pageUpdater = $this->createPagesUpdater($link, $siteId, $pl, $logger, $ul);
        $pageUpdater->go();
        unset($pageUpdater);
        $pl = new \ScpCrawler\Scp\PageList($siteName);
        $pl->loadFromDB($link, $logger);
        $this->updateStatusOverrides($siteName, $link, $pl, $ul, $logger);
        Logger::log($logger, "Updating alternative titles...");
        $this->updateAlternativeTitles($siteName, $link, $pl, $logger);
        Logger::log($logger, "Updating user activity...");
        $link->query("CALL UPDATE_USER_ACTIVITY('$siteId')");        
        if (!$usersUpdated) {
            Logger::log($logger, "Fixing membership (failed to update users)...");
            $link->query("CALL FIX_MISSING_MEMBERSHIP('$siteId')");            
        }
        Logger::log($logger, "Updating page summaries...");
        $link->query("CALL UPDATE_PAGE_SUMMARY('$siteId')");
        Logger::log($logger, "Updating site stats...");
        $link->query("CALL UPDATE_SITE_STATS('$siteId')");
        $link->query("UPDATE sites SET LastUpdate = '$startTime' WHERE WikidotId = '$siteId'");
        Logger::logFormat($logger, "Peak memory usage: %d kb", array(round(memory_get_peak_usage()/1024)));
        Logger::logFormat($logger, "======= Update %s.wikidot.com has finished =======", array($siteName));
    }
}
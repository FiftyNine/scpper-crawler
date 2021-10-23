<?php

namespace ScpCrawler\Scp;

use ScpCrawler\Logger\Logger;
use ScpCrawler\Scp\DbUtils\KeepAliveMysqli;

// SCP database page list class
class PageList extends \ScpCrawler\Wikidot\PageList
{
    // Array of pageIds that should be deleted from DB on next save
    private $toDelete;

    // Class of user objects
    protected function getPageClass()
    {
        return '\ScpCrawler\Scp\Page';
    }

    // Load list of pages from DB
    public function loadFromDB($link, Logger $logger = null)
    {
        $this->pages = array();
        $this->toDelete = array();
        $startTime = microtime(true);
        $this->loadCategoriesFromDB($link, $logger);
        $query = "SELECT ".DbUtils\Page::VIEW_PAGEID." FROM ".DbUtils\Page::VIEW." WHERE ".DbUtils\Page::VIEW_SITE_NAME." = '{$this->getSiteName()}'";
        Logger::logFormat($logger, "::: Loading list of pages for site %s.wikidot.com from DB", array($this->getSiteName()));
        try {
            if ($dataset = $link->query($query)) {
                $res = true;
                while ($row = $dataset->fetch_assoc()) {
                    $page = new Page((int)$row[DbUtils\Page::VIEW_PAGEID]);
                    $page->loadFromDB($link, $logger);
                    $this->addPage($page);
					// debug
//					sleep(1);
                }
            } else {
                Logger::logFormat($logger, "::: Failed. KeepAliveMysqli error:\"%s\"", array($link->error()));
                return false;
            }
        } catch (Exception $e) {
            Logger::logFormat(
                $logger,
                "::: Failed. Loaded %d pages before exception\nError:\"%s\"",
                array(count($this->pages), $e->getMessage())
            );
            throw $e;
        }
        $total = microtime(true)-$startTime;
        Logger::logFormat($logger, "::: Success. Loaded %d pages in %.3f sec", array(count($this->pages), $total));
        return true;
    }

    // Save all pages from DB
    public function saveToDB($link, Logger $logger = null)
    {
        $changed = 0;
        $saved = 0;
        $deleted = 0;
        $startTime = microtime(true);
        Logger::logFormat($logger, "::: Saving list of pages for site %s.wikidot.com from DB", array($this->getSiteName()));
        try {
            foreach ($this->pages as $id => $page) {
                if ($page->getModified()) {
                    $changed++;
                    if  ($page->saveToDB($link, $logger)) {
                        $saved++;
                    }
                }
            }
            if (isset($this->toDelete)) {
                $deleted = count($this->toDelete);
                foreach ($this->toDelete as $pageId => $page) {
                    DbUtils\Page::delete($link, $pageId, $logger);
                }
            }
        } catch (Exception $e) {
            Logger::logFormat(
                $logger,
                "::: Failed. Saved %d of %d pages before failing\nError: \"%s\"",
                array($saved, count($this->pages), $e->getMessage())
            );
            throw $e;
        }
        $total = microtime(true) - $startTime;
        Logger::logFormat(
            $logger,
            "::: Success. Saved %d pages (%d changed, %d total) in %.3f sec",
            array($saved, $changed, count($this->pages), $total)
        );
        return true;
    }
    
    // Load categories from DB
    private function loadCategoriesFromDb(KeepAliveMysqli $link, Logger $logger = null)
    {
        $this->categories = [];
        $query = "SELECT * FROM ".DbUtils\Category::VIEW
            ." WHERE ".DbUtils\Category::VIEW_SITENAME." = '{$this->getSiteName()}'";
        if ($dataset = $link->query($query)) {
            while ($row = $dataset->fetch_assoc()) {
                $cat = new Category(0, '');
                $cat->setDbValues($row);
                $this->categories[$cat->getId()] = $cat;
            }
        } else {
            Logger::logFormat(
                $logger,
                "Failed to load from DB categories for site %s://%s.wikidot.com\nError: \"%s\"",
                array(\ScpCrawler\Wikidot\Utils::$protocol, $this->getSiteName(), $link->error())
            );
        }
    }       
}

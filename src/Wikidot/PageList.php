<?php

namespace ScpCrawler\Wikidot;

use ScpCrawler\Logger\Logger;

// Class containing a list of pages of a single wikidot website
class PageList
{
    /*** Fields ***/
    // Wikidot name of the site
    private $siteName;
    // Array of (CategoryId => Category)
    protected $categories;
    // Array of (PageId => Page)
    protected $pages;
    // Array of (PageName)
    protected $failedPages;

    /*** Protected ***/
    // Returns class of pages
    protected function getPageClass()
    {
        return '\ScpCrawler\Wikidot\Page';
    }

    /*** Public ***/
    public function __construct($siteName)
    {
        if (!preg_match('/^[\w\-]+$/', $siteName)) {
            throw new Exception('Invalid wikidot site name');
        }
        $this->siteName = $siteName;
        $this->pages = array();
        $this->failedPages = array();
        $this->categories = array();
    }

    // Add page to the list or update existing page
    public function addPage(Page $page)
    {
        if (!$page || !is_a($page, $this->getPageClass()) || $page->getSiteName() !== $this->siteName)
            return;
        $pageId = $page->getId();
        if (isset($this->pages[$pageId])) {
            $this->pages[$pageId]->updateFrom($page);
        } else {
            $this->pages[$pageId] = $page;
        }
    }

    // Remove a page from the list
    public function removePage($pageId)
    {
        unset($this->pages[$pageId]);
    }

    // Retrieves list of categories and stores in the respective field
    public function retrieveCategories(Logger $logger = null)
    {
        $startTime = microtime(true);
        Logger::logFormat($logger, "::: Retrieving list of pages from %s.wikidot.com :::", array($this->siteName));
        try {
            $html = null;
            \ScpCrawler\Wikidot\Utils::requestModule($this->siteName, 'list/WikiCategoriesModule', 0, [], $html, $logger);
            if ($html) {
                $doc = \phpQuery::newDocument($html);
                foreach (pq('div', $doc) as $category) {
                    $catName = pq('h3', $category)->text();
                    $catId = (int)substr(pq('a', $category)->attr('id'), strlen('category-pages-toggler-'));
                    if ($catName && $catId && !array_key_exists($catId, $this->categories)) {
                        $this->categories[$catId] = new Category($catId, $catName);
                    }
                }
                $doc->unloadDocument();
            } else {
                $res = false;
            }            
        } catch (Exception $e) {
            Logger::logFormat($logger, "Error: \"%s\"", array($e->getMessage()));
            throw $e;
        }
        $totalTime = (microtime(true) - $startTime);
        if ($res) {
            Logger::logFormat(
                $logger,
                "::: Finished. Retrieved: %d. Time: %.3f sec :::",
                array(count($res), count($res), $totalTime)
            );
        } else {
            Logger::log($logger, "::: Failed :::");
        }        
    }
    
    // Returns list of pages
    public function fetchListOfPages($criteria, Logger $logger = null)
    {
        $res = array();
        $startTime = microtime(true);
        $catNames = [];
        foreach ($this->categories as $catId => $cat) {
            if (!$cat->getIgnored()) {
                $catNames[] = $cat->getName();
            }
        }
        if ($catNames === []) {
            $catNames[] = '_default';
        }
        Logger::logFormat($logger, "::: Retrieving list of pages from %s.wikidot.com :::", array($this->siteName));
        try {
            foreach ($catNames as $category) {
                if (($category=='log-of-unexplained-locations') || ($category=='fragment')||($category=='anomalous-jp')) {
                    continue;
                }
                $defaults = array(
                    'offset' => 0,
                    'page' => 1,
                    'order' => 'title',
                    'module_body' => '%%title_linked%%',
                    'perPage' => 250);
                // ToDo: Validate criteria
                if (is_array($criteria)) {
                    $args = array_merge($defaults, $criteria);
                } else {
                    $args = $defaults;
                }
                $args['category'] = $category;
                $i = 0;
                $list = \ScpCrawler\Wikidot\Utils::iteratePagedModule($this->siteName, 'list/ListPagesModule', 0, $args, null, $logger);
                while ($list->valid()) {
                    $listPage = $list->current();
                    $args['offset'] = (++$i)*250;
                    $list->send($args);
                    if ($listPage) {
                        $doc = \phpQuery::newDocument($listPage);
                        foreach (pq('div.list-pages-item a', $doc) as $page) {
                            $pageName = substr(pq($page)->attr('href'), 1);
                            if ($pageName) {
                                $pageClass = $this->getPageClass();
                                $page = new $pageClass($this->siteName, $pageName);
                                $res[] = $page;
                            }
                        }
                        $doc->unloadDocument();
                    } else {
                        $res = false;
                        break;
                    }
                }
            }
        } catch (Exception $e) {
            Logger::logFormat($logger, "Error: \"%s\"", array($e->getMessage()));
            throw $e;
        }
        $totalTime = (microtime(true) - $startTime);
        if ($res) {
            Logger::logFormat(
                $logger,
                "::: Finished. Retrieved: %d. Time: %.3f sec :::",
                array(count($res), $totalTime)
            );
        } else {
            Logger::log($logger, "::: Failed :::");
        }
        return $res;
    }    

    // Add pages fitting specified criteria
    public function retrievePages($criteria, $limit = 0, Logger $logger = null)
    {
        $res = false;
        $success = 0;
        $fail = 0;
        $count = 0;
        $finished = false;
        $startTime = microtime(true);
        Logger::logFormat($logger, "::: Retrieving pages from %s.wikidot.com :::", array($this->siteName));
        try {
            $pages = $this->fetchListOfPages($criteria, $logger);
            foreach ($pages as $page) {
                if ($page->retrievePageInfo($logger)) {
                    $this->addPage($page);
                    $success++;
                } else {
                    if (!array_search($pageName, $this->failedPages)) {
                        $this->failedPages[] = $pageName;
                    }
                    $fail++;
                }
                $count++;
                if ($limit > 0 && $count >= $limit) {
                    $finished = true;
                    break;
                }
                if ($finished) {
                    break;
                }
            }
        } catch (Exception $e) {
            Logger::logFormat($logger, "Error: \"%s\"", array($e->getMessage()));
            $res = false;
        }
        $totalTime = (microtime(true) - $startTime);
        if ($res) {
            Logger::logFormat(
                $logger,
                "::: Finished. Retrieved: %d. Failed: %d. Time: %.3f sec :::",
                array($success, $fail, $totalTime)
            );
        } else {
            Logger::logFormat(
                $logger,
                "::: Failed. Retrieved: %d. Time: %.3f sec :::",
                array($success, $totalTime)
            );
        }
        return $res;
    }

    /**
     * Try to (re)load a list of pages
     * @param array(string) $list
     * @param Logger $logger
     */
    public function retrieveList(&$list, $retrieveAll, Logger $logger = null)
    {
        if (!$list || !count($list)) {
            return;
        }
        $success = 0;
        for ($i=count($list)-1; $i>=0; $i--) {
            $pageName = $list[$i];
            $page = $this->getPageByName($pageName);
            if (!$page) {
                $pageClass = $this->getPageClass();
                $page = new $pageClass($this->getSiteName(), $pageName);
            }
            if ($retrieveAll) {
                $res = $page->retrieveAll($logger);
            } else {
                $res = $page->retrievePageInfo($logger);
            }
            if ($res) {
                $this->addPage($page);
                unset($list[$i]);
                $success++;
            }
        }
        Logger::logFormat($logger, "Retrieved %d of %d requested pages", array($success, $success+count($list)));
    }

    // Retry loading failed pages
    public function retryFailed($retrieveAll, Logger $logger = null)
    {
        if (!$this->hasFailed()) {
            return;
        }
        $this->retrieveList($this->failedPages, $retrieveAll, $logger);
        // Logger::logFormat($logger, "Retrieved %d of %d earlier failed pages", array($success, $success+count($this->failedPages)));
    }

    /*** Access methods ***/
    // Return a page by its WikidotId
    public function getPageById($id)
    {
        if (isset($this->pages[$id])) {
            return $this->pages[$id];
        } else {
            return null;
        }
    }

    // Return a page by its wikidot name
    public function getPageByName($name)
    {
        foreach ($this->pages as $page) {
            if ($page->getPageName() == $name) {
                return $page;
            }
        }
        return null;
    }

    // Generator function allowing to iterate through all pages
    public function iteratePages()
    {
        foreach ($this->pages as $id => $page) {
            yield $page;
        }
    }

    // Wikidot name of the site
    public function getSiteName()
    {
        return $this->siteName;
    }

    // List of categories
    public function getCategories()
    {
        return $this->categories;
    }    
    
    // If list has pages it failed to retrieve
    public function hasFailed()
    {
        return (is_array($this->failedPages) && count($this->failedPages) > 0);
    }
}
<?php

namespace ScpCrawler\Wikidot;

use ScpCrawler\Logger\Logger;

// Class with properties of a single wikidot page
class Page
{
    /*** Fields ***/
    // Wikidot site name, e.g. 'scp-wiki'
    private $siteName;
    // Wikidot page name (url), e.g. 'scp-307', 'the-czar-cometh'
    private $pageName;
    // Retrieval status (according to \ScpCrawler\Wikidot\PageStatus class)
    private $status = PageStatus::UNKNOWN;
    // Wikidot inner site id
    private $siteId;
    // Wikidot inner page id
    private $pageId;
    // Wikidot page category id
    private $categoryId;
    // Page title ('SCP-307', 'The Czar Cometh', etc)
    private $title;
    // Wikidot source text of the page
    private $source;
    // Array of tags
    private $tags;
    // Associative array of votes on the page (userId => vote), where vote is either 1 or -1.
    private $votes;
    // Full list of revisions
    private $revisions;
    // Latest revision number
    protected $lastRevision = -1;
    // List of users who edited, voted or otherwise influenced the page (retrieved with history and voting modules)
    // (UserId => User)
    public $retrievedUsers;

    /*** Private ***/

    // Extract all properties from a loaded page HTML (not including info from modules)
    private function extractPageInfo($html, Logger $logger = null)
    {
        if (!$html)
            return;
        $doc = \phpQuery::newDocument($html);
        $this->setProperty('siteId', Utils::extractSiteId($html));
        if (!$this->getSiteId()) {
            Logger::logFormat(
                $logger,
                "Failed to extract SiteId for page {%s://%s.wikidot.com/%s}",
                array(Utils::$protocol, $this->getSiteName(), $this->getPageName())
            );
            return;
        }

        $this->setProperty('categoryId', Utils::extractCategoryId($html));
        if (!$this->getCategoryId()) {
            Logger::logFormat(
                $logger,
                "Failed to extract CategoryId for page {%s://%s.wikidot.com/%s}",
                array(Utils::$protocol, $this->getSiteName(), $this->getPageName())
            );
            return;
        }

        $this->setProperty('pageId', Utils::extractPageId($html));
        if (!$this->getId()) {
            Logger::logFormat(
                $logger,
                "Failed to extract PageId for page {%s://%s.wikidot.com/%s}",
                array(Utils::$protocol, $this->getSiteName(), $this->getPageName())
            );
            return;
        }

        // Extract page name in case we were redirected
        $newName = Utils::extractPageName($html);
        if ($newName != $this->getPageName()) {
            Logger::logFormat($logger, 'Redirect detected: "%s" -> "%s"', array($this->getPageName(), $newName));
            $this->setProperty('pageName', $newName);
        }

        // Extract last revision number
        $pageInfo = pq('div#page-info', $doc);
        if ($pageInfo && preg_match('/\d+/', $pageInfo->text(), $matches)) {
            $this->lastRevision = (int)$matches[0];
        } /*else {
            Logger::logFormat(
                $logger,
                "Failed to extract latest revision for page {%s://%s.wikidot.com/%s}",
                array(Utils::$protocol, $this->siteName, $this->pageName)
            );
        }*/

        // Extract page title
        $titleElem = pq('div#page-title', $doc);
        if ($titleElem) {
            $this->setProperty('title', trim($titleElem->text()));
        } else {
            Logger::logFormat(
                $logger,
                "Failed to extract title for page {%s://%s.wikidot.com/%s}",
                array(Utils::$protocol, $this->getSiteName(), $this->getPageName())
            );
            return;
        }

        // Extract page tags
        $tags = array();
        $tagsElem = pq('div.page-tags', $doc);
        if ($tagsElem) {
            foreach (pq('a', $tagsElem) as $tagElem) {
                $tags[] = pq($tagElem)->text();
            }
            $this->setProperty('tags', $tags);
        } else {
            Logger::logFormat(
                $logger,
                "Failed to extract tags for page {%s://%s.wikidot.com/%s}",
                array(Utils::$protocol, $this->getSiteName(), $this->getPageName())
            );
            return;
        }
        $doc->unloadDocument();
        return true;
    }

    /*** Protected ***/
    // Name of class for revision object
    protected function getRevisionClass()
    {
        return '\ScpCrawler\Wikidot\Revision';
    }

    // Name of class for user object
    protected function getUserClass()
    {
        return 'ScpCrawler\Wikidot\User';
    }

    // Informs the object that it was changed
    protected function changed($message = null)
    {
        // Do nothing
    }

    // Add revision to the list
    protected function addRevision(Revision $revision)
    {
        $this->revisions[$revision->getIndex()] = $revision;
        $this->changed();
    }

    // Set value by property name
    protected function setProperty($name, $value)
    {
        if ($name == "siteId" && is_int($value) && $this->siteId !== $value) {
            $this->siteId = $value;
            $this->changed();
        } elseif ($name == "pageId" && is_int($value) && $this->pageId !== $value) {
            $this->pageId = $value;
            $this->changed($name);
        } elseif ($name == "categoryId" && is_int($value) && $this->categoryId !== $value) {
            $this->categoryId = $value;
            $this->changed($name);
        } elseif ($name == "siteName" && is_string($value) && $this->siteName !== $value) {
            $this->siteName = $value;
            $this->changed($name);
        } elseif ($name == "pageName" && is_string($value) && $this->pageName !== $value) {
            $this->pageName = $value;
            $this->changed($name);
        } elseif ($name == "title" && is_string($value) && $this->title !== $value) {
            $this->title = $value;
            $this->changed($name);
        } elseif ($name == "source" && (is_string($value) || $value == null) && $this->source !== $value) {
            $this->source = $value;
            $this->changed($name);
        } elseif ($name == "tags" && is_array($value)) {
            asort($value);
            if (!is_array($this->tags)) {
                $this->tags = array();
            }
            if (array_diff($this->tags, $value) || array_diff($value, $this->tags)) {
                $this->tags = $value;
                $this->changed($name);
            }
        } elseif ($name == "votes" && is_array($value) && $this->votes != $value) {
            $this->votes = $value;
            $this->changed($name);
        } elseif ($name == "revisions" && is_array($value) && $this->revisions != $value) {
            $this->revisions = $value;
            $this->changed($name);
        }
    }

    /*** Public ***/
    public function __construct ($siteName, $pageName)
    {
        if (!preg_match('/^[\w\-]+$/', $siteName)) {
            throw new Exception('Invalid wikidot site name');
        }
        if (!preg_match('/^[\w\-:]+$/', $pageName)) {
            throw new Exception('Invalid wikidot page name');
        }
        $this->siteName = $siteName;
        $this->pageName = $pageName;
        $this->retrievedUsers = array();
    }

    // Request a source module from wikidot and extract source text from it
    public function retrievePageSource(Logger $logger = null)
    {
        $res = false;
        $html = null;
        Utils::requestModule($this->getSiteName(), 'viewsource/ViewSourceModule', $this->getId(), array(), $html, $logger);
        if ($html) {
            $doc = \phpQuery::newDocument($html);
            $elem = pq('div.page-source', $doc);
            if ($elem) {
                $this->setProperty('source', $elem->text());
                $res = true;
            }
            $doc->unloadDocument();
        }
        if (!$res) {
            Logger::logFormat(
                $logger,
                "Failed to retrieve source for page {%s://%s.wikidot.com/%s}",
                array(Utils::$protocol, $this->getSiteName(), $this->getPageName())
            );
        }
        return $res;
    }

    // Request a whoRated module from wikidot and extract votes from it
    public function retrievePageVotes(Logger $logger = null)
    {
        $res = false;
        $html = null;
        Utils::requestModule($this->getSiteName(), 'pagerate/WhoRatedPageModule', $this->getId(), array(), $html, $logger);
        if ($html) {
            $doc = \phpQuery::newDocument($html);
            $votes = array();
            foreach (pq('span.printuser', $doc) as $userElem) {
                $userClass = $this->getUserClass();
                $user = new $userClass();
                if ($user->extractFrom(pq($userElem))) {
                    $this->retrievedUsers[$user->getId()] = $user;
                    $voteElem = pq($userElem)->next();
                    $voteText = trim(pq($voteElem)->text());
                    if ($voteText === '+') {
                        $vote = 1;
                    } else if ($voteText === '-') {
                        $vote = -1;
                    } else {
                        continue;
                    }
                    $key = (string)$user->getId();
                    // In the unlikely (but somehow possible) case where
                    // there are two different votes from the same user
                    // on the same page at the same time, we will
                    // count only the positive vote to avoid votes leapfrogging 
                    // each other every other update
                    if (array_key_exists($key, $votes)) {
                        $vote = 1;
                    }
                    $votes[$key] = $vote;                    
                }
            }
            $this->setProperty('votes', $votes);
            $doc->unloadDocument();
            $res = true;
        }
        if (!$res) {
            Logger::logFormat(
                $logger,
                "Failed to retrieve votes for page {%s://%s.wikidot.com/%s}",
                array(Utils::$protocol, $this->getSiteName(), $this->getPageName())
            );
        }
        return $res;
    }

    // Request a history module for the page and extract information from it (posterId, creation date and last revision info)
    public function retrievePageHistory(Logger $logger = null)
    {
        $revisions = array();
        $res = false;
        $args = array(
            // Here's hoping nobody has that many revisions on a single page
            'perpage' => 1000000,
            'options' => '{"all":true}'
        );
        $pageIterator = Utils::iteratePagedModule($this->getSiteName(), 'history/PageRevisionListModule', $this->getId(), $args, null, $logger);
        foreach ($pageIterator as $html) {
            $doc = \phpQuery::newDocument($html);
            foreach (pq('tr[id^=\'revision-row\']', $doc) as $revElem) {
                $revClass = $this->getRevisionClass();
                $rev = new $revClass($this->getId());
                $rev->extractFrom(pq($revElem));
                $revisions[$rev->getIndex()] = $rev;
                $this->retrievedUsers[$rev->getUserId()] = $rev->getUser();
                $res = true;
            }
            $doc->unloadDocument();
        }
        if ($res) {
            $this->setProperty("revisions", array_reverse($revisions));
            $this->lastRevision = $revisions[0]->getIndex();
        } else {
            Logger::logFormat(
                $logger,
                "Failed to retrieve votes for page {%s://%s.wikidot.com/%s}",
                array(Utils::$protocol, $this->getSiteName(), $this->getPageName())
            );
        }
        return $res;
    }

    // Retrieve only html of the page itself and extract information from it
    public function retrievePageInfo(Logger $logger = null)
    {
        $html = null;
        try {
            $this->status = Utils::requestPage($this->getSiteName(), $this->getPageName(), $html, $logger);
            if ($this->status != \ScpCrawler\Wikidot\PageStatus::OK || !$html) {
                return false;
            }
            if (!$this->extractPageInfo($html, $logger)) {
                return false;
            } else {
                unset($html);
            }
        } catch (Exception $e) {
            Logger::logFormat(
                $logger,
                "Failed to retrieve page info {%s/%s}\nError message: \"%s\"",
                array($this->getSiteName(), $this->getPageName(), $e->getMessage())
            );
            return false;
        }
        return true;
    }

    // Retrieve modules for page (votes, source, history) and extract data from them
    public function retrievePageModules(Logger $logger = null)
    {
        try {
            if (!$this->retrievePageSource($logger))
                return false;
            if (!$this->retrievePageVotes($logger))
                return false;
            if (!$this->retrievePageHistory($logger))
                return false;
        } catch (Exception $e) {
            Logger::logFormat(
                $logger,
                "Failed to retrieve page modules {%s/%s}\nError message: \"%s\"",
                array($this->getSiteName(), $this->getPageName(), $e->getMessage())
            );
            return false;
        }
        return true;
    }

    // Open page by http using siteName and pageName and retrieve all information about it,making additional calls to modules
    public function retrieveAll(Logger $logger = null)
    {
        return $this->retrievePageInfo($logger) && $this->retrievePageModules($logger);
    }

    // Update info fomr other object of the same id
    public function updateFrom(Page $page)
    {
        if (!$page || $page->getId() != $this->getId())
            return;
        $this->setProperty('pageName', $page->pageName);
        $this->setProperty('title', $page->title);
        $this->setProperty('categoryId', $page->categoryId);
        $this->setProperty('source', $page->source);
        $this->setProperty('tags', $page->tags);
        $this->setProperty('votes', $page->votes);
        $this->setProperty('revisions', $page->revisions);
        if (isset($page->retrievedUsers)) {
            if (isset($this->retrievedUsers)) {
                $this->retrievedUsers = $this->retrievedUsers + $page->retrievedUsers;
            } else {
                $this->retrievedUsers = $page->retrievedUsers;
            }
        }
    }

    /*** Access methods ***/
    // Wikidot id
    public function getId()
    {
        return $this->pageId;
    }

    // Wikidot site id
    public function getSiteId()
    {
        return $this->siteId;
    }

    // Wikidot category id
    public function getCategoryId()
    {
        return $this->categoryId;
    }

    // Wikidot site name
    public function getSiteName()
    {
        return $this->siteName;
    }

    // Wikidot page name
    public function getPageName()
    {
        return $this->pageName;
    }

    // Title of the page
    public function getTitle()
    {
        return $this->title;
    }

    // Wikidot source text of the page
    public function getSource()
    {
        return $this->source;
    }

    // Tags
    public function getTags()
    {
        return $this->tags;
    }

    // Votes
    public function getVotes()
    {
        return $this->votes;
    }

    // Revisions
    public function getRevisions()
    {
        return $this->revisions;
    }

    // Last revision number
    public function getLastRevision()
    {
        if ($this->revisions) {
            return end($this->revisions)->getIndex();
        } else {
            return $this->lastRevision;
        }
    }

    // Users retrieved along with modules (UserId => User)
    public function getRetrievedUsers() {
        if (!is_array($this->retrievedUsers)) {
            $this->retrievedUsers = array();
        }
        return $this->retrievedUsers;
    }

    // Retrieval status
    public function getStatus() {
        return $this->status;
    }
}
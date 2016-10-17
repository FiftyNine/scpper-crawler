<?php
/**
 * This is a core module for SCP Crawler project, which is a part of larger SCPper project.
 * This file contains classes and functions neccessary to extract information about particular
 * pages, users, etc. Effectively this is a homemade API for accessing WIKIDOT information.
 *
**/
require_once 'HTTP/Request2.php';
require_once 'phpQuery/phpQuery.php';
/******************************************************/
/************ Classes for logging *********************/
/******************************************************/

if (defined('SCP_MULTITHREADED')) {
    abstract class WikidotLoggerBase extends Threaded
    {
        
    }
} else {
    abstract class WikidotLoggerBase
    {
        
    }
}

// Base class for loggers
abstract class WikidotLogger extends WikidotLoggerBase
{
    /*** Fields ***/
    private $timeZone = null;

    /*** Private ***/
    // Adds timestamp and linebreak to a message
    private function timestampMessage($message)
    {
        $now = new DateTime(null, $this->timeZone);
        return sprintf(
            "[%s] %s\n",
            $now->format('d/m/Y H:i:s'),
            $message
        );
    }

    // Adds timestamp and outputs message
    private function logSimpleInternal($message)
    {
        $timeMessage = $this->timestampMessage($message);
        $this->logInternal($timeMessage);
    }

    // Formats message with args using vsprintf and outputs it
    private function logFormatInternal($format, $args)
    {
        $message = vsprintf($format, $args);
        $this->logSimpleInternal($message);
    }

    /*** Protected ***/
    // Actual method for outputting messages. Must be overridden in descendants
    abstract protected function logInternal($message);

    /*** Public ***/
    // Set timezone
    public function setTimeZone($name)
    {
        if (!$this->timeZone = timezone_open($name)) {
            $this->timeZone = null;
        }
    }

    // Static method that checks if logger is null and calls logSimpleInternal
    public static function log($logger, $message)
    {
        if ($logger) {
            $logger->logSimpleInternal($message);
        }
    }

    // Static method that checks if logger is null and calls logFormatInternal
    public static function logFormat($logger, $format, $args)
    {
        if ($logger) {
            $logger->logFormatInternal($format, $args);
        }
    }
}

// Logger class that writes messages to a log file
class WikidotFileLogger extends WikidotLogger
{
    /*** Fields ***/
    // Path to the log file
    private $fileName;

    /*** Protected ***/
    // Write message to log file
    protected function logInternal($message)
    {
        file_put_contents($this->fileName, $message, FILE_APPEND);
    }

    /*** Public ***/
    public function __construct ($fileName, $append)
    {
        $this->fileName = $fileName;
        file_put_contents($this->fileName, "\n", $append?FILE_APPEND:0);
    }
}

// Logger class that prints messages to screen
class WikidotDebugLogger extends WikidotLogger
{
    /*** Protected ***/
    // Write message to standard output
    protected function logInternal($message)
    {
        print($message);
    }
}

/******************************************************/
/******* Classes for retrieving wikidot data **********/
/******************************************************/

// Utility class with functions to retrieve pages and modules
class WikidotUtils
{
    /*** Fields ***/
    // Max connection time, sec
    public static $connectionTimeout = 10;
    // Max request time, sec
    public static $requestTimeout = 30;
    // Max number of attempts for a single page
    public static $maxAttempts = 3;

    /*** Private ***/
    // Create and setup a new request 
    private static function createRequest($url)
    {
        $request = new HTTP_Request2($url, HTTP_Request2::METHOD_GET);
        $request->setConfig('follow_redirects', true);
        $request->setConfig('max_redirects', 2);
        $request->setConfig('strict_redirects', true);
        $request->setConfig('connect_timeout', self::$connectionTimeout);
        $request->setConfig('timeout', self::$requestTimeout);    
        return $request;
    }
    
    // Sends request at most $maxAttempts times, returns response object
    private static function sendRequest($request, WikidotLogger $logger = null)
    {
        $response = null;
        try {
            $i = 0;
            while ($i < self::$maxAttempts) {
                try {
                    $response = $request->send();
                } catch (HTTP_Request2_Exception $e) {                    
                    $i++;
                    if ($i >= self::$maxAttempts) {
                        throw $e;
                    } else {
                        continue;
                    }
                }
                $status = $response->getStatus();
                if ($status < 400) {
                    break;
                }
                $i++;
            }
        } catch (HTTP_Request2_Exception $e) {
            WikidotLogger::logFormat($logger, "Failed to retrieve {%s}\nUnexpected error: \"%s\"", array($request->getURL(), $e->getMessage()));
            return null;
        }
        return $response;
    }

    /*** Public ***/
    
    // Searches for a pager in html and returns number of pages in it. 1 if pager is not found
    public static function extractPageCount($html)
    {
        $res = 1;
        $doc = phpQuery::newDocument($html);
        if ($pager = pq('div.pager', $doc)) {
            foreach (pq('span.target, span.current', $pager) as $pageNo) {
                $text = trim(pq($pageNo)->text());
                if (filter_var($text, FILTER_VALIDATE_INT)) {
                    $res = max($res, (int)$text);
                }
            }
        }
        $doc->unloadDocument();
        return $res;
    }

    // Extracts a DateTime object from a DOM element if it has the right class. Returns null otherwise
    public static function extractDateTime(phpQueryObject $elem)
    {
        $classes = explode(' ', $elem->attr('class'));
        foreach ($classes as $class) {
            if (preg_match('/time_(?P<Timestamp>\d+)$/', $class, $matches)) {
                return (date_create()->setTimestamp((int)$matches['Timestamp']));
            }
        }
    }

    // Extract SiteId from page html
    public static function extractSiteId($html)
    {        
        if (preg_match('/WIKIREQUEST.info.siteId = (?P<SiteId>\d+);/', $html, $matches)) {
            if (filter_var($matches['SiteId'], FILTER_VALIDATE_INT)) {
                return (int)$matches['SiteId'];
            }
        }
    }
    
    // Extract CategoryId from page html
    public static function extractCategoryId($html)
    {
        if (preg_match('/WIKIREQUEST.info.categoryId = (?P<CategoryId>\d+);/', $html, $matches)) {
            if (filter_var($matches['CategoryId'], FILTER_VALIDATE_INT)) {
                return (int)$matches['CategoryId'];
            }
        }
    }

    // Extract PageId from page html
    public static function extractPageId($html)
    {        
        if (preg_match('/WIKIREQUEST.info.pageId = (?P<PageId>\d+);/', $html, $matches)) {
            if (filter_var($matches['PageId'], FILTER_VALIDATE_INT)) {
                return (int)$matches['PageId'];
            }
        }
    }

    // Extract PageName from page html
    public static function extractPageName($html)
    {        
        if (preg_match('/WIKIREQUEST.info.pageUnixName = "(?P<PageName>[\w\-:]+)";/', $html, $matches)) {
            return $matches['PageName'];
        }
    }    
    
    // Iterate through all pages of a paged module, yielding html of each page
    public static function iteratePagedModule($siteName, $moduleName, $pageId, $args, $pageNumbers = null, WikidotLogger $logger = null)
    {
        $changedArgs = array();
        if (!is_array($pageNumbers) || count($pageNumbers) == 0) {
            $pageNumbers = array();
            $args['page'] = 1;
            $firstPage = self::requestModule($siteName, $moduleName, $pageId, $args, $logger);
            if ($firstPage) {
                $changedArgs = (yield $firstPage);
                $pageCount = self::extractPageCount($firstPage);
                if ($pageCount > 1) {
                    $pageNumbers = range(2, $pageCount);
                }
            }
        }
        foreach ($pageNumbers as $i) {         
                if (is_array($changedArgs)) {
                $args = array_merge($args, $changedArgs);                
            }
            if (is_int($i) && $i > 0) {
                $args['page'] = $i;
                $modulePage = self::requestModule($siteName, $moduleName, $pageId, $args, $logger);
                $changedArgs = (yield $modulePage);
            }
        }
    }

    // Request a specified module from wikidot. Returns HTML string.
    public static function requestModule($siteName, $moduleName, $pageId, $args, WikidotLogger $logger = null)
    {
        $fullUrl = sprintf('http://%s.wikidot.com/ajax-module-connector.php', $siteName);
        $request = self::createRequest($fullUrl);
        $request->setMethod(HTTP_Request2::METHOD_POST);
        if (!is_array($args)) {
            $args = array();
        }
        $args['moduleName'] = $moduleName;
        $args['pageId'] = $pageId;
        $args['page_id'] = $pageId;
        $args['wikidot_token7'] = 'ayylmao';

        $request->addPostParameter($args);
        $request->addCookie('wikidot_token7', 'ayylmao');
        if ($response = self::sendRequest($request, $logger)) {
            $status = $response->getStatus();
            if ($status >= 200 && $status < 300) {
                $body = json_decode($response->getBody());
                if ($body && $body->status == 'ok') {
                    return $body->body;
                } elseif ($body->status == 'not_ok') {
                    WikidotLogger::logFormat(
                        $logger,
                        "Failed to retrieve module {%s/%s}\nWikidot error: \"%s\"\nArguments: %s",
                        array($siteName, $moduleName, $body->message, var_export($args, true))
                    );
                    return false;
                } else {
                    WikidotLogger::logFormat(
                        $logger,
                        "Failed to retrieve module {%s/%s}\nUnknown error\nArguments: %s",
                        array($siteName, $moduleName, var_export($args, true))
                    );
                }
            } elseif ($status >= 300 && $status < 400) {
                WikidotLogger::logFormat(
                    $logger,
                    "Failed to retrieve module {%s/%s}\nRedirect detected. HTTP status: %d\nArguments: %s",
                    array($siteName, $moduleName, $status, var_export($args, true))
                );
            } else {
                WikidotLogger::logFormat(
                    $logger,
                    "Failed to retrieve module {%s %s}\nHTTP status: %d. Error message: \"%s\"\nArguments: %s",
                    array($siteName, $moduleName, $response->getStatus(), $response->getReasonPhrase(), var_export($args, true))
                );
            }
        }
    }

    // Request a specified page from wikidot. Returns HTML string.
    public static function requestPage($siteName, $pageName, WikidotLogger $logger = null)
    {
        $fullUrl = sprintf('http://%s.wikidot.com/%s', $siteName, $pageName);
        $request = self::createRequest($fullUrl);
        $request->setConfig('use_brackets', true);
        if ($response = self::sendRequest($request, $logger)) {
            $status = $response->getStatus();
            if ($status >= 200 && $status < 300) {
                return $response->getBody();
            } elseif ($status >= 300 && $status < 400) {
                WikidotLogger::logFormat(
                    $logger,
                    "Failed to retrieve page {%s}. HTTP status: %d. Redirect detected",
                    array($request->getURL(), $status)
                );
            } else {
                WikidotLogger::logFormat(
                    $logger,
                    "Failed to retrieve {%s}. HTTP status: %d\nError message: \"%s\"",
                    array($request->getURL(),$response->getStatus(), $response->getReasonPhrase())
                );
            }
        }
    }
}

/******************************************************/
/************* Wikidot entity classes *****************/
/******************************************************/

// Class with properties of a single revision
class WikidotRevision
{
    /*** Fields ***/    
    // Wikidot revision id
    protected $revisionId;
    // Wikidot page id
    protected $pageId;
    // Zero-based index in the list of revisions of the page
    protected $index;
    // WikidotUser object - author of the revision
    protected $user;
    // Date and time revision was submitted
    protected $dateTime;
    // Comments
    protected $comments;
    
    /*** Protected ***/    
    // Name of class for user object for overriding in descendants
    protected function getUserClass()
    {
        return 'WikidotUser';
    }

    /*** Public ***/
    public function __construct($pageId)
    {
        $this->pageId = $pageId;
    }
    
    // Extract information about revision from a html element
    public function extractFrom(phpQueryObject $rev)
    {
        preg_match('/\d+/', $rev->attr('id'), $ids);
        $this->revisionId = (int)$ids[0];
        $this->index = (int)substr(trim(pq('td:first', $rev)->text()), 0, -1);
        $userClass = $this->getUserClass();
        $this->user = new $userClass(); 
        $this->user->extractFrom(pq('span.printuser', $rev));
        $this->dateTime = WikidotUtils::extractDateTime(pq('span.odate', $rev));
        $this->comments = trim(pq('td:last', $rev)->text());    
    }
    
    /*** Access methods ***/    
    // Wikidot revision id
    public function getId()
    {
        return $this->revisionId;
    }
    
    // Wikidot page id
    public function getPageId()
    {
        return $this->pageId;
    }
    
    // Zero-based index in the list of revisions of the page
    public function getIndex()
    {
        return $this->index;
    }
    
    // WikidotUser object - author of the revision
    public function getUser()
    {
        return $this->user;
    }
    
    // Id of the author
    public function getUserId()
    {
        if ($this->user) {
            return $this->user->getId();
        }
    }
    
    // Date and time revision was submitted
    public function getDateTime()
    {
        return $this->dateTime;
    }
    
    // Comments
    public function getComments()
    {
        return $this->comments;
    }
}

// Class with properties of a single wikidot page
class WikidotPage
{
    /*** Fields ***/
    // Wikidot site name, e.g. 'scp-wiki'
    private $siteName;
    // Wikidot page name (url), e.g. 'scp-307', 'the-czar-cometh'
    private $pageName;
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
    // (UserId => WikidotUser)
    protected $retrievedUsers;

    /*** Private ***/
    
    // Extract all properties from a loaded page HTML (not including info from modules)
    private function extractPageInfo($html, WikidotLogger $logger = null)
    {
        if (!$html)
            return;        
        $doc = phpQuery::newDocument($html);
        $this->setProperty('siteId', WikidotUtils::extractSiteId($html));        
        if (!$this->getSiteId()) {
            WikidotLogger::logFormat(
                $logger, 
                "Failed to extract SiteId for page {http://%s.wikidot.com/%s}", 
                array($this->getSiteName(), $this->getPageName())
            );
            return;
        }

        $this->setProperty('categoryId', WikidotUtils::extractCategoryId($html));
        if (!$this->getCategoryId()) {
            WikidotLogger::logFormat(
                $logger, 
                "Failed to extract CategoryId for page {http://%s.wikidot.com/%s}", 
                array($this->getSiteName(), $this->getPageName())
            );
            return;
        }

        $this->setProperty('pageId', WikidotUtils::extractPageId($html));
        if (!$this->getId()) {
            WikidotLogger::logFormat(
                $logger, 
                "Failed to extract PageId for page {http://%s.wikidot.com/%s}", 
                array($this->getSiteName(), $this->getPageName())
            );
            return;
        }
        
        // Extract page name in case we were redirected
        $newName = WikidotUtils::extractPageName($html);
        if ($newName != $this->getPageName()) {            
            WikidotLogger::logFormat($logger, 'Redirect detected: "%s" -> "%s"', array($this->getPageName(), $newName));
            $this->setProperty('pageName', $newName);
        }
        
        // Extract last revision number
        $pageInfo = pq('div#page-info', $doc);        
        if ($pageInfo && preg_match('/\d+/', $pageInfo->text(), $matches)) {
            $this->lastRevision = (int)$matches[0];
        } /*else {            
            WikidotLogger::logFormat(
                $logger, 
                "Failed to extract latest revision for page {http://%s.wikidot.com/%s}", 
                array($this->siteName, $this->pageName)
            );
        }*/

        // Extract page title
        $titleElem = pq('div#page-title', $doc);
        if ($titleElem) {
            $this->setProperty('title', trim($titleElem->html()));
        } else {
            WikidotLogger::logFormat(
                $logger, 
                "Failed to extract title for page {http://%s.wikidot.com/%s}", 
                array($this->getSiteName(), $this->getPageName())
            );
            return;
        }

        // Extract page tags
        $tags = array();
        $tagsElem = pq('div.page-tags', $doc);
        if ($tagsElem) {
            foreach (pq('a', $tagsElem) as $tagElem) {
                $tags[] = pq($tagElem)->html();
            }
            $this->setProperty('tags', $tags);
        } else {
            WikidotLogger::logFormat(
                $logger, 
                "Failed to extract tags for page {http://%s.wikidot.com/%s}", 
                array($this->getSiteName(), $this->getPageName())
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
        return 'WikidotRevision';
    }

    // Name of class for user object
    protected function getUserClass()
    {
        return 'WikidotUser';
    }

    // Informs the object that it was changed
    protected function changed($message = null)
    {
        // Do nothing
    }
    
    // Add revision to the list
    protected function addRevision(WikidotRevision $revision)
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
    public function retrievePageSource(WikidotLogger $logger = null)
    {
        $res = false;
        $html = WikidotUtils::requestModule($this->getSiteName(), 'viewsource/ViewSourceModule', $this->getId(), array(), $logger);
        if ($html) {
            $doc = phpQuery::newDocument($html);
            $elem = pq('div.page-source', $doc);
            if ($elem) {
                $this->setProperty('source', $elem->text());
                $res = true;
            }
            $doc->unloadDocument();
        }
        if (!$res) {
            WikidotLogger::logFormat(
                $logger, 
                "Failed to retrieve source for page {http://%s.wikidot.com/%s}", 
                array($this->getSiteName(), $this->getPageName())
            );
        }
        return $res;
    }

    // Request a whoRated module from wikidot and extract votes from it
    public function retrievePageVotes(WikidotLogger $logger = null)
    {
        $res = false;
        $html = WikidotUtils::requestModule($this->getSiteName(), 'pagerate/WhoRatedPageModule', $this->getId(), array(), $logger);
        if ($html) {
            $doc = phpQuery::newDocument($html);
            $votes = array();
            foreach (pq('span.printuser', $doc) as $userElem) {
                $userClass = $this->getUserClass();
                $user = new $userClass();
                if ($user->extractFrom(pq($userElem))) {
                    $this->retrievedUsers[$user->getId()] = $user;
                    $voteElem = pq($userElem)->next();
                    $vote = ((trim(pq($voteElem)->text())) == '+')?1:-1;
                    $votes[(string)$user->getId()] = $vote;
                }
            }
            $this->setProperty('votes', $votes);
            $doc->unloadDocument();
            $res = true;
        }
        if (!$res) {
            WikidotLogger::logFormat(
                $logger, 
                "Failed to retrieve votes for page {http://%s.wikidot.com/%s}", 
                array($this->getSiteName(), $this->getPageName())
            );
        }
        return $res;
    }

    // Request a history module for the page and extract information from it (posterId, creation date and last revision info)
    public function retrievePageHistory(WikidotLogger $logger = null)
    {
        $revisions = array();
        $res = false;
        $args = array(
            // Here's hoping nobody has that much revisions on a single page
            'perpage' => 1000000,
            'options' => '{"all":true}'
        );
        $pageIterator = WikidotUtils::iteratePagedModule($this->getSiteName(), 'history/PageRevisionListModule', $this->getId(), $args, null, $logger);
        foreach ($pageIterator as $html) {
            $doc = phpQuery::newDocument($html);
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
            WikidotLogger::logFormat(
                $logger, 
                "Failed to retrieve votes for page {http://%s.wikidot.com/%s}", 
                array($this->getSiteName(), $this->getPageName())
            );
        }
        return $res;
    }

    // Retrieve only html of the page itself and extract information from it
    public function retrievePageInfo(WikidotLogger $logger = null)
    {        
        try {
            $html = WikidotUtils::requestPage($this->getSiteName(), $this->getPageName(), $logger);            
            if (!$html)
                return false;            
            if (!$this->extractPageInfo($html, $logger)) {
                return false;
            } else {
                unset($html);
            }
        } catch (Exception $e) {
            WikidotLogger::logFormat(
                $logger,
                "Failed to retrieve page info {%s/%s}\nError message: \"%s\"",
                array($this->getSiteName(), $this->getPageName(), $e->getMessage())
            );
            return false;
        }
        return true;
    }

    // Retrieve modules for page (votes, source, history) and extract data from them
    public function retrievePageModules(WikidotLogger $logger = null)
    {
        try {
            if (!$this->retrievePageSource($logger))
                return false;
            if (!$this->retrievePageVotes($logger))
                return false;
            if (!$this->retrievePageHistory($logger))
                return false;
        } catch (Exception $e) {
            WikidotLogger::logFormat(
                $logger,
                "Failed to retrieve page modules {%s/%s}\nError message: \"%s\"",
                array($this->getSiteName(), $this->getPageName(), $e->getMessage())
            );
            return false;
        }
        return true;
    }

    // Open page by http using siteName and pageName and retrieve all information about it,making additional calls to modules
    public function retrieveAll(WikidotLogger $logger = null)
    {
        return $this->retrievePageInfo($logger) && $this->retrievePageModules($logger);
    }

    // Update info fomr other object of the same id
    public function updateFrom(WikidotPage $page)
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
        
    // Users retrieved along with modules (UserId => WikidotUser)
    public function getRetrievedUsers() {
        if (!is_array($this->retrievedUsers)) {
            $this->retrievedUsers = array();
        }
        return $this->retrievedUsers;
    }
}

// Class containing a list of pages of a single wikidot website
class WikidotPageList
{
    /*** Fields ***/
    // Wikidot name of the site
    private $siteName;
    // Array of (PageId => WikidotPage)    
    protected $pages;
    // Array of (PageName)
    protected $failedPages;

    /*** Protected ***/
    // Returns class of pages
    protected function getPageClass()
    {
        return 'WikidotPage';
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
    }

    // Add page to the list or update existing page
    public function addPage(WikidotPage $page)
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
    
    // Returns list of pages without retrieving pages itself 
    public function fetchListOfPages($criteria, WikidotLogger $logger = null)
    {
        $res = array();
        $startTime = microtime(true);
        WikidotLogger::logFormat($logger, "::: Retrieving list of pages from %s.wikidot.com :::", array($this->siteName));
        try {
            $defaults = array(
                'offset' => 0,
                'page' => 1,
                'category' => '_default',
                'order' => 'title',
                'module_body' => '%%title_linked%%',
                'perPage' => 250);
            // ToDo: Validate criteria
            if (is_array($criteria)) {
                $args = array_merge($defaults, $criteria);
            } else {
                $args = $defaults;
            }
            $i = 0;
            $count = 0;
            $list = WikidotUtils::iteratePagedModule($this->siteName, 'list/ListPagesModule', 0, $args, null, $logger);
            while ($list->valid()) {
                $listPage = $list->current();
                $args['offset'] = (++$i)*250;
                $list->send($args);
                if ($listPage) {
                    $doc = phpQuery::newDocument($listPage);
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
        } catch (Exception $e) {
            WikidotLogger::logFormat($logger, "Error: \"%s\"", array($e->getMessage()));
            throw $e;
        }
        $totalTime = (microtime(true) - $startTime);
        if ($res) {
            WikidotLogger::logFormat(
                $logger,
                "::: Finished. Retrieved: %d. Time: %.3f sec :::",
                array(count($res), $totalTime)
            );
        } else {
            WikidotLogger::log($logger, "::: Failed :::");
        }
        return $res;
    }
    
    // Add pages fitting specified criteria
    public function retrievePages($criteria, $limit = 0, WikidotLogger $logger = null)
    {
        $res = false;
        $success = 0;
        $fail = 0;
        $count = 0;
        $finished = false;
        $startTime = microtime(true);
        WikidotLogger::logFormat($logger, "::: Retrieving pages from %s.wikidot.com :::", array($this->siteName));
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
            WikidotLogger::logFormat($logger, "Error: \"%s\"", array($e->getMessage()));
            $res = false;
        }
        $totalTime = (microtime(true) - $startTime);
        if ($res) {
            WikidotLogger::logFormat(
                $logger,
                "::: Finished. Retrieved: %d. Failed: %d. Time: %.3f sec :::",
                array($success, $fail, $totalTime)
            );
        } else {
            WikidotLogger::logFormat(
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
     * @param WikidotLogger $logger
     */
    public function retrieveList(&$list, $retrieveAll, WikidotLogger $logger = null)
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
        WikidotLogger::logFormat($logger, "Retrieved %d of %d requested pages", array($success, $success+count($list)));
    }
    
    // Retry loading failed pages
    public function retryFailed($retrieveAll, WikidotLogger $logger = null)
    {
        if (!$this->hasFailed()) {
            return;
        }
        $this->retrieveList($this->failedPages, $retrieveAll, $logger);
        // WikidotLogger::logFormat($logger, "Retrieved %d of %d earlier failed pages", array($success, $success+count($this->failedPages)));
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
    
    // If list has pages it failed to retrieve
    public function hasFailed()
    {
        return (is_array($this->failedPages) && count($this->failedPages) > 0);
    }
}

// Wikidot user account
class WikidotUser
{
    /*** Fields ***/
    // Wikidot id
    private $userId;
    // Deleted account
    private $deleted;
    // Displayed name, e.g. "Kain Pathos Crow"
    private $displayName;
    // Wikidot inner name used in links, e.g. "kain-pathos-crow"
    private $wikidotName;

    // Set value by property name
    protected function setProperty($name, $value)
    {
        if ($name == "userId" && is_int($value) && $this->userId !== $value) {
            $this->userId = $value;
            $this->changed();
        } elseif ($name == "deleted" && is_bool($value) && $this->deleted !== $value) {
            $this->deleted = $value;
            $this->changed();
        } elseif ($name == "displayName" && is_string($value) && $this->displayName !== $value) {
            $this->displayName = $value;
            $this->changed();
        } elseif ($name == "wikidotName" && is_string($value) && $this->wikidotName !== $value) {
            $this->wikidotName = $value;
            $this->changed();
        }
    }    
    
    // Informs the object that it was changed
    protected function changed()
    {
        // Do nothing
    }
    
    /*** Public ***/
    
    // Extract information about user from a html element
    public function extractFrom(phpQueryObject $elem)
    {
        if ($elem && $elem->hasClass('printuser')) {
            if ($elem->hasClass('avatarhover')) {
                if ($link = pq('a:last', $elem)) {
                    if (preg_match('/\d+/', $link->attr('onclick'), $matches)) {
                        $this->setProperty('userId', (int)$matches[0]);
                        $this->setProperty('deleted', false);
                        $temp = explode('/', $link->attr('href'));
                        $this->setProperty('wikidotName', end($temp));
                        $this->setProperty('displayName', $link->text());
                        return true;
                    }
                }
            } elseif ($elem->hasClass('deleted')) {
                $this->setProperty('userId', (int)$elem->attr('data-id'));
                $this->setProperty('deleted', true);
                $this->setProperty('wikidotName', 'deleted-account');
                $this->setProperty('displayName', 'Deleted Account');
                return true;
            } elseif ($elem->hasClass('anonymous')) {
                $this->setProperty('userId', -1);
                $this->setProperty('deleted', true);
                $this->setProperty('wikidotName', 'anonymous-user');
                $this->setProperty('displayName', 'Anonymous User');
                return true;                
            }
        }
        return false;
    }
    
    // Add information from a different object of the same class and $id
    public function updateFrom(WikidotUser $user)
    {
        if (!$user || ($user->getId() != $this->getId()))
            return;
        if ($user->deleted) {
            $this->setProperty('$deleted', true);
        } else {
            if ($user->displayName) {
                $this->setProperty('displayName', $user->displayName);
            }
            if ($user->wikidotName) {
                $this->setProperty('wikidotName', $user->wikidotName);
            }
        }
    }
        
    /*** Access methods ***/
    // Returns id
    public function getId()
    {
        return $this->userId;
    }

    // Is account deleted
    public function getDeleted()
    {
        return $this->deleted;
    }
    
    // Displayed name, e.g. "Kain Pathos Crow"
    public function getDisplayName()
    {
        return $this->displayName;
    }
    
    // Wikidot inner name used in links, e.g. "kain-pathos-crow"
    public function getWikidotName()
    {
        return $this->wikidotName;
    }
}

// List of wikidot user accounts
class WikidotUserList
{
    /*** Fields ***/
    // Wikidot name of site
    private $siteName;
    // Wikidot id of site
    protected $siteId;
    // Array of (UserId (int) => array('User' => WikidotUser, 'JoinDate' => DateTime)
    protected $users;

    /*** Protected ***/
    // Name of class used for user objects
    protected function getUserClass()
    {
        return 'WikidotUser';
    }
    
    /*** Public ***/
    public function __construct($siteName)
    {
        $this->siteName = $siteName;
        $this->users = array();
    }

    // Load members list for a specified site and add all users from it
    public function retrieveSiteMembers(WikidotLogger $logger = null)
    {
        $res = true;
        $success = 0;
        $startTime = microtime(true);
        WikidotLogger::logFormat($logger, "::: Retrieving list of members from %s.wikidot.com :::", array($this->siteName));
        try {
            $membersHtml = WikidotUtils::requestPage($this->siteName, 'system:members', $logger);
            $this->siteId = WikidotUtils::extractSiteId($membersHtml);
            if ($membersHtml && $this->siteId) {
                $pageCount = WikidotUtils::extractPageCount($membersHtml);
                $memberList = WikidotUtils::iteratePagedModule($this->siteName, 'membership/MembersListModule', 0, array(), range(1, $pageCount), $logger);
                foreach ($memberList as $mlPage) {
                    if ($mlPage) {
                        $doc = phpQuery::newDocument($mlPage);
                        foreach (pq('tr') as $row) {
                            $userClass = $this->getUserClass();
                            $user = new $userClass();
                            if ($user->extractFrom(pq('span.printuser', $row))) {
                                $joinDate = WikidotUtils::extractDateTime(pq('span.odate', $row));
                                //$user->setJoinDate($siteId, $joinDate);
                                $this->addUser($user, $joinDate);
                                $success++;
                            }
                        }
                        $doc->unloadDocument();
                        if ($success % 1000 == 0) {
                            WikidotLogger::logFormat(
                                $logger, 
                                "%d members retrieved [%d kb used]...", 
                                array($success, round(memory_get_usage()/1024))
                            );
                        }
                            // return;
                    }
                    else {
                        $res = false;
                        break;
                    }
					// debug
//					sleep(1);
                }
            }
        } catch (Exception $e) {
            WikidotLogger::logFormat($logger, "Error: \"%s\"", array($e->getMessage()));
            throw $e;
        }
        $totalTime = (microtime(true) - $startTime);
        if ($res) {
            WikidotLogger::logFormat(
                $logger,
                "::: Finished. Retrieved: %d. Time: %.3f sec :::",
                array($success, $totalTime)
            );
        } else {
            WikidotLogger::logFormat(
                $logger,
                "::: Failed. Retrieved: %d. Time: %.3f sec :::",
                array($success, $totalTime)
            );
        }
        return $res;
    }

    // Add a user to the list
    public function addUser(WikidotUser $user, DateTime $date = null)
    {
        $usr = array('User' => $user, 'Date' => $date);
        if (array_key_exists($user->getId(), $this->users)) {
            $old = &$this->users[$user->getId()];
            $old['User']->updateFrom($user);
            if ($date) {
                $old['Date'] = $date;
            }
        } else {
            $this->users[$user->getId()] = array('User' => $user, 'Date' => $date);
        }
    }
    
    // Add users associated with page
    public function addUsersFromPage(WikidotPage $page)
    {
        foreach($page->getRetrievedUsers() as $userId => $user) {
            $this->addUser($user);
        }
    }
    
    // Wikidot name of site
    public function getSiteName()
    {
        return $this->siteName;
    }
    
    // Get user by it's WikidotId
    public function getUserById($id)
    {
        if (isset($this->users[$id])) {
            return $this->users[$id]['User'];
        } else {
            return null;
        }
    }
    
    // Get user by display name
    public function getUserByDisplayName($name)
    {
        foreach ($this->users as $user) {
            if ($user['User']->getDisplayName() == $name) {
                return $user['User'];
            }
        }
        return null;
    }
}

?>
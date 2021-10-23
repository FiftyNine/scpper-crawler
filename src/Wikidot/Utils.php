<?php

namespace ScpCrawler\Wikidot;

use ScpCrawler\Logger\Logger;

// Utility class with functions to retrieve pages and modules
class Utils
{

    /*** Fields ***/
    // Max connection time, sec
    public static $connectionTimeout = 10;
    // Max request time, sec
    public static $requestTimeout = 30;
    // Max number of attempts for a single page
    public static $maxAttempts = 3;
    // Protocol used for requests
    public static $protocol = 'http';

    /*** Private ***/
    // Create and setup a new request
    private static function createRequest($url)
    {
        $request = new \HTTP_Request2($url, \HTTP_Request2::METHOD_GET);
        $request->setConfig('follow_redirects', true);
        $request->setConfig('max_redirects', 2);
        $request->setConfig('strict_redirects', true);
        $request->setConfig('connect_timeout', self::$connectionTimeout);
        $request->setConfig('timeout', self::$requestTimeout);
        return $request;
    }
    
    // Sends request at most $maxAttempts times, returns response object
    private static function sendRequest($request, Logger $logger = null)
    {
        $response = null;
        try {
            $i = 0;
            while ($i < self::$maxAttempts) {
                try {
                    $response = $request->send();
                } catch (\HTTP_Request2_Exception $e) {
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
        } catch (\HTTP_Request2_Exception $e) {
            Logger::logFormat($logger, "Failed to retrieve {%s}\nUnexpected error: \"%s\"", array($request->getURL(), $e->getMessage()));
            return null;
        }
        return $response;
    }
    
    
    /*** Public ***/

    // Searches for a pager in html and returns number of pages in it. 1 if pager is not found
    public static function extractPageCount($html)
    {
        $res = 1;
        $doc = \phpQuery::newDocument($html);
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

    // Extracts a \DateTime object from a DOM element if it has the right class. Returns null otherwise
    public static function extractDateTime(\phpQueryObject $elem)
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
    public static function iteratePagedModule($siteName, $moduleName, $pageId, $args, $pageNumbers = null, Logger $logger = null)
    {
        $changedArgs = array();
        if (!is_array($pageNumbers) || count($pageNumbers) == 0) {
            $pageNumbers = array();
            $args['page'] = 1;
            $firstPage = null;
            self::requestModule($siteName, $moduleName, $pageId, $args, $firstPage, $logger);
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
                $modulePage = null;
                self::requestModule($siteName, $moduleName, $pageId, $args, $modulePage, $logger);
                $changedArgs = (yield $modulePage);
            }
        }
    }

    // Request a specified module from wikidot. Returns HTML string.
    public static function requestModule($siteName, $moduleName, $pageId, $args, &$html, Logger $logger = null)
    {
        $html = null;
        $status = PageStatus::FAILED;
        $fullUrl = sprintf('%s://%s.wikidot.com/ajax-module-connector.php', self::$protocol, $siteName);
        if (!is_array($args)) {
            $args = array();
        }
        $args['moduleName'] = $moduleName;
        $args['pageId'] = $pageId;
        $args['page_id'] = $pageId;
        $args['wikidot_token7'] = 'ayylmao';
        $request = self::createRequest($fullUrl);
        $request->setMethod(\HTTP_Request2::METHOD_POST);
        $request->setHeader(sprintf('Referer: %s://%s.wikidot.com', self::$protocol, $siteName));        
        $request->addPostParameter($args);
        $request->addCookie('wikidot_token7', 'ayylmao');
        if ($response = self::sendRequest($request, $logger)) {
            $httpStatus = $response->getStatus();
            if ($httpStatus >= 200 && $httpStatus < 300) {
                $body = $response->getBody();
                $body = json_decode($body);
                if ($body && $body->status == 'ok' && isset($body->body)) {
                    $status = PageStatus::OK;
                    $html = $body->body;
                } elseif ($body->status == 'not_ok') {
                    Logger::logFormat(
                        $logger,
                        "Failed to retrieve module {%s/%s}\nWikidot error: \"%s\"\nArguments: %s",
                        array($siteName, $moduleName, $body->message, var_export($args, true))
                    );
                } else {
                    Logger::logFormat(
                        $logger,
                        "Failed to retrieve module {%s/%s}\nUnknown error\nArguments: %s",
                        array($siteName, $moduleName, var_export($args, true))
                    );
                }
            } elseif ($httpStatus >= 300 && $httpStatus < 400) {
                $status = PageStatus::REDIRECT;
                Logger::logFormat(
                    $logger,
                    "Failed to retrieve module {%s/%s}\nRedirect detected. HTTP status: %d\nArguments: %s",
                    array($siteName, $moduleName, $status, var_export($args, true))
                );
            } else {
                if ($httpStatus == 404) {
                    $status = PageStatus::NOT_FOUND;
                }
                Logger::logFormat(
                    $logger,
                    "Failed to retrieve module {%s %s}\nHTTP status: %d. Error message: \"%s\"\nArguments: %s",
                    array($siteName, $moduleName, $response->getStatusCode(), $response->getReasonPhrase(), var_export($args, true))
                );
            }
        }
        return $status;
    }

    // Request a specified page from wikidot. Returns HTML string in $source and status as return value
    public static function requestPage($siteName, $pageName, &$source, Logger $logger = null)
    {
        $source = null;
        $fullUrl = sprintf('%s://%s.wikidot.com/%s', self::$protocol, $siteName, $pageName);
        $request = self::createRequest($fullUrl);
        $request->setConfig('use_brackets', true);
        $status = PageStatus::FAILED;
        if ($response = self::sendRequest($request, $logger)) {
            $httpStatus = $response->getStatus();
            if ($httpStatus >= 200 && $httpStatus < 300) {
                $source = $response->getBody();
                $status = PageStatus::OK;
            } elseif ($httpStatus >= 300 && $httpStatus < 400) {
                Logger::logFormat(
                    $logger,
                    "Failed to retrieve page {%s}. HTTP status: %d. Redirect detected",
                    array($request->getUrl(), $httpStatus)
                );
                $status = PageStatus::REDIRECT;
            } else {
                Logger::logFormat(
                    $logger,
                    "Failed to retrieve {%s}. HTTP status: %d\nError message: \"%s\"",
                    array($request->getUrl(), $httpStatus, $response->getReasonPhrase())
                );
                if ($httpStatus === 404) {
                    $status = PageStatus::NOT_FOUND;
                } else {
                    $status = PageStatus::FAILED;
                }
            }
        }
        return $status;
    }
    
    // Selects default protocol for the site (https if supported, http otherwise)
    public static function selectProtocol($siteName, Logger $logger = null)
    {
        self::$protocol = 'http';
        $url = sprintf('https://%s.wikidot.com', $siteName);
        $request = self::createRequest($url);
        $request->setConfig('follow_redirects', false);
        if ($response = self::sendRequest($request, $logger)) {
            $httpStatus = $response->getStatus();
            //$httpStatus = $response->getStatusCode();
            if ($httpStatus >= 200 && $httpStatus < 300) {
                self::$protocol = 'https';
            }
        }                
        Logger::logFormat($logger, '"%s" selected as default protocol', [self::$protocol]);
    }
}
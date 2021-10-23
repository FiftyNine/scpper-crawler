<?php

namespace ScpCrawler\Scp\DbUtils;

use ScpCrawler\Logger\Logger;

// Static utility class for saving/loading pages from DB
class Page
{
    /*** Constants ***/
    // Name of view for pages
    const VIEW = 'view_pages';
    // Names of fields in view
    const VIEW_ID = '__Id';
    const VIEW_SITEID = 'SiteId';
    const VIEW_PAGEID = 'PageId';
    const VIEW_CATEGORYID = 'CategoryId';
    const VIEW_SITE_NAME = 'SiteName';
    const VIEW_PAGE_NAME = 'PageName';
    const VIEW_TITLE = 'Title';
    const VIEW_SOURCE = 'Source';
    const VIEW_ALT_TITLE = 'AltTitle';
    // Text of SQL requests
    const SELECT_TEXT = 'SELECT __Id, SiteId, SiteName, PageName, Title, AltTitle, CategoryId, Source FROM view_pages WHERE PageId = ?';
    const SELECT_ID_TEXT = 'SELECT __Id FROM view_pages_all WHERE PageId = ?';
    const INSERT_TEXT = 'INSERT INTO pages (SiteId, WikidotId, CategoryId, Name, Title, AltTitle, Source) VALUES (?, ?, ?, ?, ?, ?, ?)';
    const UPDATE_TEXT = 'UPDATE pages SET CategoryId = ?, Name = ?, Title = ?, AltTitle = ?, Source = COALESCE(?, Source), Deleted = 0 WHERE WikidotId = ?';
    // const DELETE_TEXT = 'DELETE FROM pages WHERE WikidotId = ?';
    // Now we store deleted pages and only mark them as deleted
    const DELETE_TEXT = 'UPDATE pages SET Deleted = 1 WHERE WikidotId = ?';

    /*** Fields ***/
    // Last connection used to access DB with this class
    private static $lastLink = null;
    // Prepared statements for interacting with DB
    private static $selectStmnt = null;
    private static $selectIdStmnt = null;
    private static $insertStmnt = null;
    private static $updateStmnt = null;
    private static $deleteStmnt = null;

    /*** Private ***/
    // Check if supplied connection is the same as the last time, reset statements if it's not
    private static function needStatements(KeepAliveMysqli $link)
    {
        if (self::$lastLink !== $link->getLink()) {
            self::clear();
            self::$lastLink = $link->getLink();
        }
    }

    // Close prepared statement and set them to null
    private static function closeStatement(&$statement)
    {
        if ($statement) {
            $statement->close();
        }
        $statement = null;
    }

    // Close and null all statements
    private static function clear() {
        self::closeStatement(self::$selectStmnt);
        self::closeStatement(self::$selectIdStmnt);
        self::closeStatement(self::$insertStmnt);
        self::closeStatement(self::$updateStmnt);
        self::closeStatement(self::$deleteStmnt);
    }

    // Select page data from DB by page wikidot id and return it as associative array
    public static function select(KeepAliveMysqli $link, \ScpCrawler\Scp\Page $page, Logger $logger = null)
    {

        $res = false;
        try {
            self::needStatements($link);
            if (!self::$selectStmnt) {
                self::$selectStmnt = $link->prepare(self::SELECT_TEXT);
                if (!self::$selectStmnt) {
                    Logger::logFormat(
                        $logger,
                        "Failed to prepare SELECT statement for page\nError: \"%s\"",
                        array($link->error())
                    );
                    return false;
                }
            }
            $pageId = $page->getId();
            self::$selectStmnt->bind_param('d', $pageId);
            if (self::$selectStmnt->execute()) {
                if (method_exists(self::$selectStmnt, 'get_result')) {
                    $dataset = self::$selectStmnt->get_result();
                    $res = $dataset->fetch_assoc();
                } else {
                    $dataset = iimysqli_stmt_get_result(self::$selectStmnt);
                    $res = iimysqli_result_fetch_array($dataset);
                }
                self::$selectStmnt->reset();
            } else {
                Logger::logFormat(
                    $logger,
                    "Failed to SELECT page %s://%s.wikidot.com/%s id=%d\nMysqli error: \"%s\"",
                    array(\ScpCrawler\Wikidot\Utils::$protocol, $page->getSiteName(), $page->getPageName(), $page->getId(), self::$selectStmnt->error)
                );
            }
        } catch (Exception $e) {
            Logger::logFormat(
                $logger,
                "Failed to SELECT page %s://%s.wikidot.com/%s id=%d\nError: \"%s\"",
                array(\ScpCrawler\Wikidot\Utils::$protocol, $page->getSiteName(), $page->getPageName(), $page->getId(), $e->getMessage())
            );
        }
        return $res;
    }

    // Select page dbId from DB by page wikidot id and return it
    public static function selectId(KeepAliveMysqli $link, \ScpCrawler\Scp\Page $page, Logger $logger = null)
    {

        $res = false;
        try {
            self::needStatements($link);
            if (!self::$selectIdStmnt) {
                self::$selectIdStmnt = $link->prepare(self::SELECT_ID_TEXT);
                if (!self::$selectIdStmnt) {
                    Logger::logFormat(
                        $logger,
                        "Failed to prepare SELECT ID statement for page\nError: \"%s\"",
                        array($link->error())
                    );
                    return false;
                }
            }
            $pageId = $page->getId();
            self::$selectIdStmnt->bind_param('d', $pageId);
            if (self::$selectIdStmnt->execute()) {
                self::$selectIdStmnt->bind_result($res);
                self::$selectIdStmnt->fetch();
                self::$selectIdStmnt->reset();
            } else {
                Logger::logFormat(
                    $logger,
                    "Failed to SELECT ID page %s://%s.wikidot.com/%s id=%d\nMysqli error: \"%s\"",
                    array(\ScpCrawler\Wikidot\Utils::$protocol, $page->getSiteName(), $page->getPageName(), $page->getId(), self::$selectIdStmnt->error)
                );
            }
        } catch (Exception $e) {
            Logger::logFormat(
                $logger,
                "Failed to SELECT ID page %s://%s.wikidot.com/%s id=%d\nError: \"%s\"",
                array(\ScpCrawler\Wikidot\Utils::$protocol, $page->getSiteName(), $page->getPageName(), $page->getId(), $e->getMessage())
            );
        }
        return $res;
    }

    // Insert new record into pages table in DB
    public static function insert(KeepAliveMysqli $link, \ScpCrawler\Scp\Page $page, Logger $logger = null)
    {

        $res = false;
        try {
            self::needStatements($link);
            if (!self::$insertStmnt) {
                self::$insertStmnt = $link->prepare(self::INSERT_TEXT);
                if (!self::$insertStmnt) {
                    Logger::logFormat(
                        $logger,
                        "Failed to prepare INSERT statement for page\nError: \"%s\"",
                        array($link->error())
                    );
                    return false;
                }
            }
            $arObj = array(
                'siteId' => $page->getSiteId(),
                'pageId' => $page->getId(),
                'categoryId' => $page->getCategoryId(),
                'pageName' => $page->getPageName(),
                'title' => $page->getTitle(),
                'altTitle' => $page->getAltTitle(),
                'source' => $page->getSource()
            );
            self::$insertStmnt->bind_param(
                'dddssss',
                $arObj['siteId'],
                $arObj['pageId'],
                $arObj['categoryId'],
                $arObj['pageName'],
                $arObj['title'],
                $arObj['altTitle'],
                $arObj['source']
            );
            $res = self::$insertStmnt->execute();
            if (!$res) {
                Logger::logFormat(
                    $logger,
                    "Failed to INSERT page %s://%s.wikidot.com/%s id=%d\nMysqli error: \"%s\"",
                    array(\ScpCrawler\Wikidot\Utils::$protocol, $page->getSiteName(), $page->getPageName(), $page->getId(), self::$insertStmnt->error)
                );
            }
        } catch (Exception $e) {
            Logger::logFormat(
                $logger,
                "Failed to INSERT page %s://%s.wikidot.com/%s id=%d\nError: \"%s\"",
                array(\ScpCrawler\Wikidot\Utils::$protocol, $page->getSiteName(), $page->getPageName(), $page->getId(), $e->getMessage())
            );
        }
        return $res;
    }

    // Update existing DB record by page wikidot id
    public static function update(KeepAliveMysqli $link, \ScpCrawler\Scp\Page $page, Logger $logger = null)
    {

        $res = false;
        try {
            self::needStatements($link);
            if (!self::$updateStmnt) {
                self::$updateStmnt = $link->prepare(self::UPDATE_TEXT);
                if (!self::$updateStmnt) {
                    Logger::logFormat(
                        $logger,
                        "Failed to prepare UPDATE statement for page\nError: \"%s\"",
                        array($link->error())
                    );
                    return false;
                }
            }
            $arObj = array(
                'categoryId' => $page->getCategoryId(),
                'pageName' => $page->getPageName(),
                'title' => $page->getTitle(),
                'altTitle' => $page->getAltTitle(),
                'source' => $page->getSource(),
                'pageId' => $page->getId()
            );
            self::$updateStmnt->bind_param(
                'dssssd',
                $arObj['categoryId'],
                $arObj['pageName'],
                $arObj['title'],
                $arObj['altTitle'],
                $arObj['source'],
                $arObj['pageId']
            );
            $res = self::$updateStmnt->execute();
            if (!$res) {
                Logger::logFormat(
                    $logger,
                    "Failed to UPDATE page %s://%s.wikidot.com/%s id=%d\nMysqli error: \"%s\"",
                    array(\ScpCrawler\Wikidot\Utils::$protocol, $page->getSiteName(), $page->getPageName(), $page->getId(), self::$updateStmnt->error)
                );
            }
        } catch (Exception $e) {
            Logger::logFormat(
                $logger,
                "Failed to UPDATE page %s://%s.wikidot.com/%s id=%d\nError: \"%s\"",
                array(\ScpCrawler\Wikidot\Utils::$protocol, $page->getSiteName(), $page->getPageName(), $page->getId(), $e->getMessage())
            );
        }
        return $res;
    }

    // Delete existing DB record by page wikidot id
    public static function delete(KeepAliveMysqli $link, $pageId, Logger $logger = null)
    {

        $res = false;
        try {
            self::needStatements($link);
            if (!self::$deleteStmnt) {
                self::$deleteStmnt = $link->prepare(self::DELETE_TEXT);
                if (!self::$deleteStmnt) {
                    Logger::logFormat(
                        $logger,
                        "Failed to prepare DELETE statement for page\nError: \"%s\"",
                        array($link->error())
                    );
                    return false;
                }
            }
            self::$deleteStmnt->bind_param('d', $pageId);
            $res = self::$deleteStmnt->execute();
            if (!$res) {
                Logger::logFormat(
                    $logger,
                    "Failed to DELETE pageId = %d\nMysqli error: \"%s\"",
                    array($pageId, self::$deleteStmnt->error)
                );
            }
        } catch (Exception $e) {
            Logger::logFormat(
                $logger,
                "Failed to DELETE pageId = %d\nError: \"%s\"",
                array($pageId, $e->getMessage())
            );
        }
        return $res;
    }
}
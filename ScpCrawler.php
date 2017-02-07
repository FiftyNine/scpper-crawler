<?php

require_once "WikidotCrawler.php";
require_once "utils.php";

// Static utility class for saving/loading votes from DB
class ScpVoteDbUtils
{
    /*** Constants ***/
    // Name of view for votes
    const VIEW = 'view_votes';
    // Names of fields in view
    const VIEW_ID = '__Id';
    const VIEW_PAGEID = 'PageId';
    const VIEW_USERID = 'UserId';
    const VIEW_VALUE = 'Value';
    // Text of SQL requests
    const SELECT_TEXT = 'SELECT UserId, Value FROM view_votes WHERE PageId = ?';
    const INSERT_TEXT = 'INSERT INTO votes (PageId, UserId, Value) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE Value = VALUES(Value);';
    const DELETE_TEXT = 'DELETE FROM votes WHERE PageId = ? AND UserId = ?';
    
    /*** Fields ***/
    // Last connection used to access DB with this class
    private static $lastLink = null;
    // Prepared statements for interacting with DB
    private static $selectStmnt = null;
    private static $insertStmnt = null;    
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
        self::closeStatement(self::$insertStmnt);
        self::closeStatement(self::$deleteStmnt);
    }

    // Select all votes from DB by page wikidot id and return them in $votes as (userId -> vote). Returns true/false
    public static function select(KeepAliveMysqli $link, $pageId, &$votes, WikidotLogger $logger = null)
    {
        $res = false;
        try {
            self::needStatements($link);
            if (!self::$selectStmnt) {
                self::$selectStmnt = $link->prepare(self::SELECT_TEXT);
                if (!self::$selectStmnt) {
                    WikidotLogger::logFormat(
                        $logger,
                        "Failed to prepare SELECT statement for votes\nError: \"%s\"",
                        array($link->error())
                    );
                    return false;
                }                
            }
            self::$selectStmnt->bind_param('d', $pageId);
            if (self::$selectStmnt->execute()) {
                self::$selectStmnt->bind_result($userId, $vote);
                while (self::$selectStmnt->fetch()) {
                    $votes[$userId] = $vote;
                }
                self::$selectStmnt->reset();
                $res = true;
            } else {
                WikidotLogger::logFormat(
                    $logger,
                    "Failed to SELECT votes of pageId %d\nMysqli error: \"%s\"",
                    array($pageId, self::$selectStmnt->error)
                );                
            }
        } catch (Exception $e) {
            WikidotLogger::logFormat(
                $logger,
                "Failed to SELECT votes of pageId %d\nError: \"%s\"",
                array($pageId, $e->getMessage())
            );
        }
        return $res;        
    }
    
    // Insert new record into votes table in DB
    public static function insert(KeepAliveMysqli $link, $pageId, $userId, $value, WikidotLogger $logger = null)
    {
        $res = false;
        try {
            self::needStatements($link);
            if (!self::$insertStmnt) {
                self::$insertStmnt = $link->prepare(self::INSERT_TEXT);
                if (!self::$insertStmnt) {
                    WikidotLogger::logFormat(
                        $logger,
                        "Failed to prepare INSERT statement for vote\nError: \"%s\"",
                        array($link->error())
                    );
                    return false;
                }                                
            }
            self::$insertStmnt->bind_param('ddd', $pageId, $userId, $value);
            $res = self::$insertStmnt->execute();
            if (!$res) {
                WikidotLogger::logFormat(
                    $logger,
                    "Failed to INSERT vote of userId %d on pageId %d\nMysqli error: \"%s\"",
                    array($userId, $pageId, self::$insertStmnt->error)
                );
            }
        } catch (Exception $e) {
            WikidotLogger::logFormat(
                $logger,
                "Failed to INSERT vote of userId %d on pageId %d\nError: \"%s\"",
                array($userId, $pageId, $e->getMessage())
            );
        }                
        return $res;            
    }        
    
    // Delete vote from table in DB by page id and user id
    public static function delete(KeepAliveMysqli $link, $pageId, $userId, WikidotLogger $logger = null)
    {
        $res = false;
        try {
            self::needStatements($link);
            if (!self::$deleteStmnt) {
                self::$deleteStmnt = $link->prepare(self::DELETE_TEXT);
                if (!self::$deleteStmnt) {
                    WikidotLogger::logFormat(
                        $logger,
                        "Failed to prepare DELETE statement for vote\nError: \"%s\"",
                        array($link->error())
                    );
                    return false;
                }                                
            }
            self::$deleteStmnt->bind_param('dd', $pageId, $userId);
            $res = self::$deleteStmnt->execute();
            if (!$res) {
                WikidotLogger::logFormat(
                    $logger,
                    "Failed to DELETE vote by userId %d on pageId %d\nMysqli error: \"%s\"",
                    array($userId, $pageId, self::$deleteStmnt->error)
                );
            }
        } catch (Exception $e) {
            WikidotLogger::logFormat(
                $logger,
                "Failed to DELETE vote by userId %d on pageId %d\nError: \"%s\"",
                array($userId, $pageId, $e->getMessage())
            );
        }                
        return $res;            
    }
}

// Static utility class for saving/loading tags from DB
class ScpTagDbUtils
{
    /*** Constants ***/
    // Name of view for tags
    const VIEW = 'view_tags';
    // Names of fields in view
    const VIEW_ID = '__Id';
    const VIEW_PAGEID = 'PageId';
    const VIEW_TAG = 'Tag';
    // Text of SQL requests
    const SELECT_TEXT = 'SELECT Tag FROM view_tags WHERE PageId = ?';
    const INSERT_TEXT = 'INSERT INTO tags (PageId, Tag) VALUES (?, ?)';
    const DELETE_TEXT = 'DELETE FROM tags WHERE PageId = ?';
    
    /*** Fields ***/
    // Last connection used to access DB with this class
    private static $lastLink = null;
    // Prepared statements for interacting with DB
    private static $selectStmnt = null;
    private static $insertStmnt = null;    
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
        self::closeStatement(self::$insertStmnt);
        self::closeStatement(self::$deleteStmnt);
    }

    // Select all tags from DB by page wikidot id and return them in $tags. Returns true/false
    public static function select(KeepAliveMysqli $link, $pageId, &$tags, WikidotLogger $logger = null)
    {
        $res = false;
        try {
            self::needStatements($link);
            if (!self::$selectStmnt) {
                self::$selectStmnt = $link->prepare(self::SELECT_TEXT);
                if (!self::$selectStmnt) {
                    WikidotLogger::logFormat(
                        $logger,
                        "Failed to prepare SELECT statement for tags\nError: \"%s\"",
                        array($link->error())
                    );
                    return false;
                }                
            }
            self::$selectStmnt->bind_param('d', $pageId);
            if (self::$selectStmnt->execute()) {
                self::$selectStmnt->bind_result($tag);
                while (self::$selectStmnt->fetch()) {
                    $tags[] = $tag;
                }
                self::$selectStmnt->reset();
                $res = true;
            } else {
                WikidotLogger::logFormat(
                    $logger,
                    "Failed to SELECT tags of pageId %d\nMysqli error: \"%s\"",
                    array($pageId, self::$selectStmnt->error)
                );                
            }
        } catch (Exception $e) {
            WikidotLogger::logFormat(
                $logger,
                "Failed to SELECT tags of pageId %d\nError: \"%s\"",
                array($pageId, $e->getMessage())
            );
        }
        return $res;        
    }
    
    // Insert new record into tags table in DB
    public static function insert(KeepAliveMysqli $link, $pageId, $tag, WikidotLogger $logger = null)
    {
        $res = false;
        try {
            self::needStatements($link);
            if (!self::$insertStmnt) {
                self::$insertStmnt = $link->prepare(self::INSERT_TEXT);
                if (!self::$insertStmnt) {
                    WikidotLogger::logFormat(
                        $logger,
                        "Failed to prepare INSERT statement for tag\nError: \"%s\"",
                        array($link->error())
                    );
                    return false;
                }                                
            }
            self::$insertStmnt->bind_param('ds', $pageId, $tag);
            $res = self::$insertStmnt->execute();
            if (!$res) {
                WikidotLogger::logFormat(
                    $logger,
                    "Failed to INSERT tag \"%s\" of pageId %d\nMysqli error: \"%s\"",
                    array($tag, $pageId, self::$insertStmnt->error)
                );
            }
        } catch (Exception $e) {
            WikidotLogger::logFormat(
                $logger,
                "Failed to INSERT tag \"%s\" of pageId %d\nError: \"%s\"",
                array($tag, $pageId, $e->getMessage())
            );
        }                
        return $res;            
    }        
    
    // Delete records from tags table in DB by page id
    public static function delete(KeepAliveMysqli $link, $pageId, WikidotLogger $logger = null)
    {
        $res = false;
        try {
            self::needStatements($link);
            if (!self::$deleteStmnt) {
                self::$deleteStmnt = $link->prepare(self::DELETE_TEXT);
                if (!self::$deleteStmnt) {
                    WikidotLogger::logFormat(
                        $logger,
                        "Failed to prepare DELETE statement for tags\nError: \"%s\"",
                        array($link->error())
                    );
                    return false;
                }                                
            }
            self::$deleteStmnt->bind_param('d', $pageId);
            $res = self::$deleteStmnt->execute();
            if (!$res) {
                WikidotLogger::logFormat(
                    $logger,
                    "Failed to DELETE tag \"%s\" of pageId %d\nMysqli error: \"%s\"",
                    array($tag, $pageId, self::$deleteStmnt->error)
                );
            }
        } catch (Exception $e) {
            WikidotLogger::logFormat(
                $logger,
                "Failed to DELETE tag \"%s\" of pageId %d\nError: \"%s\"",
                array($tag, $pageId, $e->getMessage())
            );
        }                
        return $res;            
    }
}

// Static utility class for saving/loading revisions from DB
class ScpRevisionDbUtils
{
    /*** Constants ***/
    // Name of view for revisions
    const VIEW = 'view_revisions';
    // Names of fields in view
    const VIEW_ID = '__Id';
    const VIEW_REVISIONID = 'RevisionId';
    const VIEW_REVISION_INDEX = 'RevisionIndex';
    const VIEW_PAGEID = 'PageId';
    const VIEW_PAGE_NAME = 'PageName';
    const VIEW_PAGE_TITLE = 'PageTitle';
    const VIEW_USER_ID = 'UserId';
    const VIEW_USER_NAME = 'UserName';
    const VIEW_USER_DELETED = 'UserDeleted';
    const VIEW_DATETIME = 'DateTime';
    const VIEW_COMMENTS = 'Comments';
    // Text of SQL requests
    const SELECT_TEXT = 'SELECT __Id, RevisionId, PageId, RevisionIndex, UserId, UNIX_TIMESTAMP(DateTime), Comments FROM view_revisions WHERE RevisionId = ?';
    const SELECT_ID_TEXT = 'SELECT __Id FROM view_revisions WHERE RevisionId = ?';
    const INSERT_TEXT = 'INSERT INTO revisions (WikidotId, PageId, RevisionIndex, UserId, DateTime, Comments) VALUES (?, ?, ?, ?, FROM_UNIXTIME(?), ?)';
    
    /*** Fields ***/
    // Last connection used to access DB with this class
    private static $lastLink = null;
    // Prepared statements for interacting with DB
    private static $selectStmnt = null;
    private static $selectIdStmnt = null;
    private static $insertStmnt = null;    
    
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
    }

    // Select revision data from DB by revision wikidot id and return it as associative array
    public static function select(KeepAliveMysqli $link, ScpRevision $revision, WikidotLogger $logger = null)
    {
        $res = false;
        try {
            self::needStatements($link);
            if (!self::$selectStmnt) {
                self::$selectStmnt = $link->prepare(self::SELECT_TEXT);
                if (!self::$selectStmnt) {
                    WikidotLogger::logFormat(
                        $logger,
                        "Failed to prepare SELECT statement for revisions\nError: \"%s\"",
                        array($link->error())
                    );
                    return false;
                }                
            }
            $revId = $revision->getId();
            self::$selectStmnt->bind_param('d', $revId);
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
                WikidotLogger::logFormat(
                    $logger,
                    "Failed to SELECT from DB revision #%d (id=%d) of pageId %d\nMysqli error: \"%s\"",
                    array($revision->getIndex(), $revision->getId(), $revision->getPageId(), self::$selectStmnt->error)
                );                
            }
        } catch (Exception $e) {
            WikidotLogger::logFormat(
                $logger,
                "Failed to SELECT from DB revision #%d (id=%d) of pageId %d\nError: \"%s\"",
                array($revision->getIndex(), $revision->getId(), $revision->getPageId(), $e->getMessage())
            );
        }
        return $res;        
    }

    // Select revision dbId from DB by revision wikidot id and return it
    public static function selectId(KeepAliveMysqli $link, ScpRevision $revision, WikidotLogger $logger = null)
    {
        $res = false;
        try {
            self::needStatements($link);
            if (!self::$selectIdStmnt) {
                self::$selectIdStmnt = $link->prepare(self::SELECT_ID_TEXT);
                if (!self::$selectIdStmnt) {
                    WikidotLogger::logFormat(
                        $logger,
                        "Failed to prepare SELECT ID statement for revision\nError: \"%s\"",
                        array($link->error())
                    );
                    return false;
                }
            }
            $revId = $revision->getId();
            self::$selectIdStmnt->bind_param('d', $revId);
            if (self::$selectIdStmnt->execute()) {
                self::$selectIdStmnt->bind_result($res);
                self::$selectIdStmnt->fetch();
                self::$selectIdStmnt->reset();
            } else {
                WikidotLogger::logFormat(
                    $logger,
                    "Failed to SELECT ID from DB revision #%d (id=%d) of pageId %d\nMysqli error: \"%s\"",
                    array($revision->getIndex(), $revision->getId(), $revision->getPageId(), self::$selectIdStmnt->error)
                );
            }
        } catch (Exception $e) {
            WikidotLogger::logFormat(
                $logger,
                "Failed to SELECT ID from DB revision #%d (id=%d) of pageId %d\nError: \"%s\"",
                array($revision->getIndex(), $revision->getId(), $revision->getPageId(), $e->getMessage())
            );
        }
        return $res;            
    }    
    
    // Insert new record into revisions table in DB
    public static function insert(KeepAliveMysqli $link, ScpRevision $revision, WikidotLogger $logger = null)
    {
        $res = false;
        try {
            self::needStatements($link);
            if (!self::$insertStmnt) {
                self::$insertStmnt = $link->prepare(self::INSERT_TEXT);
                if (!self::$insertStmnt) {
                    WikidotLogger::logFormat(
                        $logger,
                        "Failed to prepare INSERT statement for revision\nError: \"%s\"",
                        array($link->error())
                    );
                    return false;
                }                                
            }
            $arObj = (array)$revision;
            $arObj['userId'] = $revision->getUserId();
            $arObj['timestamp'] = $revision->getDateTime()->getTimestamp();
            self::$insertStmnt->bind_param(
                'ddddds', 
                $arObj["\0*\0revisionId"], 
                $arObj["\0*\0pageId"], 
                $arObj["\0*\0index"],
                $arObj["userId"],
                $arObj["timestamp"],
                $arObj["\0*\0comments"]
            );
            $res = self::$insertStmnt->execute();
            if (!$res) {
                WikidotLogger::logFormat(
                    $logger,
                    "Failed to INSERT into DB revision #%d (id=%d) of pageId %d\nMysqli error: \"%s\"",
                    array($revision->getIndex(), $revision->getId(), $revision->getPageId(), self::$insertStmnt->error)
                );
            }
        } catch (Exception $e) {
            WikidotLogger::logFormat(
                $logger,
                "Failed to INSERT into DB revision #%d (id=%d) of pageId %d\nError: \"%s\"",
                array($revision->getIndex(), $revision->getId(), $revision->getPageId(), $e->getMessage())
            );
        }                
        return $res;            
    }
}

// Static utility class for saving/loading pages from DB
class ScpPageDbUtils
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
    const SELECT_ID_TEXT = 'SELECT __Id FROM view_pages WHERE PageId = ?';
    const INSERT_TEXT = 'INSERT INTO pages (SiteId, WikidotId, CategoryId, Name, Title, AltTitle, Source) VALUES (?, ?, ?, ?, ?, ?, ?)';
    const UPDATE_TEXT = 'UPDATE pages SET CategoryId = ?, Name = ?, Title = ?, AltTitle = ?, Source = COALESCE(?, Source) WHERE WikidotId = ?';
    const DELETE_TEXT = 'DELETE FROM pages WHERE WikidotId = ?';

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
    public static function select(KeepAliveMysqli $link, ScpPage $page, WikidotLogger $logger = null)
    {
        
        $res = false;
        try {
            self::needStatements($link);
            if (!self::$selectStmnt) {
                self::$selectStmnt = $link->prepare(self::SELECT_TEXT);
                if (!self::$selectStmnt) {
                    WikidotLogger::logFormat(
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
                WikidotLogger::logFormat(
                    $logger,
                    "Failed to SELECT page http://%s.wikidot.com/%s id=%d\nMysqli error: \"%s\"",
                    array($page->getSiteName(), $page->getPageName(), $page->getId(), self::$selectStmnt->error)
                );
            }
        } catch (Exception $e) {
            WikidotLogger::logFormat(
                $logger,
                "Failed to SELECT page http://%s.wikidot.com/%s id=%d\nError: \"%s\"",
                array($page->getSiteName(), $page->getPageName(), $page->getId(), $e->getMessage())
            );
        }
        return $res;
    }

    // Select page dbId from DB by page wikidot id and return it
    public static function selectId(KeepAliveMysqli $link, ScpPage $page, WikidotLogger $logger = null)
    {
        
        $res = false;
        try {
            self::needStatements($link);
            if (!self::$selectIdStmnt) {
                self::$selectIdStmnt = $link->prepare(self::SELECT_ID_TEXT);
                if (!self::$selectIdStmnt) {
                    WikidotLogger::logFormat(
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
                WikidotLogger::logFormat(
                    $logger,            
                    "Failed to SELECT ID page http://%s.wikidot.com/%s id=%d\nMysqli error: \"%s\"",
                    array($page->getSiteName(), $page->getPageName(), $page->getId(), self::$selectIdStmnt->error)
                );
            }
        } catch (Exception $e) {
            WikidotLogger::logFormat(
                $logger,
                "Failed to SELECT ID page http://%s.wikidot.com/%s id=%d\nError: \"%s\"",
                array($page->getSiteName(), $page->getPageName(), $page->getId(), $e->getMessage())
            );
        }
        return $res;            
    }    
    
    // Insert new record into pages table in DB
    public static function insert(KeepAliveMysqli $link, ScpPage $page, WikidotLogger $logger = null)
    {
        
        $res = false;
        try {
            self::needStatements($link);
            if (!self::$insertStmnt) {
                self::$insertStmnt = $link->prepare(self::INSERT_TEXT);
                if (!self::$insertStmnt) {
                    WikidotLogger::logFormat(
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
                'dddsssb', 
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
                WikidotLogger::logFormat(
                    $logger,
                    "Failed to INSERT page http://%s.wikidot.com/%s id=%d\nMysqli error: \"%s\"",
                    array($page->getSiteName(), $page->getPageName(), $page->getId(), self::$insertStmnt->error)
                );
            }
        } catch (Exception $e) {
            WikidotLogger::logFormat(
                $logger,
                "Failed to INSERT page http://%s.wikidot.com/%s id=%d\nError: \"%s\"",
                array($page->getSiteName(), $page->getPageName(), $page->getId(), $e->getMessage())
            );
        }                
        return $res;
    }

    // Update existing DB record by page wikidot id
    public static function update(KeepAliveMysqli $link, ScpPage $page, WikidotLogger $logger = null)
    {
        
        $res = false;
        try {
            self::needStatements($link);
            if (!self::$updateStmnt) {
                self::$updateStmnt = $link->prepare(self::UPDATE_TEXT);
                if (!self::$updateStmnt) {
                    WikidotLogger::logFormat(
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
                WikidotLogger::logFormat(
                    $logger,
                    "Failed to UPDATE page http://%s.wikidot.com/%s id=%d\nMysqli error: \"%s\"",
                    array($page->getSiteName(), $page->getPageName(), $page->getId(), self::$updateStmnt->error)
                );            
            }
        } catch (Exception $e) {
            WikidotLogger::logFormat(
                $logger,
                "Failed to UPDATE page http://%s.wikidot.com/%s id=%d\nError: \"%s\"",
                array($page->getSiteName(), $page->getPageName(), $page->getId(), $e->getMessage())
            );
        }
        return $res;            
    }
    
    // Delete existing DB record by page wikidot id
    public static function delete(KeepAliveMysqli $link, $pageId, WikidotLogger $logger = null)
    {
        
        $res = false;
        try {
            self::needStatements($link);
            if (!self::$deleteStmnt) {
                self::$deleteStmnt = $link->prepare(self::DELETE_TEXT);
                if (!self::$deleteStmnt) {
                    WikidotLogger::logFormat(
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
                WikidotLogger::logFormat(
                    $logger,
                    "Failed to DELETE pageId = %d\nMysqli error: \"%s\"",
                    array($pageId, self::$deleteStmnt->error)
                );            
            }
        } catch (Exception $e) {
            WikidotLogger::logFormat(
                $logger,
                "Failed to DELETE pageId = %d\nError: \"%s\"",
                array($pageId, $e->getMessage())
            );
        }
        return $res;            
    }    
}

// Static utility class for saving/loading site membership from DB
class ScpMembershipDbUtils
{
    /*** Constants ***/
    // Name of view for membership
    const VIEW = 'view_membership';
    // Names of fields in view
    const VIEW_ID = '__Id';
    const VIEW_SITEID = 'SiteId';
    const VIEW_USERID = 'UserId';
    const VIEW_JOIN_DATE = 'JoinDate';
    // Text of SQL requests
    const SELECT_TEXT = 'SELECT UserId, UNIX_TIMESTAMP(JoinDate) FROM view_membership WHERE SiteId = ?';
    const INSERT_TEXT = 'INSERT INTO membership (SiteId, UserId, JoinDate) VALUES (?, ?, FROM_UNIXTIME(?)) ON DUPLICATE KEY UPDATE JoinDate = JoinDate';
    const DELETE_TEXT = 'DELETE FROM membership WHERE SiteId = ? and UserId = ?';
    /*** Fields ***/
    // Last connection used to access DB with this class
    private static $lastLink = null;
    // Prepared statements for interacting with DB
    private static $selectStmnt = null;
    private static $insertStmnt = null;
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
        self::closeStatement(self::$insertStmnt);
        self::closeStatement(self::$deleteStmnt);
    }
    
    // Select membership data from DB by site id and return it as associative array (UserId => JoinDate)
    public static function select(KeepAliveMysqli $link, $siteId, &$membership, WikidotLogger $logger = null)
    {
        
        $res = false;
        try {
            self::needStatements($link);
            if (!self::$selectStmnt) {
                self::$selectStmnt = $link->prepare(self::SELECT_TEXT);
                if (!self::$selectStmnt) {
                    WikidotLogger::logFormat(
                        $logger,
                        "Failed to prepare SELECT statement for membership\nError: \"%s\"",
                        array($link->error())
                    );
                    return false;
                }                                
            }
            $membership = array();
            self::$selectStmnt->bind_param('d', $siteId);
            if (self::$selectStmnt->execute()) {
                self::$selectStmnt->bind_result($userId, $joinTimestamp);
                while (self::$selectStmnt->fetch()) {
                    $date = new DateTime();
                    $date->setTimestamp($joinTimestamp);
                    $membership[$userId] = $date;
                }
                self::$selectStmnt->reset();                
                $res = true;
            } else {
                WikidotLogger::logFormat(
                    $logger,
                    "Failed to SELECT membership for site %d\nMysqli error: \"%s\"",
                    array($siteId, self::$selectStmnt->error)
                );
            }
        } catch (Exception $e) {
            WikidotLogger::logFormat(
                $logger,
                "Failed to SELECT membership for site %d\nError: \"%s\"",
                array($siteId, $e->getMessage())
            );
        }
        return $res;
    }
    
    // Insert new record into membership table in DB
    public static function insert(KeepAliveMysqli $link, $siteId, $userId, $joinDate, WikidotLogger $logger = null)
    {
        
        $res = false;
        try {
            self::needStatements($link);
            if (!self::$insertStmnt) {
                self::$insertStmnt = $link->prepare(self::INSERT_TEXT);
                if (!self::$insertStmnt) {
                    WikidotLogger::logFormat(
                        $logger,
                        "Failed to prepare INSERT statement for membership\nError: \"%s\"",
                        array($link->error())
                    );
                    return false;
                }
            }
            $timestamp = $joinDate->getTimestamp();
            self::$insertStmnt->bind_param('ddd', $siteId, $userId, $timestamp);
            $res = self::$insertStmnt->execute();
            if (!$res) {
                WikidotLogger::logFormat(
                    $logger,
                    "Failed to INSERT membership siteId = %d, userId = %d\nMysqli error: \"%s\"",
                    array($siteId, $userId, self::$insertStmnt->error)
                );
            }
        } catch (Exception $e) {
            WikidotLogger::logFormat(
                $logger,
                "Failed to INSERT membership siteId = %d, userId = $d\nError: \"%s\"",
                array($siteId, $userId, $e->getMessage())
            );
        }                
        return $res;
    }

    // Delete existing DB record by siteId and userId
    public static function delete(KeepAliveMysqli $link, $siteId, $userId, WikidotLogger $logger = null)
    {
        
        $res = false;
        try {
            self::needStatements($link);
            if (!self::$deleteStmnt) {
                self::$deleteStmnt = $link->prepare(self::DELETE_TEXT);
                if (!self::$deleteStmnt) {
                    WikidotLogger::logFormat(
                        $logger,
                        "Failed to prepare DELETE statement for membership\nError: \"%s\"",
                        array($link->error())
                    );
                    return false;
                }
            }
            self::$deleteStmnt->bind_param('dd', $siteId, $userId);
            $res = self::$deleteStmnt->execute();
            if (!$res) {
                WikidotLogger::logFormat(
                    $logger,
                    "Failed to DELETE user %d from site %d\nMysqli error: \"%s\"",
                    array($userId, $siteId, self::$deleteStmnt->error)
                );            
            }
        } catch (Exception $e) {
            WikidotLogger::logFormat(
                $logger,
                "Failed to DELETE user %d from site %d\nError: \"%s\"",
                array($userId, $siteId, $e->getMessage())
            );
        }
        return $res;
    }    
}

// Static utility class for saving/loading users from DB
class ScpUserDbUtils
{
    /*** Constants ***/
    // Name of view for users
    const VIEW = 'view_users';
    // Names of fields in view
    const VIEW_ID = '__Id';
    const VIEW_USERID = 'UserId';
    const VIEW_WIKIDOT_NAME = 'WikidotName';
    const VIEW_DISPLAY_NAME = 'DisplayName';
    const VIEW_DELETED = 'Deleted';
    // Text of SQL requests
    const SELECT_TEXT = 'SELECT __Id, WikidotName, DisplayName, Deleted FROM view_users WHERE UserId = ?';
    const SELECT_ID_TEXT = 'SELECT __Id FROM view_users WHERE UserId = ?';
    const INSERT_TEXT = 'INSERT INTO users (WikidotId, WikidotName, DisplayName, Deleted) VALUES (?, ?, ?, ?)';
    const UPDATE_TEXT = 'UPDATE users SET WikidotName = ?, DisplayName = ?, Deleted = ? WHERE WikidotId = ?';
    /*** Fields ***/
    // Last connection used to access DB with this class
    private static $lastLink = null;
    // Prepared statements for interacting with DB
    private static $selectStmnt = null;
    private static $selectIdStmnt = null;
    private static $insertStmnt = null;
    private static $updateStmnt = null;
    
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
    }
    
    // Select user data from DB by user wikidot id and return it as associative array
    public static function select(KeepAliveMysqli $link, ScpUser $user, WikidotLogger $logger = null)
    {
        
        $res = false;
        try {
            self::needStatements($link);
            if (!self::$selectStmnt) {
                self::$selectStmnt = $link->prepare(self::SELECT_TEXT);
                if (!self::$selectStmnt) {
                    WikidotLogger::logFormat(
                        $logger,
                        "Failed to prepare SELECT statement for user\nError: \"%s\"",
                        array($link->error())
                    );
                    return false;
                }                                
            }
            $userId = $user->getId();
            self::$selectStmnt->bind_param('d', $userId);
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
                WikidotLogger::logFormat(
                    $logger,
                    "Failed to SELECT user %s (id %d)\nMysqli error: \"%s\"",
                    array($user->getWikidotName(), $user->getId(), self::$selectStmnt->error)
                );
            }
        } catch (Exception $e) {
            WikidotLogger::logFormat(
                $logger,
                "Failed to SELECT user %s (id %d)\nError: \"%s\"",
                array($user->getWikidotName(), $user->getId(), $e->getMessage())
            );
        }
        return $res;
    }

    // Select user dbId from DB by user wikidot id and return it
    public static function selectId(KeepAliveMysqli $link, ScpUser $user, WikidotLogger $logger = null)
    {
        
        $res = false;
        try {
            self::needStatements($link);
            if (!self::$selectIdStmnt) {
                self::$selectIdStmnt = $link->prepare(self::SELECT_ID_TEXT);
                if (!self::$selectIdStmnt) {
                    WikidotLogger::logFormat(
                        $logger,
                        "Failed to prepare SELECT ID statement for user\nError: \"%s\"",
                        array($link->error())
                    );
                    return false;
                }
            }
            $userId = $user->getId();
            self::$selectIdStmnt->bind_param('d', $userId);
            if (self::$selectIdStmnt->execute()) {
                self::$selectIdStmnt->bind_result($res);
                self::$selectIdStmnt->fetch();
                self::$selectIdStmnt->reset();
            } else {
                WikidotLogger::logFormat(
                    $logger,
                    "Failed to SELECT ID user %s (id %d)\nMysqli error: \"%s\"",
                    array($user->getWikidotName(), $user->getId(), self::$selectIdStmnt->error)
                );
            }
        } catch (Exception $e) {
            WikidotLogger::logFormat(
                $logger,
                "Failed to SELECT ID user %s (id %d)\nError: \"%s\"",
                array($user->getWikidotName(), $user->getId(), $e->getMessage())
            );
        }
        return $res;            
    }    
    
    // Insert new record into users table in DB
    public static function insert(KeepAliveMysqli $link, ScpUser $user, WikidotLogger $logger = null)
    {
        
        $res = false;
        try {
            self::needStatements($link);
            if (!self::$insertStmnt) {
                self::$insertStmnt = $link->prepare(self::INSERT_TEXT);
                if (!self::$insertStmnt) {
                    WikidotLogger::logFormat(
                        $logger,
                        "Failed to prepare INSERT statement for user\nError: \"%s\"",
                        array($link->error())
                    );
                    return false;
                }
            }
            $values = array(
                'userId' => $user->getId(),
                'wikidotName' => $user->getWikidotName(),
                'displayName' => $user->getDisplayName(),
                'deleted' => $user->getDeleted()
            );
            self::$insertStmnt->bind_param(
                'dssd', 
                $values["userId"], 
                $values["wikidotName"], 
                $values["displayName"], 
                $values["deleted"]
            );
            $res = self::$insertStmnt->execute();
            if (!$res) {
                WikidotLogger::logFormat(
                    $logger,
                    "Failed to INSERT user %s (id %d)\nMysqli error: \"%s\"",
                    array($user->getWikidotName(), $user->getId(), self::$insertStmnt->error)
                );
            }
        } catch (Exception $e) {
            WikidotLogger::logFormat(
                $logger,
                "Failed to INSERT user %s (id %d)\nError: \"%s\"",
                array($user->getWikidotName(), $user->getId(), $e->getMessage())
            );
        }                
        return $res;
    }

    // Update existing DB record by user wikidot id
    public static function update(KeepAliveMysqli $link, ScpUser $user, WikidotLogger $logger = null)
    {
        
        $res = false;
        try {
            self::needStatements($link);
            if (!self::$updateStmnt) {
                self::$updateStmnt = $link->prepare(self::UPDATE_TEXT);                
                if (!self::$updateStmnt) {
                    WikidotLogger::logFormat(
                        $logger,
                        "Failed to prepare UPDATE statement for user\nError: \"%s\"",
                        array($link->error())
                    );
                    return false;
                }
            }
            $values = array(
                'userId' => $user->getId(),
                'wikidotName' => $user->getWikidotName(),
                'displayName' => $user->getDisplayName(),
                'deleted' => $user->getDeleted()
            );
            self::$updateStmnt->bind_param(
                'ssdd', 
                $values["wikidotName"], 
                $values["displayName"], 
                $values["deleted"],
                $values["userId"]
            );
            $res = self::$updateStmnt->execute();
            if (!$res) {
                WikidotLogger::logFormat(
                    $logger,
                    "Failed to UPDATE user %s (id %d)\nMysqli error: \"%s\"",
                    array($user->getWikidotName(), $user->getId(), self::$updateStmnt->error)
                );            
            }
        } catch (Exception $e) {
            WikidotLogger::logFormat(
                $logger,
                "Failed to UPDATE user %s (id %d)\nError: \"%s\"",
                array($user->getWikidotName(), $user->getId(), $e->getMessage())
            );
        }
        return $res;
    }    
}

// SCP database revision class
class ScpRevision extends WikidotRevision
{
    // Inner database id
    private $dbId;

    // Class of user objects
    protected function getUserClass()
    {
        return 'ScpUser';
    }
    
    // Save revision to DB
    public function saveToDB(KeepAliveMysqli $link, WikidotLogger $logger = null)
    {
        
        $res = false;
        try {
            if (!$this->dbId) {
                $this->dbId = ScpRevisionDbUtils::selectId($link, $this, $logger);
            }
            if (!$this->dbId) {
                $res = ScpRevisionDbUtils::insert($link, $this, $logger);
                if ($res) {
                    $this->dbId = $link->insert_id();
                }
            } else
                $res = true;
            if (!$res) {
                WikidotLogger::logFormat(
                    $logger, 
                    "Failed to save to DB revision #%d (id=%d) of pageId %d", 
                    array($this->index, $this->revisionId, $this->pageId)
               );
            }
        } catch (Exception $e) {
            WikidotLogger::logFormat(
                $logger,
                "Failed to save to DB revision #%d (id=%d) of pageId %d\nError: \"%s\"",
                array($this->index, $this->revisionId, $this->pageId, $e->getMessage())
            );            
        }
        return $res;
    }
    
    // Load revision from DB by revisionId
    public function loadFromDB(KeepAliveMysqli $link, WikidotLogger $logger = null)
    {
        
        $res = false;
        try {
            if ($data = ScpRevisionDbUtils::select($link, $this, $logger)) {
                $this->setDbValues($data);
                $res = true;
            }
        } catch (Exception $e) {
            WikidotLogger::logFormat(
                $logger,
                "Failed to load from DB revision #%d (id=%d) of pageId %d\nError: \"%s\"",
                array($this->index, $this->revisionId, $this->pageId, $e->getMessage())
            );
        }
        return $res;    
    }
    
    // Set field values from associative array returned by SELECT
    public function setDbValues($values, WikidotLogger $logger = null)
    {
        if (isset($values[ScpRevisionDbUtils::VIEW_ID]) && filter_var($values[ScpRevisionDbUtils::VIEW_ID], FILTER_VALIDATE_INT)) {
            $this->dbId = (int)$values[ScpRevisionDbUtils::VIEW_ID];
        }
        if (isset($values[ScpRevisionDbUtils::VIEW_REVISIONID]) && filter_var($values[ScpRevisionDbUtils::VIEW_REVISIONID], FILTER_VALIDATE_INT)) {
            $this->revisionId = (int)$values[ScpRevisionDbUtils::VIEW_REVISIONID];
        }
        if (isset($values[ScpRevisionDbUtils::VIEW_REVISION_INDEX]) && filter_var($values[ScpRevisionDbUtils::VIEW_REVISION_INDEX], FILTER_VALIDATE_INT) !== FALSE) {
            $this->index = (int)$values[ScpRevisionDbUtils::VIEW_REVISION_INDEX];
        }
        if (isset($values[ScpRevisionDbUtils::VIEW_PAGEID]) && filter_var($values[ScpRevisionDbUtils::VIEW_PAGEID], FILTER_VALIDATE_INT)) {
            $this->pageId = (int)$values[ScpRevisionDbUtils::VIEW_PAGEID];
        }
        if (isset($values[ScpRevisionDbUtils::VIEW_USER_ID]) && filter_var($values[ScpRevisionDbUtils::VIEW_USER_ID], FILTER_VALIDATE_INT)) {
            $this->user = new WikidotUser($values[ScpRevisionDbUtils::VIEW_USER_ID]);
        }
        if (isset($values[ScpRevisionDbUtils::VIEW_DATETIME])) {
            $this->dateTime = new DateTime($values[ScpRevisionDbUtils::VIEW_DATETIME]);
        }        
        if (isset($values[ScpRevisionDbUtils::VIEW_COMMENTS])) {
            $this->comments = $values[ScpRevisionDbUtils::VIEW_COMMENTS];
        }        
    }
}

// SCP database page class
class ScpPage extends WikidotPage
{   
    /*** Fields ***/
    
    // Inner database id 
    private $dbId;
    // Alternative title (for SCP object titles)
    private $altTitle;
    // Flag of modification since last save/load operation
    private $modified = true;
    // Hash of vote array
    private $votesHash;
    // Hash of source
    private $sourceHash;

    /*** Private ***/

    // Set field values from associative array returned by SELECT
    private function setDbValues($values, WikidotLogger $logger = null)
    {
        if (isset($values[ScpPageDbUtils::VIEW_ID]) && filter_var($values[ScpPageDbUtils::VIEW_ID], FILTER_VALIDATE_INT)) {
            $this->dbId = (int)$values[ScpPageDbUtils::VIEW_ID];
        }
        if (isset($values[ScpPageDbUtils::VIEW_SITEID]) && filter_var($values[ScpPageDbUtils::VIEW_SITEID], FILTER_VALIDATE_INT)) {
            $this->setProperty('siteId', (int)$values[ScpPageDbUtils::VIEW_SITEID]);
        }
        if (isset($values[ScpPageDbUtils::VIEW_PAGEID]) && filter_var($values[ScpPageDbUtils::VIEW_PAGEID], FILTER_VALIDATE_INT)) {
            $this->setProperty('pageId', (int)$values[ScpPageDbUtils::VIEW_PAGEID]);
        }
        if (isset($values[ScpPageDbUtils::VIEW_CATEGORYID]) && filter_var($values[ScpPageDbUtils::VIEW_CATEGORYID], FILTER_VALIDATE_INT)) {
            $this->setProperty('categoryId', (int)$values[ScpPageDbUtils::VIEW_CATEGORYID]);
        }
        if (isset($values[ScpPageDbUtils::VIEW_SITE_NAME])) {
            $this->setProperty('siteName', $values[ScpPageDbUtils::VIEW_SITE_NAME]);
        }        
        if (isset($values[ScpPageDbUtils::VIEW_PAGE_NAME])) {
            $this->setProperty('pageName', $values[ScpPageDbUtils::VIEW_PAGE_NAME]);
        }        
        if (isset($values[ScpPageDbUtils::VIEW_TITLE])) {
            $this->setProperty('title', $values[ScpPageDbUtils::VIEW_TITLE]);
        }
        if (isset($values[ScpPageDbUtils::VIEW_ALT_TITLE])) {
            $this->setProperty('altTitle', $values[ScpPageDbUtils::VIEW_ALT_TITLE]);
        }        
        if (isset($values[ScpPageDbUtils::VIEW_SOURCE])) {
            $this->setProperty('source', $values[ScpPageDbUtils::VIEW_SOURCE]);
        }
    }
    
    // Save revisions to DB
    private function saveRevisionsToDb(KeepAliveMysqli $link, WikidotLogger $logger = null)
    {
        $res = true;
        $revs = $this->getRevisions();
        if (isset($revs)) {
            foreach ($revs as $rev) {
                $res = $res && $rev->saveToDB($link, $logger);
            }
        }
        return $res;
    }
    
    // Save tags to DB
    private function saveTagsToDb(KeepAliveMysqli $link, WikidotLogger $logger = null)
    {
        $res = true;
        $tags = $this->getTags();
        if (isset($tags)) {
            ScpTagDbUtils::delete($link, $this->getId(), $logger);
            foreach ($tags as $tag) {
                $res = $res && ScpTagDbUtils::insert($link, $this->getId(), $tag, $logger);
            }
        }
        return $res;
    }
    
    // Save votes to DB
    private function saveVotesToDb(KeepAliveMysqli $link, WikidotLogger $logger = null)
    {
        $res = true;
        $votes = $this->getVotes();
        if (isset($votes)) {
            $oldVotes = array();
            if (ScpVoteDbUtils::select($link, $this->getId(), $oldVotes, $logger)) {
                foreach($votes as $userId => $vote) {
                    unset($oldVotes[$userId]);
                    $res = $res && ScpVoteDbUtils::insert($link, $this->getId(), $userId, $vote, $logger);                    
                }
                foreach ($oldVotes as $userId => $vote) {
                    $res = $res && ScpVoteDbUtils::delete($link, $this->getId(), $userId, $logger);
                }
            }
        }
        return $res;
    }    

    // Load revisions from DB
    private function loadRevisionsFromDb(KeepAliveMysqli $link, WikidotLogger $logger = null)
    {
        $revisions = array();
        $revQuery = "SELECT * FROM ".ScpRevisionDbUtils::VIEW
            ." WHERE ".ScpRevisionDbUtils::VIEW_PAGEID." = {$this->getId()}"
            ." ORDER BY ".ScpRevisionDbUtils::VIEW_REVISION_INDEX." DESC LIMIT 1";      
        if ($dataset = $link->query($revQuery)) {
            while ($row = $dataset->fetch_assoc()) {
                $rev = new ScpRevision($this->getId());
                $rev->setDbValues($row);
                $revisions[$rev->getIndex()] = $rev;
            }
            $this->setProperty('revisions', $revisions);
        } else {
            WikidotLogger::logFormat(
                $logger, 
                "Failed to load from DB revisions for page http://%s.wikidot.com/%s id=%d\nError: \"%s\"", 
                array($this->getSiteName(), $this->getPageName(), $this->pageId, $link->error())
            );
        }
    }
    
    // Load tags from DB
    private function loadTagsFromDb(KeepAliveMysqli $link, WikidotLogger $logger = null)
    {
        $tags = array();
        ScpTagDbUtils::select($link, $this->getId(), $tags, $logger);
        asort($tags);
        $this->setProperty('tags', $tags);
    }
    
    // Load votes from DB
    private function loadVotesFromDb(KeepAliveMysqli $link, WikidotLogger $logger = null)
    {
        $votes = array();
        ScpVoteDbUtils::select($link, $this->getId(), $votes, $logger);
        $this->setProperty('votes', $votes);
    }
    
    /*** Protected ***/
    
    // Class of revision objects
    protected function getRevisionClass()
    {
        return 'ScpRevision';
    }

    // Class of user objects
    protected function getUserClass()
    {
        return 'ScpUser';
    }

    // Informs the object that it was changed
    protected function changed($message = null)
    {
        $this->modified = true;
    }    

    // Set value by property name
    public function setProperty($name, $value)
    {
        if ($name == 'votes' && is_array($value)) {
            ksort($value);
            if (md5(json_encode($value)) != $this->votesHash) {
                parent::setProperty($name, $value);
            }
        } else if ($name == 'revisions') {
            if (is_array($value) && count($value) > 0) {
                $maxRev = -1;
                foreach ($value as $rev) {
                    if (is_a($rev, 'ScpRevision')) {
                        $maxRev = max($maxRev, $rev->getIndex());
                    }
                }
                if (!$this->dbId || $maxRev > $this->getLastRevision()) {
                    parent::setProperty($name, $value);
                }
            }
        } else if ($name == 'source' && $this->getSource() == null && $value !== null && $this->sourceHash !== null) {
            $newHash = md5($value);
            if ($newHash!==$this->sourceHash) {
                parent::setProperty($name, $value);
            }
        } else if ($name == 'altTitle' && $this->getAltTitle() !== $value) {
            $this->altTitle = $value;
            $this->changed();
        } else {
            parent::setProperty($name, $value);
        }
    }
    
    /*** Public ***/
    
    public function __construct ()
    {
        $args = func_get_args(); 
        $num = func_num_args(); 
        if ($num === 1 && is_int($args[0])) {
            $this->setProperty('pageId', $args[0]);
            $this->retrievedUsers = array();            
        } elseif ($num === 2 && is_string($args[0]) && is_string($args[1])) {
            parent::__construct($args[0], $args[1]);
        } elseif ($num > 0) {
            throw new Exception("Wrong number/type of arguments in ScpPage constructor");
        }
    }
        
    // Save page to DB
    public function saveToDB(KeepAliveMysqli $link, WikidotLogger $logger = null)
    {
        $res = false;
        try {
            $link->begin_transaction();
            if (!$this->dbId) {
                $this->dbId = ScpPageDbUtils::selectId($link, $this, $logger);
            }
            if ($this->dbId) {
                $res = ScpPageDbUtils::update($link, $this, $logger);
            } else {
                $res = ScpPageDbUtils::insert($link, $this, $logger);
                if ($res) {
                    $this->dbId = $link->insert_id();
                }
            }
            $res = $res && $this->saveRevisionsToDb($link, $logger);
            $res = $res && $this->saveTagsToDb($link, $logger);
            $res = $res && $this->saveVotesToDb($link, $logger);            
            if ($res) {
                $link->commit();
                $this->modified = false;
            } else {
                $link->rollback();
                WikidotLogger::logFormat(
                    $logger,
                    "Failed to save to DB page http://%s.wikidot.com/%s id=%d",
                    array($this->getSiteName(), $this->getPageName(), $this->getId())
                );
            }
        } catch (Exception $e) {
            $link->rollback();
            WikidotLogger::logFormat(
                $logger,
                "Failed to save to DB page http://%s.wikidot.com/%s id=%d\nError: \"%s\"",
                array($this->getSiteName(), $this->getPageName(), $this->getId(), $e->getMessage())
            );
        }
        return $res;        
    }
    
    // Load page to DB
    public function loadFromDB(KeepAliveMysqli $link, WikidotLogger $logger = null)
    {
        $res = false;
        try {
            if ($data = ScpPageDbUtils::select($link, $this, $logger)) {
                $this->setDbValues($data);                
                $this->loadRevisionsFromDb($link, $logger);
                $this->loadTagsFromDb($link, $logger);
                // We have to conserve memory, so instead of keeping actual votes, just keep a checksum of them
                $votes = array();
                ScpVoteDbUtils::select($link, $this->getId(), $votes, $logger);
                ksort($votes);
                $this->votesHash = md5(json_encode($votes));
                unset($votes);
                // Same for the source
                if ($this->getSource()!== null)
                {
                    $this->sourceHash = md5($this->getSource());
                    $this->setProperty('source', null);
                }
                $this->modified = false;
                $res = true;
            }
        } catch (Exception $e) {
            WikidotLogger::logFormat(
                $logger, 
                "Failed to load from DB page http://%s.wikidot.com/%s id=%d\nError: \"%s\"", 
                array($this->getSiteName(), $this->getPageName(), $this->getId(), $e->getMessage())
            );
        }
        return $res;
    }
    
    // Object was modified since the last save/load from DB
    public function getModified()
    {
        return $this->modified;
    }    
    
    // Alternative title
    public function getAltTitle()
    {
        return $this->altTitle;
    }
}

// SCP database page list class
class ScpPageList extends WikidotPageList
{
    // Array of pageIds that should be deleted from DB on next save
    private $toDelete;

    // Class of user objects
    protected function getPageClass()
    {
        return 'ScpPage';
    }
    
    // Load list of pages from DB
    public function loadFromDB($link, WikidotLogger $logger = null)
    {
        $this->pages = array();
        $this->toDelete = array();
        $startTime = microtime(true);
        $query = "SELECT ".ScpPageDbUtils::VIEW_PAGEID." FROM ".ScpPageDbUtils::VIEW." WHERE ".ScpPageDbUtils::VIEW_SITE_NAME." = '{$this->getSiteName()}'";
        WikidotLogger::logFormat($logger, "::: Loading list of pages for site %s.wikidot.com from DB", array($this->getSiteName()));
        try {
            if ($dataset = $link->query($query)) {
                $res = true;
                while ($row = $dataset->fetch_assoc()) {
                    $page = new ScpPage((int)$row[ScpPageDbUtils::VIEW_PAGEID]);
                    $page->loadFromDB($link, $logger);
                    $this->addPage($page);
					// debug
//					sleep(1);
                }
            } else {
                WikidotLogger::logFormat($logger, "::: Failed. KeepAliveMysqli error:\"%s\"", array($link->error()));
                return false;
            }
        } catch (Exception $e) {
            WikidotLogger::logFormat(
                $logger, 
                "::: Failed. Loaded %d pages before exception\nError:\"%s\"", 
                array(count($this->pages), $e->getMessage())
            );
            throw $e;
        }
        $total = microtime(true)-$startTime;
        WikidotLogger::logFormat($logger, "::: Success. Loaded %d pages in %.3f sec", array(count($this->pages), $total));
        return true;
    }
    
    // Save all pages from DB
    public function saveToDB($link, WikidotLogger $logger = null)
    {
        $changed = 0;
        $saved = 0;
        $deleted = 0;
        $startTime = microtime(true);        
        WikidotLogger::logFormat($logger, "::: Saving list of pages for site %s.wikidot.com from DB", array($this->getSiteName()));
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
                    ScpPageDbUtils::delete($link, $pageId, $logger);
                }
            }
        } catch (Exception $e) {
            WikidotLogger::logFormat(
                $logger, 
                "::: Failed. Saved %d of %d pages before failing\nError: \"%s\"", 
                array($saved, count($this->pages), $e->getMessage())
            );
            throw $e;
        }
        $total = microtime(true) - $startTime;
        WikidotLogger::logFormat(
            $logger,  
            "::: Success. Saved %d pages (%d changed, %d total) in %.3f sec", 
            array($saved, $changed, count($this->pages), $total)
        );
        return true;
    }
}

// SCP database user class
class ScpUser extends WikidotUser
{
    // Inner database id 
    private $dbId;
    // Shows if object was modified since the last save/load from DB
    private $modified = true;

    // Set field values from associative array returned by SELECT
    private function setDbValues($values, WikidotLogger $logger = null)
    {
        if (isset($values[ScpUserDbUtils::VIEW_ID]) && filter_var($values[ScpUserDbUtils::VIEW_ID], FILTER_VALIDATE_INT)) {
            $this->dbId = (int)$values[ScpUserDbUtils::VIEW_ID];
        }
        if (isset($values[ScpUserDbUtils::VIEW_USERID]) && filter_var($values[ScpUserDbUtils::VIEW_USERID], FILTER_VALIDATE_INT)) {
            $this->setProperty('userId', (int)$values[ScpUserDbUtils::VIEW_USERID]);
        }
        if (isset($values[ScpUserDbUtils::VIEW_WIKIDOT_NAME])) {
            $this->setProperty('wikidotName', $values[ScpUserDbUtils::VIEW_WIKIDOT_NAME]);
        }        
        if (isset($values[ScpUserDbUtils::VIEW_DISPLAY_NAME])) {
            $this->setProperty('displayName', $values[ScpUserDbUtils::VIEW_DISPLAY_NAME]);
        }        
        if (isset($values[ScpUserDbUtils::VIEW_DELETED])) {
            $this->setProperty('deleted', (boolean)$values[ScpUserDbUtils::VIEW_DELETED]);
        }
    }    
    
    // Informs the object that it was changed
    protected function changed()
    {
        $this->modified = true;
    }
    
    /*** Public ***/
    
    public function __construct()
    {
        $args = func_get_args(); 
        $num = func_num_args(); 
        if ($num == 1 && is_array($args[0]))
            $this->setDbValues($args[0]);
        $this->modified = false;
    }
    
    // Save user to DB
    public function saveToDB(KeepAliveMysqli $link, WikidotLogger $logger = null)
    {
        // WikidotLogger::log($logger, 'Saving user');
        $res = false;
        try {
            if (!$this->dbId) {
                $this->dbId = ScpUserDbUtils::selectId($link, $this, $logger);
            }
            if ($this->dbId) {
                $res = ScpUserDbUtils::update($link, $this, $logger);
            } else {
                $res = ScpUserDbUtils::insert($link, $this, $logger);
                if ($res) {
                    $this->dbId = $link->insert_id();
                }
            }
            if ($res) {
                $this->modified = false;
            } else {
                WikidotLogger::logFormat(
                    $logger, 
                    "Failed to save to DB user %s (id %d)", 
                    array($this->getWikidotName(), $this->getId())
                );
            }
        } catch (Exception $e) {
            WikidotLogger::logFormat(
                $logger, 
                "Failed to save to DB user %s (id %d)\nError: \"%s\"", 
                array($this->getWikidotName(), $this->getId(), $e->getMessage())
            );
        }
        return $res;
    }
    
    // Load user from DB
    public function loadFromDB(KeepAliveMysqli $link, WikidotLogger $logger = null)
    {
        try {
            if ($data = ScpUserDbUtils::select($link, $this, $logger)) {
                $this->setDbValues($data);
                $this->modified = false;
                return true;
            }
        } catch (Exception $e) {
            WikidotLogger::logFormat(
                $logger, 
                "Failed to load from DB user %s (id %d)\nError: \"%s\"", 
                array($this->getWikidotName(), $this->getId(), $e->getMessage())
            );
        }
        return false;
    }
        
    // Object was modified since the last save/load from DB
    public function getModified()
    {
        return $this->modified;
    }
}

// SCP database user list class
class ScpUserList extends WikidotUserList
{       
    // User object class
    protected function getUserClass()
    {
        return 'ScpUser';
    }

    // Save list of users to DB
    public function saveMembershipToDB($link, WikidotLogger $logger = null)
    {
        $saved = 0;
        $deleted = 0;
        $all = 0;
        $oldMembership = array();
        ScpMembershipDbUtils::select($link, $this->siteId, $oldMembership, $logger);
        foreach($this->users as $userId => $usr) {            
            if ($usr['Date']) {
                $all++;
                if (isset($oldMembership[$userId])) {
                    unset($oldMembership[$userId]);
                } else {
                    ScpMembershipDbUtils::insert($link, $this->siteId, $userId, $usr['Date'], $logger);
                    $saved++;
                }
            }
        }
        foreach ($oldMembership as $userId => $joinDate) {
            ScpMembershipDbUtils::delete($link, $this->siteId, $userId, $logger);
            $deleted++;
        }
        WikidotLogger::logFormat($logger, "Membership saved. Inserted %d, deleted %d, total members %d", array($saved, $deleted, $all));
    }
    
    // Load membership from DB
    public function loadMembershipFromDB($link, WikidotLogger $logger = null)
    {
        foreach($this->users as $userId => $usr) {
            $usr['Date'] = null;
        }
        $membership = array();
        ScpMembershipDbUtils::select($link, $this->siteId, $membership, $logger);
        foreach($membership as $userId => $joinDate) {
            $this->users[$userId]['Date'] = $joinDate;
        }
    }
    
    // Load list of users from DB
    public function loadFromDB($link, WikidotLogger $logger = null)
    {
        $res = true;
        $page = new ScpPage($this->getSiteName(), 'system:members');
        if ($page->retrievePageInfo($logger)) {
            $this->siteId = $page->getSiteId();
        } else {
            WikidotLogger::logFormat($logger, "Failed to retrieve list of users site Id for site %s", array($this->getSiteName()));
            return false;
        }
        $this->users = array();
        $startTime = microtime(true);
        WikidotLogger::log($logger, "::: Loading list of users from DB :::");
        if ($dataset = $link->query("SELECT * FROM ".ScpUserDbUtils::VIEW)) {
            while ($row = $dataset->fetch_assoc()) {
                $user = new ScpUser($row);
                $this->addUser($user);
            }
        } else {
            $res = false;
            WikidotLogger::logFormat($logger, "Failed to retrieve list of users from DB\nError: \"%s\"", array($link->error()));
        }
        $this->loadMembershipFromDB($link, $logger);
        $total = microtime(true)-$startTime;
        if ($res) {
            $members = 0;
            foreach ($this->users as $userId => $usr) {
                if (isset($usr['Date'])) {
                    $members++;
                }
            }
            WikidotLogger::logFormat(
                $logger, 
                "::: Success. Loaded %d users (%d members) in %.3f sec :::", 
                array(count($this->users), $members, $total)
            );
        } else {
            WikidotLogger::logFormat($logger, "::: Fail. Loaded %d users in %.3f sec before failing :::", array(count($this->users, $total)));
        }
    }
    
    // Save list of users to DB
    public function saveToDB($link, WikidotLogger $logger = null)
    {
        $saved = 0;
        $changed = 0;
        $startTime = microtime(true);
        WikidotLogger::log($logger, "::: Saving list of users to DB :::");
        try {
            foreach ($this->users as $usr) {
                if ($usr['User']->getModified()) {
                    $changed++;
                    if ($usr['User']->saveToDB($link, $logger)) {
                        $saved++;
                    }
                }
            }            
            $this->saveMembershipToDb($link, $logger);
        } catch (Exception $e) {
            WikidotLogger::logFormat($logger, "::: Failed. Saved %d of %d changed users\nError:\"%s\" :::", array($saved, $changed, $e->getMessage()));
            throw $e;
        }
        $total = microtime(true)-$startTime;
        WikidotLogger::logFormat($logger, "::: Success. Saved %d of %d changed users in %.3f sec :::", array($saved, $changed, $total));        
        return true;
    }
    
    // Update from website list of users and membership previously loaded from DB
    public function updateFromSite(WikidotLogger $logger = null)
    {
        $webList = new ScpUserList($this->getSiteName());        
        $webList->retrieveSiteMembers($logger);                
        foreach ($webList->users as $userId => $usr) {
            $this->addUser($usr['User'], $usr['Date']);
        }        
    }    
}

?>
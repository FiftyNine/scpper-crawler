<?php

namespace ScpCrawler\Scp\DbUtils;

use ScpCrawler\Logger\Logger;

// Static utility class for saving/loading revisions from DB
class Revision
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
    public static function select(KeepAliveMysqli $link, \ScpCrawler\Scp\Revision $revision, Logger $logger = null)
    {
        $res = false;
        try {
            self::needStatements($link);
            if (!self::$selectStmnt) {
                self::$selectStmnt = $link->prepare(self::SELECT_TEXT);
                if (!self::$selectStmnt) {
                    Logger::logFormat(
                        $logger,
                        "Failed to prepare SELECT statement for categories\nError: \"%s\"",
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
                Logger::logFormat(
                    $logger,
                    "Failed to SELECT from DB revision #%d (id=%d) of pageId %d\nMysqli error: \"%s\"",
                    array($revision->getIndex(), $revision->getId(), $revision->getPageId(), self::$selectStmnt->error)
                );
            }
        } catch (Exception $e) {
            Logger::logFormat(
                $logger,
                "Failed to SELECT from DB revision #%d (id=%d) of pageId %d\nError: \"%s\"",
                array($revision->getIndex(), $revision->getId(), $revision->getPageId(), $e->getMessage())
            );
        }
        return $res;
    }

    // Select revision dbId from DB by revision wikidot id and return it
    public static function selectId(KeepAliveMysqli $link, \ScpCrawler\Scp\Revision $revision, Logger $logger = null)
    {
        $res = false;
        try {
            self::needStatements($link);
            if (!self::$selectIdStmnt) {
                self::$selectIdStmnt = $link->prepare(self::SELECT_ID_TEXT);
                if (!self::$selectIdStmnt) {
                    Logger::logFormat(
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
                Logger::logFormat(
                    $logger,
                    "Failed to SELECT ID from DB revision #%d (id=%d) of pageId %d\nMysqli error: \"%s\"",
                    array($revision->getIndex(), $revision->getId(), $revision->getPageId(), self::$selectIdStmnt->error)
                );
            }
        } catch (Exception $e) {
            Logger::logFormat(
                $logger,
                "Failed to SELECT ID from DB revision #%d (id=%d) of pageId %d\nError: \"%s\"",
                array($revision->getIndex(), $revision->getId(), $revision->getPageId(), $e->getMessage())
            );
        }
        return $res;
    }

    // Insert new record into revisions table in DB
    public static function insert(KeepAliveMysqli $link, \ScpCrawler\Scp\Revision $revision, Logger $logger = null)
    {
        $res = false;
        try {
            self::needStatements($link);
            if (!self::$insertStmnt) {
                self::$insertStmnt = $link->prepare(self::INSERT_TEXT);
                if (!self::$insertStmnt) {
                    Logger::logFormat(
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
                Logger::logFormat(
                    $logger,
                    "Failed to INSERT into DB revision #%d (id=%d) of pageId %d\nMysqli error: \"%s\"",
                    array($revision->getIndex(), $revision->getId(), $revision->getPageId(), self::$insertStmnt->error)
                );
            }
        } catch (Exception $e) {
            Logger::logFormat(
                $logger,
                "Failed to INSERT into DB revision #%d (id=%d) of pageId %d\nError: \"%s\"",
                array($revision->getIndex(), $revision->getId(), $revision->getPageId(), $e->getMessage())
            );
        }
        return $res;
    }
}

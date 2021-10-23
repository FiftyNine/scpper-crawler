<?php

namespace ScpCrawler\Scp\DbUtils;

use ScpCrawler\Logger\Logger;

// Static utility class for saving/loading votes from DB
class Vote
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
    public static function select(KeepAliveMysqli $link, $pageId, &$votes, Logger $logger = null)
    {
        $res = false;
        try {
            self::needStatements($link);
            if (!self::$selectStmnt) {
                self::$selectStmnt = $link->prepare(self::SELECT_TEXT);
                if (!self::$selectStmnt) {
                    Logger::logFormat(
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
                Logger::logFormat(
                    $logger,
                    "Failed to SELECT votes of pageId %d\nMysqli error: \"%s\"",
                    array($pageId, self::$selectStmnt->error)
                );
            }
        } catch (Exception $e) {
            Logger::logFormat(
                $logger,
                "Failed to SELECT votes of pageId %d\nError: \"%s\"",
                array($pageId, $e->getMessage())
            );
        }
        return $res;
    }

    // Insert new record into votes table in DB
    public static function insert(KeepAliveMysqli $link, $pageId, $userId, $value, Logger $logger = null)
    {
        $res = false;
        try {
            self::needStatements($link);
            if (!self::$insertStmnt) {
                self::$insertStmnt = $link->prepare(self::INSERT_TEXT);
                if (!self::$insertStmnt) {
                    Logger::logFormat(
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
                Logger::logFormat(
                    $logger,
                    "Failed to INSERT vote of userId %d on pageId %d\nMysqli error: \"%s\"",
                    array($userId, $pageId, self::$insertStmnt->error)
                );
            }
        } catch (Exception $e) {
            Logger::logFormat(
                $logger,
                "Failed to INSERT vote of userId %d on pageId %d\nError: \"%s\"",
                array($userId, $pageId, $e->getMessage())
            );
        }
        return $res;
    }

    // Delete vote from table in DB by page id and user id
    public static function delete(KeepAliveMysqli $link, $pageId, $userId, Logger $logger = null)
    {
        $res = false;
        try {
            self::needStatements($link);
            if (!self::$deleteStmnt) {
                self::$deleteStmnt = $link->prepare(self::DELETE_TEXT);
                if (!self::$deleteStmnt) {
                    Logger::logFormat(
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
                Logger::logFormat(
                    $logger,
                    "Failed to DELETE vote by userId %d on pageId %d\nMysqli error: \"%s\"",
                    array($userId, $pageId, self::$deleteStmnt->error)
                );
            }
        } catch (Exception $e) {
            Logger::logFormat(
                $logger,
                "Failed to DELETE vote by userId %d on pageId %d\nError: \"%s\"",
                array($userId, $pageId, $e->getMessage())
            );
        }
        return $res;
    }
}
<?php

namespace ScpCrawler\Scp\DbUtils;

use ScpCrawler\Logger\Logger;

// Static utility class for saving/loading tags from DB
class Tag
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
    public static function select(KeepAliveMysqli $link, $pageId, &$tags, Logger $logger = null)
    {
        $res = false;
        try {
            self::needStatements($link);
            if (!self::$selectStmnt) {
                self::$selectStmnt = $link->prepare(self::SELECT_TEXT);
                if (!self::$selectStmnt) {
                    Logger::logFormat(
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
                Logger::logFormat(
                    $logger,
                    "Failed to SELECT tags of pageId %d\nMysqli error: \"%s\"",
                    array($pageId, self::$selectStmnt->error)
                );
            }
        } catch (Exception $e) {
            Logger::logFormat(
                $logger,
                "Failed to SELECT tags of pageId %d\nError: \"%s\"",
                array($pageId, $e->getMessage())
            );
        }
        return $res;
    }

    // Insert new record into tags table in DB
    public static function insert(KeepAliveMysqli $link, $pageId, $tag, Logger $logger = null)
    {
        $res = false;
        try {
            self::needStatements($link);
            if (!self::$insertStmnt) {
                self::$insertStmnt = $link->prepare(self::INSERT_TEXT);
                if (!self::$insertStmnt) {
                    Logger::logFormat(
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
                Logger::logFormat(
                    $logger,
                    "Failed to INSERT tag \"%s\" of pageId %d\nMysqli error: \"%s\"",
                    array($tag, $pageId, self::$insertStmnt->error)
                );
            }
        } catch (Exception $e) {
            Logger::logFormat(
                $logger,
                "Failed to INSERT tag \"%s\" of pageId %d\nError: \"%s\"",
                array($tag, $pageId, $e->getMessage())
            );
        }
        return $res;
    }

    // Delete records from tags table in DB by page id
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
                        "Failed to prepare DELETE statement for tags\nError: \"%s\"",
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
                    "Failed to DELETE tag \"%s\" of pageId %d\nMysqli error: \"%s\"",
                    array($tag, $pageId, self::$deleteStmnt->error)
                );
            }
        } catch (Exception $e) {
            Logger::logFormat(
                $logger,
                "Failed to DELETE tag \"%s\" of pageId %d\nError: \"%s\"",
                array($tag, $pageId, $e->getMessage())
            );
        }
        return $res;
    }
}
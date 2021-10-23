<?php

namespace ScpCrawler\Scp\DbUtils;

use ScpCrawler\Logger\Logger;

// Static utility class for saving/loading site membership from DB
class Membership
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
    const SELECT_TEXT = 'SELECT UserId, UNIX_TIMESTAMP(JoinDate) FROM membership WHERE SiteId = ? AND Aborted != ?';
    const INSERT_TEXT = 'INSERT INTO membership (SiteId, UserId, JoinDate) VALUES (?, ?, FROM_UNIXTIME(?)) ON DUPLICATE KEY UPDATE Aborted = 0';
    const DELETE_TEXT = 'UPDATE membership SET Aborted = 1 WHERE SiteId = ? and UserId = ?';
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
    public static function select(KeepAliveMysqli $link, $siteId, &$membership, $excludeAborted, Logger $logger = null)
    {

        $res = false;
        try {
            self::needStatements($link);
            if (!self::$selectStmnt) {
                self::$selectStmnt = $link->prepare(self::SELECT_TEXT);
                if (!self::$selectStmnt) {
                    Logger::logFormat(
                        $logger,
                        "Failed to prepare SELECT statement for membership\nError: \"%s\"",
                        array($link->error())
                    );
                    return false;
                }
            }
            $membership = array();
            // aborted is always 0 or 1, so pass 2 to select all
            if ($excludeAborted) {
                $aborted = 1;
            } else {
                $aborted = 2;
            }
            self::$selectStmnt->bind_param('dd', $siteId, $aborted);            
            if (self::$selectStmnt->execute()) {
                self::$selectStmnt->bind_result($userId, $joinTimestamp);
                while (self::$selectStmnt->fetch()) {
                    $date = new \DateTime();
                    $date->setTimestamp($joinTimestamp);
                    $membership[$userId] = $date;
                }
                self::$selectStmnt->reset();
                $res = true;
            } else {
                Logger::logFormat(
                    $logger,
                    "Failed to SELECT membership for site %d\nMysqli error: \"%s\"",
                    array($siteId, self::$selectStmnt->error)
                );
            }
        } catch (Exception $e) {
            Logger::logFormat(
                $logger,
                "Failed to SELECT membership for site %d\nError: \"%s\"",
                array($siteId, $e->getMessage())
            );
        }
        return $res;
    }

    // Insert new record into membership table in DB
    public static function insert(KeepAliveMysqli $link, $siteId, $userId, $joinDate, Logger $logger = null)
    {

        $res = false;
        try {
            self::needStatements($link);
            if (!self::$insertStmnt) {
                self::$insertStmnt = $link->prepare(self::INSERT_TEXT);
                if (!self::$insertStmnt) {
                    Logger::logFormat(
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
                Logger::logFormat(
                    $logger,
                    "Failed to INSERT membership siteId = %d, userId = %d\nMysqli error: \"%s\"",
                    array($siteId, $userId, self::$insertStmnt->error)
                );
            }
        } catch (Exception $e) {
            Logger::logFormat(
                $logger,
                "Failed to INSERT membership siteId = %d, userId = $d\nError: \"%s\"",
                array($siteId, $userId, $e->getMessage())
            );
        }
        return $res;
    }

    // Delete existing DB record by siteId and userId
    public static function delete(KeepAliveMysqli $link, $siteId, $userId, Logger $logger = null)
    {

        $res = false;
        try {
            self::needStatements($link);
            if (!self::$deleteStmnt) {
                self::$deleteStmnt = $link->prepare(self::DELETE_TEXT);
                if (!self::$deleteStmnt) {
                    Logger::logFormat(
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
                Logger::logFormat(
                    $logger,
                    "Failed to DELETE user %d from site %d\nMysqli error: \"%s\"",
                    array($userId, $siteId, self::$deleteStmnt->error)
                );
            }
        } catch (Exception $e) {
            Logger::logFormat(
                $logger,
                "Failed to DELETE user %d from site %d\nError: \"%s\"",
                array($userId, $siteId, $e->getMessage())
            );
        }
        return $res;
    }
}
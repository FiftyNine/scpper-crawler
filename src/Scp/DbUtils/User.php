<?php

namespace ScpCrawler\Scp\DbUtils;

use ScpCrawler\Logger\Logger;

// Static utility class for saving/loading users from DB
class User
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
    public static function select(KeepAliveMysqli $link, \ScpCrawler\Scp\User $user, Logger $logger = null)
    {

        $res = false;
        try {
            self::needStatements($link);
            if (!self::$selectStmnt) {
                self::$selectStmnt = $link->prepare(self::SELECT_TEXT);
                if (!self::$selectStmnt) {
                    Logger::logFormat(
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
                Logger::logFormat(
                    $logger,
                    "Failed to SELECT user %s (id %d)\nMysqli error: \"%s\"",
                    array($user->getWikidotName(), $user->getId(), self::$selectStmnt->error)
                );
            }
        } catch (Exception $e) {
            Logger::logFormat(
                $logger,
                "Failed to SELECT user %s (id %d)\nError: \"%s\"",
                array($user->getWikidotName(), $user->getId(), $e->getMessage())
            );
        }
        return $res;
    }

    // Select user dbId from DB by user wikidot id and return it
    public static function selectId(KeepAliveMysqli $link, \ScpCrawler\Scp\User $user, Logger $logger = null)
    {

        $res = false;
        try {
            self::needStatements($link);
            if (!self::$selectIdStmnt) {
                self::$selectIdStmnt = $link->prepare(self::SELECT_ID_TEXT);
                if (!self::$selectIdStmnt) {
                    Logger::logFormat(
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
                Logger::logFormat(
                    $logger,
                    "Failed to SELECT ID user %s (id %d)\nMysqli error: \"%s\"",
                    array($user->getWikidotName(), $user->getId(), self::$selectIdStmnt->error)
                );
            }
        } catch (Exception $e) {
            Logger::logFormat(
                $logger,
                "Failed to SELECT ID user %s (id %d)\nError: \"%s\"",
                array($user->getWikidotName(), $user->getId(), $e->getMessage())
            );
        }
        return $res;
    }

    // Insert new record into users table in DB
    public static function insert(KeepAliveMysqli $link, \ScpCrawler\Scp\User $user, Logger $logger = null)
    {

        $res = false;
        try {
            self::needStatements($link);
            if (!self::$insertStmnt) {
                self::$insertStmnt = $link->prepare(self::INSERT_TEXT);
                if (!self::$insertStmnt) {
                    Logger::logFormat(
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
                Logger::logFormat(
                    $logger,
                    "Failed to INSERT user %s (id %d)\nMysqli error: \"%s\"",
                    array($user->getWikidotName(), $user->getId(), self::$insertStmnt->error)
                );
            }
        } catch (Exception $e) {
            Logger::logFormat(
                $logger,
                "Failed to INSERT user %s (id %d)\nError: \"%s\"",
                array($user->getWikidotName(), $user->getId(), $e->getMessage())
            );
        }
        return $res;
    }

    // Update existing DB record by user wikidot id
    public static function update(KeepAliveMysqli $link, \ScpCrawler\Scp\User $user, Logger $logger = null)
    {

        $res = false;
        try {
            self::needStatements($link);
            if (!self::$updateStmnt) {
                self::$updateStmnt = $link->prepare(self::UPDATE_TEXT);
                if (!self::$updateStmnt) {
                    Logger::logFormat(
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
                Logger::logFormat(
                    $logger,
                    "Failed to UPDATE user %s (id %d)\nMysqli error: \"%s\"",
                    array($user->getWikidotName(), $user->getId(), self::$updateStmnt->error)
                );
            }
        } catch (Exception $e) {
            Logger::logFormat(
                $logger,
                "Failed to UPDATE user %s (id %d)\nError: \"%s\"",
                array($user->getWikidotName(), $user->getId(), $e->getMessage())
            );
        }
        return $res;
    }
}
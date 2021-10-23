<?php

namespace ScpCrawler\Scp\DbUtils;

use ScpCrawler\Logger\Logger;

// Static utility class for saving categories from DB
class Category
{
    /*** Constants ***/
    // Name of table
    const VIEW = 'view_categories';
    
    // Names of fields in view
    const VIEW_ID = '__Id';
    const VIEW_CATEGORYID = 'CategoryId';
    const VIEW_SITEID = 'SiteId';
    const VIEW_NAME = 'Name';
    const VIEW_IGNORED = 'Ignored';
    const VIEW_SITENAME = 'SiteName';
    
    // Text of SQL requests
    const INSERT_TEXT = 'INSERT INTO categories (SiteId, WikidotId, Name) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE Name = VALUES(Name);';

    /*** Fields ***/
    // Last connection used to access DB with this class
    private static $lastLink = null;
    // Prepared statements for interacting with DB
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
        self::closeStatement(self::$insertStmnt);
    }
    
    // Insert new record into votes table in DB
    public static function insert(KeepAliveMysqli $link, $siteId, $categoryId, $name, Logger $logger = null)
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
            self::$insertStmnt->bind_param('dds', $siteId, $categoryId, $name);
            $res = self::$insertStmnt->execute();
            if (!$res) {
                Logger::logFormat(
                    $logger,
                    "Failed to INSERT category %s of site %d\nMysqli error: \"%s\"",
                    array($name, $siteId, self::$insertStmnt->error)
                );
            }
        } catch (Exception $e) {
            Logger::logFormat(
                $logger,
                "Failed to INSERT category %s of site %d\nMysqli error: \"%s\"",
                array($name, $siteId, $e->getMessage())
            );
        }
        return $res;
    }
}
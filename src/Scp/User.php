<?php

namespace ScpCrawler\Scp;

use ScpCrawler\Logger\Logger;
use ScpCrawler\Scp\DbUtils\KeepAliveMysqli;

// SCP database user class
class User extends \ScpCrawler\Wikidot\User
{
    // Inner database id
    private $dbId;
    // Shows if object was modified since the last save/load from DB
    private $modified = true;

    // Set field values from associative array returned by SELECT
    private function setDbValues($values, Logger $logger = null)
    {
        if (isset($values[DbUtils\User::VIEW_ID]) && filter_var($values[DbUtils\User::VIEW_ID], FILTER_VALIDATE_INT)) {
            $this->dbId = (int)$values[DbUtils\User::VIEW_ID];
        }
        if (isset($values[DbUtils\User::VIEW_USERID]) && filter_var($values[DbUtils\User::VIEW_USERID], FILTER_VALIDATE_INT)) {
            $this->setProperty('userId', (int)$values[DbUtils\User::VIEW_USERID]);
        }
        if (isset($values[DbUtils\User::VIEW_WIKIDOT_NAME])) {
            $this->setProperty('wikidotName', $values[DbUtils\User::VIEW_WIKIDOT_NAME]);
        }
        if (isset($values[DbUtils\User::VIEW_DISPLAY_NAME])) {
            $this->setProperty('displayName', $values[DbUtils\User::VIEW_DISPLAY_NAME]);
        }
        if (isset($values[DbUtils\User::VIEW_DELETED])) {
            $this->setProperty('deleted', (boolean)$values[DbUtils\User::VIEW_DELETED]);
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
    public function saveToDB(KeepAliveMysqli $link, Logger $logger = null)
    {
        // Logger::log($logger, 'Saving user');
        $res = false;
        try {
            if (!$this->dbId) {
                $this->dbId = DbUtils\User::selectId($link, $this, $logger);
            }
            if ($this->dbId) {
                $res = DbUtils\User::update($link, $this, $logger);
            } else {
                $res = DbUtils\User::insert($link, $this, $logger);
                if ($res) {
                    $this->dbId = $link->insert_id();
                }
            }
            if ($res) {
                $this->modified = false;
            } else {
                Logger::logFormat(
                    $logger,
                    "Failed to save to DB user %s (id %d)",
                    array($this->getWikidotName(), $this->getId())
                );
            }
        } catch (Exception $e) {
            Logger::logFormat(
                $logger,
                "Failed to save to DB user %s (id %d)\nError: \"%s\"",
                array($this->getWikidotName(), $this->getId(), $e->getMessage())
            );
        }
        return $res;
    }

    // Load user from DB
    public function loadFromDB(KeepAliveMysqli $link, Logger $logger = null)
    {
        try {
            if ($data = DbUtils\User::select($link, $this, $logger)) {
                $this->setDbValues($data);
                $this->modified = false;
                return true;
            }
        } catch (Exception $e) {
            Logger::logFormat(
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

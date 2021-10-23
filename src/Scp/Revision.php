<?php

namespace ScpCrawler\Scp;

use ScpCrawler\Logger\Logger;
use ScpCrawler\Scp\DbUtils\KeepAliveMysqli;

// SCP database revision class
class Revision extends \ScpCrawler\Wikidot\Revision
{
    // Inner database id
    private $dbId;

    // Class of user objects
    protected function getUserClass()
    {
        return '\ScpCrawler\Scp\User';
    }

    // Save revision to DB
    public function saveToDB(KeepAliveMysqli $link, Logger $logger = null)
    {

        $res = false;
        try {
            if (!$this->dbId) {
                $this->dbId = DbUtils\Revision::selectId($link, $this, $logger);
            }
            if (!$this->dbId) {
                $res = DbUtils\Revision::insert($link, $this, $logger);
                if ($res) {
                    $this->dbId = $link->insert_id();
                }
            } else
                $res = true;
            if (!$res) {
                Logger::logFormat(
                    $logger,
                    "Failed to save to DB revision #%d (id=%d) of pageId %d",
                    array($this->index, $this->revisionId, $this->pageId)
               );
            }
        } catch (Exception $e) {
            Logger::logFormat(
                $logger,
                "Failed to save to DB revision #%d (id=%d) of pageId %d\nError: \"%s\"",
                array($this->index, $this->revisionId, $this->pageId, $e->getMessage())
            );
        }
        return $res;
    }

    // Load revision from DB by revisionId
    public function loadFromDB(KeepAliveMysqli $link, Logger $logger = null)
    {
        $res = false;
        try {
            if ($data = DbUtils\Revision::select($link, $this, $logger)) {
                $this->setDbValues($data);
                $res = true;
            }
        } catch (Exception $e) {
            Logger::logFormat(
                $logger,
                "Failed to load from DB revision #%d (id=%d) of pageId %d\nError: \"%s\"",
                array($this->index, $this->revisionId, $this->pageId, $e->getMessage())
            );
        }
        return $res;
    }

    // Set field values from associative array returned by SELECT
    public function setDbValues($values, Logger $logger = null)
    {
        if (isset($values[DbUtils\Revision::VIEW_ID]) && filter_var($values[DbUtils\Revision::VIEW_ID], FILTER_VALIDATE_INT)) {
            $this->dbId = (int)$values[DbUtils\Revision::VIEW_ID];
        }
        if (isset($values[DbUtils\Revision::VIEW_REVISIONID]) && filter_var($values[DbUtils\Revision::VIEW_REVISIONID], FILTER_VALIDATE_INT)) {
            $this->revisionId = (int)$values[DbUtils\Revision::VIEW_REVISIONID];
        }
        if (isset($values[DbUtils\Revision::VIEW_REVISION_INDEX]) && filter_var($values[DbUtils\Revision::VIEW_REVISION_INDEX], FILTER_VALIDATE_INT) !== FALSE) {
            $this->index = (int)$values[DbUtils\Revision::VIEW_REVISION_INDEX];
        }
        if (isset($values[DbUtils\Revision::VIEW_PAGEID]) && filter_var($values[DbUtils\Revision::VIEW_PAGEID], FILTER_VALIDATE_INT)) {
            $this->pageId = (int)$values[DbUtils\Revision::VIEW_PAGEID];
        }
        if (isset($values[DbUtils\Revision::VIEW_USER_ID]) && filter_var($values[DbUtils\Revision::VIEW_USER_ID], FILTER_VALIDATE_INT)) {
            $this->user = new \ScpCrawler\Wikidot\User($values[DbUtils\Revision::VIEW_USER_ID]);
        }
        if (isset($values[DbUtils\Revision::VIEW_DATETIME])) {
            $this->dateTime = new \DateTime($values[DbUtils\Revision::VIEW_DATETIME]);
        }
        if (isset($values[DbUtils\Revision::VIEW_COMMENTS])) {
            $this->comments = $values[DbUtils\Revision::VIEW_COMMENTS];
        }
    }
}

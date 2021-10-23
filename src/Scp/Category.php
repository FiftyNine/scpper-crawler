<?php

namespace ScpCrawler\Scp;

use ScpCrawler\Logger\Logger;
use ScpCrawler\Scp\DbUtils\KeepAliveMysqli;

// SCP database category class
class Category extends \ScpCrawler\Wikidot\Category
{
    // Inner database id
    private $dbId;

    // Save category to DB
    public function saveToDB(KeepAliveMysqli $link, Logger $logger = null)
    {
        $res = false;
        try {
            if (!$this->dbId) {
                $this->dbId = DbUtils\Category::selectId($link, $this, $logger);
            }
            if (!$this->dbId) {
                $res = DbUtils\Category::insert($link, $this, $logger);
                if ($res) {
                    $this->dbId = $link->insert_id();
                }
            } else
                $res = true;
            if (!$res) {
                Logger::logFormat(
                    $logger,
                    "Failed to save to DB category %d (%s)",
                    array($this->categoryId, $this->name)
               );
            }
        } catch (Exception $e) {
            Logger::logFormat(
                $logger,
                "Failed to save to DB category %d (%s)\nError: \"%s\"",
                array($this->categoryId, $this->name, $e->getMessage())
            );
        }
        return $res;
    }

    // Set field values from associative array returned by SELECT
    public function setDbValues($values, Logger $logger = null)
    {
        if (isset($values[DbUtils\Category::VIEW_ID]) && filter_var($values[DbUtils\Category::VIEW_ID], FILTER_VALIDATE_INT)) {
            $this->dbId = (int)$values[DbUtils\Category::VIEW_ID];
        }
        if (isset($values[DbUtils\Category::VIEW_CATEGORYID]) && filter_var($values[DbUtils\Category::VIEW_CATEGORYID], FILTER_VALIDATE_INT)) {
            $this->categoryId = (int)$values[DbUtils\Category::VIEW_CATEGORYID];
        }
        if (isset($values[DbUtils\Category::VIEW_SITEID]) && filter_var($values[DbUtils\Category::VIEW_SITEID], FILTER_VALIDATE_INT)) {
            $this->siteId = (int)$values[DbUtils\Category::VIEW_SITEID];
        }        
        if (isset($values[DbUtils\Category::VIEW_NAME])) {
            $this->name = $values[DbUtils\Category::VIEW_NAME];
        }
        if (isset($values[DbUtils\Category::VIEW_IGNORED])) {
            $this->ignored = (bool)$values[DbUtils\Category::VIEW_IGNORED];
        }
    }
}

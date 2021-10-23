<?php

namespace ScpCrawler\Scp;

use ScpCrawler\Logger\Logger;
use ScpCrawler\Scp\DbUtils\KeepAliveMysqli;

// SCP database page class
class Page extends \ScpCrawler\Wikidot\Page
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

    public $test = false;
    
    /*** Private ***/

    // Set field values from associative array returned by SELECT
    private function setDbValues($values, Logger $logger = null)
    {
        if (isset($values[DbUtils\Page::VIEW_ID]) && filter_var($values[DbUtils\Page::VIEW_ID], FILTER_VALIDATE_INT)) {
            $this->dbId = (int)$values[DbUtils\Page::VIEW_ID];
        }
        if (isset($values[DbUtils\Page::VIEW_SITEID]) && filter_var($values[DbUtils\Page::VIEW_SITEID], FILTER_VALIDATE_INT)) {
            $this->setProperty('siteId', (int)$values[DbUtils\Page::VIEW_SITEID]);
        }
        if (isset($values[DbUtils\Page::VIEW_PAGEID]) && filter_var($values[DbUtils\Page::VIEW_PAGEID], FILTER_VALIDATE_INT)) {
            $this->setProperty('pageId', (int)$values[DbUtils\Page::VIEW_PAGEID]);
        }
        if (isset($values[DbUtils\Page::VIEW_CATEGORYID]) && filter_var($values[DbUtils\Page::VIEW_CATEGORYID], FILTER_VALIDATE_INT)) {
            $this->setProperty('categoryId', (int)$values[DbUtils\Page::VIEW_CATEGORYID]);
        }
        if (isset($values[DbUtils\Page::VIEW_SITE_NAME])) {
            $this->setProperty('siteName', $values[DbUtils\Page::VIEW_SITE_NAME]);
        }
        if (isset($values[DbUtils\Page::VIEW_PAGE_NAME])) {
            $this->setProperty('pageName', $values[DbUtils\Page::VIEW_PAGE_NAME]);
        }
        if (isset($values[DbUtils\Page::VIEW_TITLE])) {
            $this->setProperty('title', $values[DbUtils\Page::VIEW_TITLE]);
        }
        if (isset($values[DbUtils\Page::VIEW_ALT_TITLE])) {
            $this->setProperty('altTitle', $values[DbUtils\Page::VIEW_ALT_TITLE]);
        }
        if (isset($values[DbUtils\Page::VIEW_SOURCE])) {
            $this->setProperty('source', $values[DbUtils\Page::VIEW_SOURCE]);
        }
    }

    // Save revisions to DB
    private function saveRevisionsToDb(KeepAliveMysqli $link, Logger $logger = null)
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
    private function saveTagsToDb(KeepAliveMysqli $link, Logger $logger = null)
    {
        $res = true;
        $tags = $this->getTags();
        if (isset($tags)) {
            DbUtils\Tag::delete($link, $this->getId(), $logger);
            foreach ($tags as $tag) {
                $res = $res && DbUtils\Tag::insert($link, $this->getId(), $tag, $logger);
            }
        }
        return $res;
    }

    // Save votes to DB
    private function saveVotesToDb(KeepAliveMysqli $link, Logger $logger = null)
    {
        $res = true;
        $votes = $this->getVotes();
        if (isset($votes)) {
            $oldVotes = array();
            if (DbUtils\Vote::select($link, $this->getId(), $oldVotes, $logger)) {
                foreach($votes as $userId => $vote) {
                    unset($oldVotes[$userId]);
                    // INSERT OR UPDATE
                    $res = $res && DbUtils\Vote::insert($link, $this->getId(), $userId, $vote, $logger);
                }
                foreach ($oldVotes as $userId => $vote) {
                    // INSERT OR UPDATE
                    // Instead of deleting old vote we change it to neutral vote
                    $res = $res && DbUtils\Vote::insert($link, $this->getId(), $userId, 0, $logger);
                }
            }
        }
        return $res;
    }

    // Load revisions from DB
    private function loadRevisionsFromDb(KeepAliveMysqli $link, Logger $logger = null)
    {
        $revisions = array();
        $revQuery = "SELECT * FROM ".DbUtils\Revision::VIEW
            ." WHERE ".DbUtils\Revision::VIEW_PAGEID." = {$this->getId()}"
            ." ORDER BY ".DbUtils\Revision::VIEW_REVISION_INDEX." DESC LIMIT 1";
        if ($dataset = $link->query($revQuery)) {
            while ($row = $dataset->fetch_assoc()) {
                $rev = new Revision($this->getId());
                $rev->setDbValues($row);
                $revisions[$rev->getIndex()] = $rev;
            }
            $this->setProperty('revisions', $revisions);
        } else {
            Logger::logFormat(
                $logger,
                "Failed to load from DB revisions for page %s://%s.wikidot.com/%s id=%d\nError: \"%s\"",
                array(\ScpCrawler\Wikidot\Utils::$protocol, $this->getSiteName(), $this->getPageName(), $this->pageId, $link->error())
            );
        }
    }

    // Load tags from DB
    private function loadTagsFromDb(KeepAliveMysqli $link, Logger $logger = null)
    {
        $tags = array();
        DbUtils\Tag::select($link, $this->getId(), $tags, $logger);
        asort($tags);
        $this->setProperty('tags', $tags);
    }

    // Load votes from DB
    private function loadVotesFromDb(KeepAliveMysqli $link, Logger $logger = null)
    {
        $votes = array();
        DbUtils\Vote::select($link, $this->getId(), $votes, $logger);
        $this->setProperty('votes', $votes);
    }

    /*** Protected ***/

    // Class of revision objects
    protected function getRevisionClass()
    {
        return '\ScpCrawler\Scp\Revision';
    }

    // Class of user objects
    protected function getUserClass()
    {
        return '\ScpCrawler\Scp\User';
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
                    if (is_a($rev, 'Revision')) {
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
            throw new Exception("Wrong number/type of arguments in ".__CLASS__." constructor");
        }
    }
    
    // Save page to DB
    public function saveToDB(KeepAliveMysqli $link, Logger $logger = null)
    {
        $res = false;
        try {
            $link->begin_transaction();
            if (!$this->dbId) {
                $this->dbId = DbUtils\Page::selectId($link, $this, $logger);
            }
            if ($this->dbId) {
                $res = DbUtils\Page::update($link, $this, $logger);
            } else {
                $res = DbUtils\Page::insert($link, $this, $logger);
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
                Logger::logFormat(
                    $logger,
                    "Failed to save to DB page %s://%s.wikidot.com/%s id=%d",
                    array(\ScpCrawler\Wikidot\Utils::$protocol, $this->getSiteName(), $this->getPageName(), $this->getId())
                );
            }
        } catch (Exception $e) {
            $link->rollback();
            Logger::logFormat(
                $logger,
                "Failed to save to DB page %s://%s.wikidot.com/%s id=%d\nError: \"%s\"",
                array(\ScpCrawler\Wikidot\Utils::$protocol, $this->getSiteName(), $this->getPageName(), $this->getId(), $e->getMessage())
            );
        }
        return $res;
    }

    // Load page to DB
    public function loadFromDB(KeepAliveMysqli $link, Logger $logger = null)
    {
        $res = false;
        try {
            if ($data = DbUtils\Page::select($link, $this, $logger)) {
                $this->setDbValues($data);
                $this->loadRevisionsFromDb($link, $logger);
                $this->loadTagsFromDb($link, $logger);
                // We have to conserve memory, so instead of keeping actual votes, just keep a checksum of them
                $votes = array();
                DbUtils\Vote::select($link, $this->getId(), $votes, $logger);
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
            Logger::logFormat(
                $logger,
                "Failed to load from DB page %s://%s.wikidot.com/%s id=%d\nError: \"%s\"",
                array(\ScpCrawler\Wikidot\Utils::$protocol, $this->getSiteName(), $this->getPageName(), $this->getId(), $e->getMessage())
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
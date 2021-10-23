<?php

namespace ScpCrawler\Wikidot;

// Class with properties of a single revision
class Revision
{
    /*** Fields ***/
    // Wikidot revision id
    protected $revisionId;
    // Wikidot page id
    protected $pageId;
    // Zero-based index in the list of revisions of the page
    protected $index;
    // User object - author of the revision
    protected $user;
    // Date and time revision was submitted
    protected $dateTime;
    // Comments
    protected $comments;

    /*** Protected ***/
    // Name of class for user object for overriding in descendants
    protected function getUserClass()
    {
        return '\ScpCrawler\Wikidot\User';
    }

    /*** Public ***/
    public function __construct($pageId)
    {
        $this->pageId = $pageId;
    }

    // Extract information about revision from a html element
    public function extractFrom(\phpQueryObject $rev)
    {
        preg_match('/\d+/', $rev->attr('id'), $ids);
        $this->revisionId = (int)$ids[0];
        $this->index = (int)substr(trim(pq('td:first', $rev)->text()), 0, -1);
        $userClass = $this->getUserClass();
        $this->user = new $userClass();
        $this->user->extractFrom(pq('span.printuser', $rev));
        $this->dateTime = Utils::extractDateTime(pq('span.odate', $rev));
        $this->comments = trim(pq('td:last', $rev)->text());
    }

    /*** Access methods ***/
    // Wikidot revision id
    public function getId()
    {
        return $this->revisionId;
    }

    // Wikidot page id
    public function getPageId()
    {
        return $this->pageId;
    }

    // Zero-based index in the list of revisions of the page
    public function getIndex()
    {
        return $this->index;
    }

    // User object - author of the revision
    public function getUser()
    {
        return $this->user;
    }

    // Id of the author
    public function getUserId()
    {
        if ($this->user) {
            return $this->user->getId();
        }
    }

    // Date and time revision was submitted
    public function getDateTime()
    {
        return $this->dateTime;
    }

    // Comments
    public function getComments()
    {
        return $this->comments;
    }
}
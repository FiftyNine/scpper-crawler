<?php

require_once "ScpUpdater.php";

// Global var for thread workers to read from because I have no idea how
// memory management and passing be reference works in PHP
$lastRevisions = [];

class ScpThreadWorker extends Worker
{

    protected $logger;

    public function __construct(WikidotLogger $logger) {
        $this->logger = $logger;
    }

    public function getLogger()
    {
        return $this->logger;
    }
}

class ScpAbstractWork extends Threaded
{
    protected $complete;
    protected $success;

    public function __construct()
    {
        $this->complete = false;
        $this->success = false;
    }

    public function isComplete()
    {
        return $this->complete;
    }

    public function isSuccess()
    {
        return $this->success;
    }
}

class ScpPageWork extends ScpAbstractWork
{
    protected $page;    

    public function __construct(ScpPage $page)
    {
        parent::__construct();
        $this->page = $page;
    }

    public function run()
    {
        global $lastRevisions;
        $logger = $this->worker->getLogger();
        $page = $this->page;
        if (!$page->retrievePageInfo($logger)) {
            $this->complete = true;
            return;
        }
        $this->success = $page->retrievePageVotes($logger);
        if (!array_key_exists($page->getId(), $lastRevisions) || ($page->getLastRevision() != $lastRevisions[$page->getId()])) {
            $this->success = $this->success
                && $page->retrievePageHistory($logger) 
                && $page->retrievePageSource($logger);
        }
        $this->page = $page;
        $this->complete = true;
    }

    public function getPage()
    {
        return $this->page;
    }
}

class ScpMemberListPageWork extends ScpAbstractWork
{
    protected $siteName;
    protected $pageIndex;
    protected $pageHtml;

    public function __construct($siteName, $pageIndex)
    {
        parent::__construct();
        $this->siteName = $siteName;
        $this->pageIndex = $pageIndex;
    }

    public function run()
    {
        $logger = $this->worker->getLogger();
        $args = ['page' => $this->pageIndex];
        $html = null;
        try {
            $status = WikidotUtils::requestModule($this->siteName, 'membership/MembersListModule', 0, $args, $html, $logger);
            if ($status === WikidotStatus::OK) {
                $this->pageHtml = $html;
                $this->success = true;
            }
        } finally {
            $this->complete = true;
        }
    }

    public function getPageHtml()
    {
        return $this->pageHtml;
    }
}

class ScpMultithreadUsersUpdater extends ScpUsersUpdater
{
    // Retrieve all the users
    protected function retrieveUsers()
    {
        $pool = new Pool(SCP_THREADS, ScpThreadWorker::class, [$this->logger]);
        for ($i = 1; $i <= $this->pageCount; $i++) {
            $pool->submit(new ScpMemberListPageWork($this->siteName, $i));
        }
        $left = $this->pageCount;
        $failed = false;
        while ($left > 0 && !$failed) {
            $pool->collect(
                function(ScpMemberListPageWork $task) use (&$left, &$failed)
                {
                    if ($task->isComplete()) {
                        if ($task->isSuccess()) {
                            $loaded = $this->webList->addMembersFromListPage($task->getPageHtml(), $this->logger);
                            if (intdiv($this->total + $loaded, 1000) > intdiv($this->total, 1000)) {
                                WikidotLogger::logFormat(
                                    $this->logger,
                                    "%d members retrieved [%d kb used]...",
                                    [intdiv($this->total + $loaded, 1000)*1000, round(memory_get_usage()/1024)]
                                );
                            }
                            $this->total += $loaded;
                        } else {
                            $failed = true;
                        }
                        $left--;
                        return true;
                    } else {
                        return false;
                    }
                }
            );
        }
        $this->failed = $this->failed || $failed;
    }
}

class ScpMultithreadPagesUpdater extends ScpPagesUpdater
{
    // Process all the pages
    protected function processPages()
    {
        global $lastRevisions;
        $lastRevisions = [];
        foreach ($this->pages->iteratePages() as $page) {
            $lastRevisions[$page->getId()] = $page->getLastRevision();            
        }               
        $pool = new Pool(SCP_THREADS, ScpThreadWorker::class, [$this->logger]);
        // Iterate through all pages and process them one by one
        for ($i = count($this->sitePages)-1; $i>=0; $i--) {
            $page = $this->sitePages[$i];
            $pool->submit(new ScpPageWork($page));
        }
        $left = count($this->sitePages);
        while ($left > 0) {
            $pool->collect(
                function(ScpPageWork $task) use (&$left)
                {
                    if ($task->isComplete()) {
                        $this->processPage($task->getPage(), $task->isSuccess());
                        $left--;
                        return true;
                    } else {
                        return false;
                    }
                }
            );
        }
    }
}

class ScpMultithreadedSiteUpdater extends ScpSiteUpdater
{
    protected function getPagesUpdaterClass()
    {
        return 'ScpMultithreadPagesUpdater';
    }

    protected function getUsersUpdaterClass()
    {
        return 'ScpMultithreadUsersUpdater';
    }
}
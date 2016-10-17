<?php

require_once "ScpUpdater.php";

class WebWorker extends Worker 
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

class WebWork extends Threaded  
{
    protected $page;    
    protected $complete;    
    protected $success;
    
    public function __construct(ScpPage $page) 
    {
        $this->complete = false;
        $this->success = false;
        $this->page = $page;
    }    
    
    public function run() 
    {
        $logger = $this->worker->getLogger();        
        $page = $this->page;
        if (!$page->retrievePageInfo($logger)) {
            $this->complete = true;
            return;
        }
        $this->success = 
            $page->retrievePageVotes($logger)
            && $page->retrievePageHistory($logger) 
            && $page->retrievePageSource($logger);        
        $this->page = $page;
        $this->complete = true;
    }

    public function isComplete() 
    {
        return $this->complete;
    }

    public function isSuccess() 
    {
        return $this->success;
    }
    
    public function getPage()
    {
        return $this->page;
    }
}

class ScpMultithreadPagesUpdater extends ScpPagesUpdater
{
    // Process all the pages
    protected function processPages()
    {
        $pool = new Pool(16, WebWorker::class, [$this->logger]);
        // Iterate through all pages and process them one by one
        for ($i = count($this->sitePages)-1; $i>=0; $i--) {
            $page = $this->sitePages[$i];     
            $pool->submit(new WebWork($page));
        }
        $left = count($this->sitePages);
        while ($left > 0) {
            $pool->collect(
                function(WebWork $task) use (&$left) 
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
}
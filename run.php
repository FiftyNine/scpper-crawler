<?php

require 'vendor/autoload.php';

ini_set('memory_limit', '2048M');

$logger = new \ScpCrawler\Logger\DebugLogger('dbupdate.log', true);
$logger->setTimeZone('Asia/Nicosia');

$branch = getenv('CRAWLER_BRANCH');

if (!$branch) {
    if ($argc < 2 || !preg_match('/[\w\-]+/', $argv[1])) {
        \ScpCrawler\Logger\Logger::log($logger, "Branch to update must be supplied either in 'CRAWLER_BRANCH' environment variable or as a first command line argument.\n");
        die();
    }
    $branch = argv[1];
}

$concurrentCount = 1;
$concurrency = null;
if ($argc > 2) {
    if (preg_match('/--threads=(\d+$)/', $argv[2], $matches)) {
        $concurrency = "threads";
        $concurrentCount = (int)$matches[0];
        if (!class_exists('Thread', false)) {
            \ScpCrawler\Logger\Logger::log($logger, "To use thread-based implementation php must be compiled with pthreads.");
            die();
        }
    } else if (preg_match('/--processes=(\d+$)/', $argv[2], $matches)) {
        $concurrency = "processes";
        $concurrentCount = (int)$matches[0];        
    } else {
        \ScpCrawler\Logger\Logger::log($logger, "Invalid argument. Use --threads=NUMBER for thread-based implementation of concurrency or --processes=NUMBER for process based. Omit second argument for a non-concurrency implementation.\n");
        die();
    }    
}       

if ($concurrency == "threads") {
    printf("Running update in %d threads...\n", $concurrentCount);
    $updater = new \ScpCrawler\Updater\Threads\SiteUpdater($threads);
} else if ($concurrency == "processes") {    
    printf("Running update with up to %d processes...\n", $concurrentCount);    
    $updater = new \ScpCrawler\Updater\Processes\SiteUpdater(__DIR__."/tmp/", $threads);
} else {
    print("Running update in a single thread\n");
    $updater = new \ScpCrawler\Updater\SiteUpdater();
}

$dbHost = getenv('DB_HOST');
$dbPort = getenv('DB_PORT');
$dbName = getenv('DB_NAME');
$dbUser = getenv('DB_USER');
$dbPassword = getenv('DB_PASSWORD');

$link = new \ScpCrawler\Scp\DbUtils\KeepAliveMysqli($dbHost, $dbUser, $dbPassword, $dbName, $dbPort, $logger);

$updater->updateSiteData($branch, $link, $logger);

?>

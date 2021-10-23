<?php

namespace ScpCrawler\Logger;

// Logger class that writes messages both to a log file and to the standard output
class CronLogger extends FileLogger
{
    /*** Protected ***/
    // Write message to log file
    protected function logInternal($message)
    {
        parent::logInternal($message);
        echo $message;
    }
}
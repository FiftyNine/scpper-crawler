<?php

namespace ScpCrawler\Logger;

// Logger class that prints messages to screen
class DebugLogger extends Logger
{
    /*** Protected ***/
    // Write message to standard output
    protected function logInternal($message)
    {
        print($message);
    }
}
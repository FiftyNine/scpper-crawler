<?php

namespace ScpCrawler\Logger;

class ExceptionLogger extends Logger
{
    /*** Protected ***/
    // Write message to log file
    protected function logInternal($message)
    {
        throw new \Exception($message);
    }    
}
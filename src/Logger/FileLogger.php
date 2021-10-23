<?php

namespace ScpCrawler\Logger;

// Logger class that writes messages to a log file
class FileLogger extends Logger
{
    /*** Fields ***/
    // Path to the log file
    protected $fileName;

    /*** Protected ***/
    // Write message to log file
    protected function logInternal($message)
    {
        file_put_contents($this->fileName, $message, FILE_APPEND);
    }

    /*** Public ***/
    public function __construct ($fileName, $append)
    {
        $this->fileName = $fileName;
        file_put_contents($this->fileName, "\n", $append?FILE_APPEND:0);
    }
}
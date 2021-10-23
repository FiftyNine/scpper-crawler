<?php

/******************************************************/
/************ Classes for logging *********************/
/******************************************************/

namespace ScpCrawler\Logger;

if (defined('SCP_THREADS')) {
    abstract class AbstractLogger extends \Threaded
    {

    }
} else {
    abstract class AbstractLogger
    {

    }
}

// Base class for loggers
abstract class Logger extends AbstractLogger
{
    /*** Fields ***/
    protected $timeZone = null;

    /*** Private ***/
    // Adds timestamp and linebreak to a message
    private function timestampMessage($message)
    {
        $now = new \DateTime(null, $this->timeZone);
        return sprintf(
            "[%s] %s\n",
            $now->format('d/m/Y H:i:s'),
            $message
        );
    }

    // Adds timestamp and outputs message
    private function logSimpleInternal($message)
    {
        $timeMessage = $this->timestampMessage($message);
        $this->logInternal($timeMessage);
    }

    // Formats message with args using vsprintf and outputs it
    private function logFormatInternal($format, $args)
    {
        $message = vsprintf($format, $args);
        $this->logSimpleInternal($message);
    }

    /*** Protected ***/
    // Actual method for outputting messages. Must be overridden in descendants
    abstract protected function logInternal($message);

    /*** Public ***/
    // Set timezone
    public function setTimeZone($name)
    {
        if (!$this->timeZone = timezone_open($name)) {
            $this->timeZone = null;
        }
    }

    // Static method that checks if logger is null and calls logSimpleInternal
    public static function log($logger, $message)
    {
        if ($logger) {
            $logger->logSimpleInternal($message);
        }
    }

    // Static method that checks if logger is null and calls logFormatInternal
    public static function logFormat($logger, $format, $args)
    {
        if ($logger) {
            $logger->logFormatInternal($format, $args);
        }
    }
}

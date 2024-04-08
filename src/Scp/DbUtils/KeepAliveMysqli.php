<?php

namespace ScpCrawler\Scp\DbUtils;

use ScpCrawler\Logger\Logger;

class KeepAliveMysqli
{
    const MAX_TIMEOUT = 59; // 180000000;// 3*60*1000*1000;
    /*** Fields ***/
    private $link = null;
    private $logger = null;
    private $lastAccess = 0;
    private $host;
    private $login;
    private $password;
    private $database;
    private $port;    
    private $retries = 5;
    private $retry_timeout = 10000; // ms

    public function __construct($host, $login, $password, $database, $port, $logger = null)
    {
        $this->host = $host;
        $this->login = $login;
        $this->password = $password;
        $this->database = $database;
        $this->port = $port;
        $this->logger = $logger;
    }

    public function query($text)
    {
        return $this->getLink()->query($text);
    }

    public function prepare($text)
    {
        return $this->getLink()->prepare($text);
    }

    public function begin_transaction()
    {
        return $this->getLink()->begin_transaction();
    }

    public function commit()
    {
        return $this->getLink()->commit();
    }

    public function rollback()
    {
        return $this->getLink()->rollback();
    }    

    public function insert_id()
    {
        return $this->getLink()->insert_id;
    }

    public function error()
    {
        return $this->getLink()->error;
    }    

    public function getLink()
    {
        if (!$this->link || ((microtime(true) - $this->lastAccess) >= self::MAX_TIMEOUT)) {
            if ($this->link) {
                $this->link->close();
            }            
            $tries = 0;
            while ($tries < $this->retries)
            {
                $this->link = new \mysqli($this->host, $this->login, $this->password, $this->database, $this->port);
                if ($this->link->connect_error) {                
                    Logger::log($this->logger, 'Connect Error (' . $this->link->connect_errno . ') '. $this->link->connect_error);                    
                    $tries++;
                    if ($tries < $this->retries)
                    {
                        Logger::log($this->logger, "Waiting for $this->retry_timeout ms before retrying...");
                        usleep($this->retry_timeout*1000);
                    }
                } else {
                    break;
                }
            }
            $this->link->set_charset("utf8mb4");
            $this->link->query("SET collation_connection = utf8mb4_unicode_ci");
        }
        $this->lastAccess = microtime(true);
        return $this->link;
    }

    public function set_connect_retries($count, $timeout)
    {
        $this->retries = $count;
        $this->retry_timeout = $timeout;
    }
}

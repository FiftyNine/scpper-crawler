
<?php

require_once "auth.inc";
require_once "WikidotCrawler.php";

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

    public function __construct($host, $login, $password, $database, $port, $logger = nil)
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
            $this->link = new mysqli($this->host, $this->login, $this->password, $this->database, $this->port);
            if ($this->link->connect_error) {                
                WikidotLogger::log($this->logger, 'Connect Error (' . $this->link->connect_errno . ') '. $this->link->connect_error);
                die('');
            }
            $this->link->set_charset("utf8mb4");
        }
        $this->lastAccess = microtime(true);
        return $this->link;
    }
}

class iimysqli_result
{
    public $stmt, $cols;
}    

function iimysqli_stmt_get_result($stmt)
{
    /**    EXPLANATION:
     * We are creating a fake "result" structure to enable us to have
     * source-level equivalent syntax to a query executed via
     * mysqli_query().
     *
     *    $stmt = mysqli_prepare($conn, "");
     *    mysqli_bind_param($stmt, "types", ...);
     *
     *    $param1 = 0;
     *    $param2 = 'foo';
     *    $param3 = 'bar';
     *    mysqli_execute($stmt);
     *    $result _mysqli_stmt_get_result($stmt);
     *        [ $arr = _mysqli_result_fetch_array($result);
     *            || $assoc = _mysqli_result_fetch_assoc($result); ]
     *    mysqli_stmt_close($stmt);
     *    mysqli_close($conn);
     *
     * At the source level, there is no difference between this and mysqlnd.
     **/
    $metadata = mysqli_stmt_result_metadata($stmt);
    $ret = new iimysqli_result;
    if (!$ret) return NULL;
    $ret->cols = array();//mysqli_num_fields($metadata);
    while ($field = $metadata->fetch_field()) {
        $ret->cols[] = $field->name;
    }    
    $ret->stmt = $stmt;
    mysqli_free_result($metadata);
    return $ret;
}

function iimysqli_result_fetch_array(&$result)
{
    $ret = array();
    $code = "return mysqli_stmt_bind_result(\$result->stmt ";

    for ($i=0; $i<count($result->cols); $i++)
    {
        $ret[$i] = NULL;
        $code .= ", \$ret['" .$result->cols[$i] ."']";
    };

    $code .= ");";
    if (!eval($code)) { return NULL; };

    // This should advance the "$stmt" cursor.
    if (!mysqli_stmt_fetch($result->stmt)) { return NULL; };

    // Return the array we built.
    return $ret;
}

function createElement($doc, $elemType, $text = '', $attrs = array())
{
    $elem = $doc->createElement($elemType);
    if (is_array($attrs)) {
        foreach ($attrs as $attr => $value) {
            $elem->setAttribute($attr, $value);
        }
    }
    if ($text) {
        $elem->appendChild($doc->createTextNode($text));
    }
    return $elem;
}

function createPage()
{
    $x = new DOMImplementation();
    $doctype = $x->createDocumentType('html', '', '');
    $document = $x->createDocument('', 'html', $doctype);
    $head = $document->createElement('head');
    $metahttp = $document->createElement('meta');
    $metahttp->setAttribute('http-equiv', 'Content-Type');
    $metahttp->setAttribute('content', 'text/html; charset=utf-8');
    $head->appendChild($metahttp);
     
    $title = $document->createElement('title', 'SCPper');
    $head->appendChild($title);
    $body = $document->createElement('body');
    $html = $document->getElementsByTagName('html')->item(0);
    $html->appendChild($head);
    $html->appendChild($body);
    return $document;
}

function getDbLink($mode = 0)
{
    global $SCPPER_READER;
    global $SCPPER_WRITER;
    if ($mode == 1) {
        $auth = $SCPPER_WRITER;
    } else {
        $auth = $SCPPER_READER;
    }
    $link = new mysqli($auth['HOST'], $auth['LOGIN'], $auth['PASSWORD'], $auth['DATABASE'], 3306);
    if ($link->connect_error) {
        return null;
    }
    $link->set_charset("utf8mb4");
    return $link;
}

function generateCallTrace()
{
    $e = new Exception();
    $trace = explode("\n", $e->getTraceAsString());
    // reverse array to make steps line up chronologically
    $trace = array_reverse($trace);
    array_shift($trace); // remove {main}
    array_pop($trace); // remove call to this method
    $length = count($trace);
    $result = array();
    
    for ($i = 0; $i < $length; $i++)
    {
        $result[] = ($i + 1)  . ')' . substr($trace[$i], strpos($trace[$i], ' ')); // replace '#someNum' with '$i)', set the right ordering
    }
    
    return "\t" . implode("\n\t", $result);
}

function showConnectionStatus(mysqli $link) 
{
    $result = $link->query('SHOW SESSION STATUS;', MYSQLI_USE_RESULT); 
    while ($row = $result->fetch_assoc()) { 
        $array[$row['Variable_name']] = $row['Value']; 
    } 
    $result->close(); 
    print_r($array);     
}



?>

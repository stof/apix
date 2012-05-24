<?php

/** @see Zenya_Api_Response_Interface */
#require_once 'Zenya/Api/Response/Interface.php';

namespace Zenya\Api;

class Response
{
    /**
     * Format to use by default when not provided.
     */
    const DEFAULT_FORMAT = 'html';

    /**
     * Constante used for status report.
     */
    const SUCCESS = 'successful';
    const FAILURE = 'failed';

    /**
     * Associative array of HTTP status code / reason phrase.
     *
     * @var  array
     * @link http://tools.ietf.org/html/rfc2616#section-10
     */
    protected static $defs = array(

        // 1xx: Informational - Request received, continuing process
        100 => 'Continue',
        101 => 'Switching Protocols',

        // 2xx: Success - The action was successfully received, understood and
        // accepted
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',

        // 3xx: Redirection - Further action must be taken in order to complete
        // the request
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',  // 1.1
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        307 => 'Temporary Redirect',

        // 4xx: Client Error - The request contains bad syntax or cannot be
        // fulfilled
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Request Entity Too Large',
        414 => 'Request-URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Requested Range Not Satisfiable',
        417 => 'Expectation Failed',

        // 5xx: Server Error - The server failed to fulfill an apparently
        // valid request
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported',
        509 => 'Bandwidth Limit Exceeded',

    );

    /**
     * Hold the output format.
     * @var string
     */
    public $format = null;

    /**
     * Hold the encoding information.
     * @var string
     */
    public $encoding = 'UTF-8';

    /**
     * List of supported formats.
     * @var array
     */
    public static $formats = array('json', 'xml', 'html', 'php');

    /**
     * @var Zenya_Api_Server
     */
    protected $server;

    public function __construct(Server $server, $format)
    {
        $this->server = $server;
        $this->format = $format;
    }

    public function toArray()
    {
        #$data = array();
        #foreach ($this->server->results as $k => $v) {
        #	$data[$k] = $v;
        #}

        $req = $this->server->request;
        $route = $this->server->route;

        $array = array(
            $this->server->route->name => $this->server->results,
            'signature'	=> array(
                'status'	=> sprintf('%d %s - %s',
                                        $this->server->httpCode,
                                        self::$defs[$this->server->httpCode],
                                        self::getStatusString($this->server->httpCode)
                                ),
                'request'	=> sprintf('%s %s', $req->getMethod(), $req->getUri()),
                'timestamp'	=> $this->getDateTime()
            )
        );

        if ($this->server->debug == true) {
            $array['debug'] = array(
                //	'request'	=> $req->getPathInfo(),		// Request URI
                    'params'	=> $route->params,	// Params
                    'format'	=> $this->format,
                    'ip'		=> $req->getIp(true),
                    'headers'	=> $this->headers
            );
        }

        return $array;
    }

    protected function getDateTime($time=null)
    {
        return gmdate('Y-m-d H:i:s') . ' UTC';
        #$dt = new \DateTime($time);
        #$dt->setTimezone(new \DateTimeZone('UTC'));
        #return $dt->format(\DateTime::ISO8601);
    }

    public static function getStatusString($int)
    {
        static $status = null;
        if (!is_null($status)) {
            // Response status already set, something must be wrong.
            throw new \Exception('Internal Error', 500);
        }

        return floor($int/100)<=3 ? self::SUCCESS : self::FAILURE;
    }

    /**
     * Send the response
     *
     * @return string
     */
    public function send()
    {
        $this->setHeaders();

        $format = isset($this->format) ? $this->format : self::DEFAULT_FORMAT;

        $classname = '\Zenya\Api\Response' . '\\' . ucfirst($this->format);

        $formatter = new $classname($this->encoding);
        $body = $formatter->encode($this->toArray(), $this->server->rootNode);

        $this->setHeader('Content-type', $classname::$contentType);

        header('X-Powered-By: ' . $this->server->version, true, $this->server->httpCode);

        // iterate and send all the headers
        foreach ($this->headers as $key => $value) {
            header($key . ': ' . $value);
        }

        if ($this->server->route->method == 'HEAD') {
            #$body = null;
            $body = 'null';
        }

        return $body;
    }

    public static function throwExceptionIfUnsupportedFormat($format)
    {
        if (!in_array(strtolower($format), self::$formats)) {
            throw new Exception("Format ({$format}) not supported.", 404); // TODO: maybe 406?
        }
    }

    /**
     * Get all the response formats available.
     *
     * @return array
     */
    public static function getFormats()
    {
        return self::$formats;
    }

    private $headers = array();

    public function setHeader($key, $value)
    {
        $this->headers[$key] = $value;
    }

    /**
     *	#header('Cache-Control: no-cache, must-revalidate');	// HTTP/1.1
     *	#header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');	// Date in the past
     *		// upload example
     *	#header('Content-Disposition: attachment; filename="downloaded.pdf"');
     *	#readfile('original.pdf');
     */
    public function setHeaders()
    {
        $this->setHeader('Vary', 'Accept');

        // check $this->httpCode
        switch ($this->server->httpCode) {
            case 405:
                #Server::d($this->resource);
                $this->setHeader('Allow', 'TODO: add HTTP methods here.');
                #$this->setHeader('Allow', implode(', ', $this->server->res->getMethods()));
        }

    }

    public function _addHeaderFromException()
    {
        $r = $this->getResponse();
        if ($r->isException()) {
            $stack = $r->getException();
            $e = is_array($stack) ? $stack[0] : $stack;
            if (is_a($e, 'Zenya_Api_Exception')) {
                //Zend_debug::dump($e);
                $r->setHttpResponseCode($e->getCode());
                #$resp->setHeader('X-Error', $e->getCode());
            }
        }
    }

    /**
     * Hold alist of Http status used by Zenya_Api
     * as per http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html
     *
     * k=Code, Desc, Msg
     */
    protected $_httpStatuses = array(
        200 => array('OK', 'The request has succeeded.'),
        // POST
        '201' => array('Created', 'The request has been fulfilled and resulted in a new resource being created.'),
        // Note: require to use ->setHeader("Location", "http://url/action/id")

        '202' => array('Accepted', 'The request has been accepted for processing, but the processing has not been completed.'),
        // DELETE
        '204' => array('No Content', 'Request fulfilled successfully.'),
        // Error
        '400' => array('Bad Request', 'Request is malformed.'),
        '401' => array('Unauthorized', 'Not Authenticated.'),
        '403' => array('Forbidden', 'Access to this ressource has been denied.'),
        '404' => array('Not Found', 'No ressource found at the Request-URI.'),
        '503' => array('Service Unavailable', 'The service is currently unable to handle the request due to a temporary overloading or maintenance of the server. Try again later.'),
    );

    /* depreciated */
    public function getHttp()
    {
        $status = $this->httpCode . ' ' . Zend_Http_Response::responseCodeAsText($this->httpCode);
        $description = isset($this->_httpStatuses[$this->httpCode][1]) ? $this->_httpStatuses[$this->httpCode][1] : $status . ' (not implemented)';

        // Zend_Http_Response::fromString
        return array(
            'status' => $status,
            'description' => $description,
        );
    }

}

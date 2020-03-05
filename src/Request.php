<?php

namespace Yab\Core;

class Request {

    static $verbs = array(
        'GET',
        'POST',
        'PUT',
        'DELETE',
        'PATCH',
        'OPTIONS',
        'HEAD',
    );

    const DEFAULT_PROTOCOL = "HTTP/1.0";

    protected $protocol = '';
    protected $verb = '';
    protected $uri = '';
    protected $headers = [];
    protected $body = '';
    protected $files = [];

    static public function __callStatic($verb, $arguments) {

        $request = new self(strtoupper($verb), ...$arguments);

        return $request;

    }

    static public function getSession($key = null, $default = null) {

        if(!isset($_SESSION))
            session_start();

        if($key === null)
            return $_SESSION;
 
        if(isset($_SESSION[$key]))
            return $_SESSION[$key];
         
        if($default === null)
            throw new \Exception('invalid session var "'.$key.'"');

        return $default;

    }

    static public function clearSession() {

        if(!isset($_SESSION))
            session_start();

        $_SESSION = [];

        return true;

    }

    static public function unsetSession($key) {

        if(!isset($_SESSION))
            session_start();

        unset($_SESSION[$key]);

        return true;

    }

    static public function setSession($key, $value) {

        if(!isset($_SESSION))
            session_start();

        $_SESSION[$key] = $value;

        return true;

    }

    static public function fromGlobals() {

        $request = new self($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);

        $request->headers = apache_request_headers();
        $request->body = file_get_contents('php://input');
        $request->files = $_FILES;
        
        return $request;

    }

    static public function unparseUrl(array $parsedUrl) {

        $scheme   = isset($parsedUrl['scheme']) ? $parsedUrl['scheme'] . '://' : '';
        $host     = isset($parsedUrl['host']) ? $parsedUrl['host'] : '';
        $port     = isset($parsedUrl['port']) ? ':' . $parsedUrl['port'] : '';
        $user     = isset($parsedUrl['user']) ? $parsedUrl['user'] : '';
        $pass     = isset($parsedUrl['pass']) ? ':' . $parsedUrl['pass']  : '';

        $pass     = ($user || $pass) ? "$pass@" : '';

        $path     = isset($parsedUrl['path']) ? $parsedUrl['path'] : '';
        $query    = isset($parsedUrl['query']) ? '?' . $parsedUrl['query'] : '';
        $fragment = isset($parsedUrl['fragment']) ? '#' . $parsedUrl['fragment'] : '';

        return "$scheme$user$pass$host$port$path$query$fragment";

    }

    public function __construct($verb, $uri, array $headers = [], $body = '', $protocol = self::DEFAULT_PROTOCOL) {

        $this
            ->setProtocol($protocol)
            ->setVerb($verb)
            ->setUri($uri)
            ->setHeaders($headers)
            ->setBody($body);

    }

    public function getVerb() {

        return $this->verb;

    }

    public function getUri() {

        return $this->uri;

    }

    public function getPath() {

        $parsedUrl = parse_url($this->uri);

        return $parsedUrl['path'];

    }

    public function setVerb($verb) {

        $verb = strtoupper((string) $verb);

        if(!in_array($verb, self::$verbs))
            throw new \Exception('invalid HTTP verb "'.$verb.'"');

        $this->verb = $verb;

        return $this;

    }

    public function getFileParams() {
        
        return $this->files;

    }

    public function getFileParam($name, $defaultValue = null) {

        $files = $this->getFileParams();

        if(isset($files[$name]))
            return $files[$name];

        if($defaultValue !== null)
            return $defaultValue;

        throw new \Exception('invalid file param "'.$name.'"');

    }

    public function setUri($uri) {

        $this->uri = (string) $uri;

        return $this;

    }

    public function setProtocol($protocol) {

        $this->protocol = (string) $protocol;

        return $this;

    }

    public function setHeaders(array $headers) {

        $this->headers = $headers;

        return $this;

    }

    public function addHeader($header) {

        $this->headers[] = $header;

        return $this;

    }

    public function removeQueryParam($param) {

        $params = $this->getQueryParams();

        if(!isset($params[$param]))
            throw new \Exception('can not remove query param "'.$param.'"');

        unset($params[$param]);

        return $this->setQueryParams($params);

    }

    public function removeQueryParams() {

        return $this->setQueryParams(array());

    }

    public function addQueryParams(array $queryParams) {

        $params = $this->getQueryParams();

        foreach($queryParams as $param => $value)
            $params[$param] = $value;

        return $this->setQueryParams($params);

    }

    public function addQueryParam($param, $value) {

        $params = $this->getQueryParams();

        $params[$param] = $value;

        return $this->setQueryParams($params);

    }

    public function setQueryString($queryString) {

        $parsedUrl = parse_url($this->uri);

        $parsedUrl['query'] = $queryString;

        $this->uri = self::unparseUrl($parsedUrl);

        return $this;

    }

    public function setQueryParams(array $params = []) {

        return $this->setQueryString(http_build_query($params));

    }

    public function getQueryString() {

        $parse = parse_url($this->uri);

        return isset($parse['query']) ? $parse['query'] : '';

    }

    public function getQueryParams() {

        $params = [];

        parse_str($this->getQueryString(), $params);

        return $params;

    }

    public function getQueryParam($name, $defaultValue = null) {

        $params = $this->getQueryParams();

        if(isset($params[$name]))
            return $params[$name];

        if($defaultValue !== null)
            return $defaultValue;

        throw new \Exception('invalid query param "'.$name.'"');

    }

    public function getBodyParam($name, $defaultValue = null) {

        $params = $this->getBodyParams();

        if(isset($params[$name]))
            return $params[$name];

        if($defaultValue !== null)
            return $defaultValue;

        throw new \Exception('invalid body param "'.$name.'"');

    }

    public function getBodyParams() {

        $params = [];

        parse_str($this->body, $params);

        return $params;

    }

    public function getBody() {

        return $this->body;

    }

    public function addBodyParam($param, $value) {

        $params = $this->getBodyParams();

        $params[$param] = $value;

        return $this->setBodyParams($params);

    }

    public function setBodyParams($params) {

        return $this->setBody(http_build_query($params));

    }

    public function setBody($body) {

        $this->body = (string) $body;

        return $this;

    }

    public function isAjax() {

        return strtolower($this->getHeader('X-Requested-With', '')) == 'xmlhttprequest';

    }

    public function getHeader($header, $default = null) {

        return isset($this->headers[$header]) ? $this->headers[$header] : $default;
        
    }

    public function getHeaders() {

        return $this->headers;
        
    }

    public function getCookies() {
        
        $cookies = [];

        $header = $this->getHeader('Cookie', '');

        if(!$header)
            return $cookies;
            
        $header = explode(';', $header);

        foreach($header as $cookie) {

            $cookie = explode('=', $cookie);

            $cookieName = array_shift($cookie);
            $cookieValue = array_shift($cookie);
            
            $cookies[trim($cookieName)] = urldecode($cookieValue);

        }

        return $cookies;

    }

    public function getCookie($cookieName, $defaultValue = null) {

        $cookies = $this->getCookies();

        return isset($cookies[$cookieName]) ? $cookies[$cookieName] : $defaultValue;

    }

    public function send($userOptions = []) {

        // Overwritable options by userOptions
        $defaultOptions = array(
            CURLOPT_TIMEOUT_MS => Config::getConfig()->getParam('curl.default_timeout_ms', 3000),
            CURLOPT_CONNECTTIMEOUT_MS => Config::getConfig()->getParam('curl.default_connecttimeout_ms', 1000),
            CURLOPT_FRESH_CONNECT => 1,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_FORBID_REUSE => 1,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_SSL_VERIFYPEER => false,
        );

        // Forced options
        $requestOptions = array(
            CURLOPT_URL => $this->uri,
            CURLOPT_HEADER => 1,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTP_VERSION => $this->protocol,
            CURLOPT_CUSTOMREQUEST => $this->verb,
            CURLOPT_HTTPHEADER => $this->headers,
            CURLOPT_POSTFIELDS => $this->body,
        );

        $curlOptions = ($defaultOptions + $userOptions + $requestOptions);

        $timeout = isset($curlOptions[CURLOPT_TIMEOUT_MS]) ? (int) $curlOptions[CURLOPT_TIMEOUT_MS] : null;
        $connectTimeout = isset($curlOptions[CURLOPT_CONNECTTIMEOUT_MS]) ? (int) $curlOptions[CURLOPT_CONNECTTIMEOUT_MS] : null;

        $ch = curl_init();
        
        curl_setopt_array($ch, $curlOptions);

        $result = curl_exec($ch);

        $infos = curl_getinfo($ch);

        if($result === false) {
         /*   
            if($timeout && $connectTimeout && $connectTimeout <= $timeout) {
                
                $length = $infos['total_time'];
                
                $newTimeout = $timeout - ($length * 1000);
             
                $userOptions[CURLOPT_TIMEOUT_MS] = $newTimeout;

                if($connectTimeout < $newTimeout) {
                    
                    $this->retries++;
                    
                    return $this->send($userOptions);
                    
                }
                
            }
            */
            throw new \Exception('request curl error : '.curl_error($ch).', url: '.$this->url);
            
        }

        curl_close($ch);

        return Response::fromString($result);

    }

}

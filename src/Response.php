<?php

namespace Yab\Core;

class Response {

    static  $codes = array(
        100 => "Continue", 101 => "Switching Protocols", 
        102 => "Processing", 
        200 => "OK", 
        201 => "Created", 
        202 => "Accepted", 
        203 => "Non-Authoritative Information", 
        204 => "No Content", 
        205 => "Reset Content", 
        206 => "Partial Content", 
        207 => "Multi-Status", 
        300 => "Multiple Choices", 
        301 => "Moved Permanently", 
        302 => "Found", 
        303 => "See Other", 
        304 => "Not Modified", 
        305 => "Use Proxy", 
        306 => "(Unused)", 
        307 => "Temporary Redirect", 
        308 => "Permanent Redirect", 
        400 => "Bad Request", 
        401 => "Unauthorized", 
        402 => "Payment Required", 
        403 => "Forbidden", 
        404 => "Not Found", 
        405 => "Method Not Allowed", 
        406 => "Not Acceptable", 
        407 => "Proxy Authentication Required", 
        408 => "Request Timeout", 
        409 => "Conflict", 
        410 => "Gone", 
        411 => "Length Required", 
        412 => "Precondition Failed", 
        413 => "Request Entity Too Large", 
        414 => "Request-URI Too Long", 
        415 => "Unsupported Media Type", 
        416 => "Requested Range Not Satisfiable", 
        417 => "Expectation Failed", 
        418 => "I'm a teapot", 
        419 => "Authentication Timeout", 
        420 => "Enhance Your Calm", 
        422 => "Unprocessable Entity", 
        423 => "Locked", 
        424 => "Failed Dependency", 
        424 => "Method Failure", 
        425 => "Unordered Collection", 
        426 => "Upgrade Required", 
        428 => "Precondition Required", 
        429 => "Too Many Requests", 
        431 => "Request Header Fields Too Large", 
        444 => "No Response", 
        449 => "Retry With", 
        450 => "Blocked by Windows Parental Controls", 
        451 => "Unavailable For Legal Reasons", 
        494 => "Request Header Too Large", 
        495 => "Cert Error", 
        496 => "No Cert", 
        497 => "HTTP to HTTPS", 
        499 => "Client Closed Request", 
        500 => "Internal Server Error", 
        501 => "Not Implemented", 
        502 => "Bad Gateway", 
        503 => "Service Unavailable", 
        504 => "Gateway Timeout", 
        505 => "HTTP Version Not Supported", 
        506 => "Variant Also Negotiates", 
        507 => "Insufficient Storage", 
        508 => "Loop Detected", 
        509 => "Bandwidth Limit Exceeded", 
        510 => "Not Extended", 
        511 => "Network Authentication Required", 
        598 => "Network read timeout error", 
        599 => "Network connect timeout error",
    );

    protected $protocol = "HTTP/1.0";
    protected $code = null;
    protected $headers = [];
    protected $cookies = [];
    protected $body = '';

    protected $headersSent = false;

    static public function flush(Response $response) {

        if(!$response->headersSent) {

            header($response->protocol." ".$response->code." ".self::$codes[$response->code]);

            foreach($response->headers as $header) 
                header($header);

            foreach($response->cookies as $name => $value) 
                setcookie($name, $value, 0, '/');

            $response->headersSent = true;

        }
      
        echo $response->body;

        $response->body = '';

    }

    static public function codeByMessage($camelCaseMessage) {

        foreach(self::$codes as $code => $message) 
            if(Tool::camelCase($message) == $camelCaseMessage)
                return new self($code);

        throw new \Exception('invalid HTTP camelCase message "'.$camelCaseMessage.'"');

    }

    static public function messageByCode($code) {

        if(!isset(self::$codes[$code]))
            throw new \Exception('invalid HTTP code "'.$code.'"');

        return self::$codes[$code];

    }

    static public function fromString($string) {

        $parts = explode("\r\n\r\n", $string);

        $headers = array_shift($parts);
        $body = implode("\r\n\r\n", $parts);

        $headers = explode("\r\n", $headers);

        $firstHeader = array_shift($headers);

		$response = self::fromHttpHeader($firstHeader);

        foreach($headers as $header) 
            $response->setHeader($header);

		$response->body = (string) $body;
		
		return $response;
		
    }

    static public function fromHttpHeader($header) {

        if(!preg_match('#(http/[0-9\.]+)\s+([0-9]+)\s+.+$#i', $header, $match))
            throw new \Exception('unable to generate response from header "'.$header.'"');

        $response = new self($match[2]);
        $response->setProtocol($match[1]);

        return $response;

    }

    static public function __callStatic($method, array $args) {

        return new self(self::codeByMessage($method));

    }

    public function __construct($code) {

        $this->setCode($code);

    }

    public function setProtocol($protocol) {

        $this->protocol = (string) $protocol;

        return $this;
    }

    public function setCode($code) {

        if(!isset(self::$codes[$code]))
            throw new \Exception('invalid HTTP code "'.$code.'"');

        $this->code = (int) $code;

        return $this;
    }

    public function getCode() {

        return $this->code;
        
    }

    public function getHeaders($name = null) : array {

        if($name === null)
            return $this->headers;
        
        return array_filter($this->headers, function($header) use($name) {
            return preg_match('#^'.preg_quote($name, '#').'#i', $header);
        });

    }

    public function appendBody($body) {

        $this->body .= (string) $body;

        if($this->headersSent)
            Response::flush($this);
        
        return $this;

    }

    public function getBody() {

        return $this->body;

    }

    public function setHeader($header) {

        $this->headers[] = $header;

        return $this;
    }

    public function redirect($url, $code = 303) {

        $this->code = $code;

        $this->setHeader('Location: ' . $url);

    }

    public function getCookiesHeaders() {
        
        $headers = [];

        foreach($this->cookies as $path => $datas) {

            foreach($datas as $expires => $cookies) {

                $header = 'Set-Cookie:';

                foreach($cookies as $name => $value) 
                    $header .= ' '.$name.'='.urlencode($value).';';
                
                $header .= ' Expires: '.$expires.';';

                $header .= ' Path: '.$path.';';

                $headers[] = $header;
            }

        }

        return $headers;

    }

    public function setCookie($name, $value, $expires = 0, $path = '') {

        $this->cookies[$name] = $value;

        return $this;

    }

    public function getCookie($name, $default = null) {

        return $this->cookies[$name] ?? $default;

    }

}

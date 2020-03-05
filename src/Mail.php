<?php
 
namespace Yab\Core;

use Yab\Core\Tool;

class Mail {

    const EOL = "\r\n";
    
    private $bodySplit = 76;
    private $headersEncoding = 'quoted-printable';
    private $headersCharset = 'UTF-8';
    
    private $boundary = '';

    private $parts = [];
    
    public function __construct() {
        
		$this->boundary = '-----='.md5(uniqid(mt_rand()));
        
    }
    
    public function setReturnPath($returnPath) {
        
        return $this->setHeader('Return-Path', $returnPath, 0);
        
    }
    
    public function setFrom($from) {
        
        return $this->setHeader('From', $from, 0);
        
    }
    
    public function setTo($to) {
        
        return $this->setHeader('To', $to, 0);
        
    }
    
    public function setCc($cc) {
        
        return $this->setHeader('Cc', $cc, 0);
        
    }
    
    public function setBcc($bcc) {
        
        return $this->setHeader('Bcc', $bcc, 0);
        
    }
    
    public function setSubject($subject) {
        
        return $this->setHeader('Subject', $subject, 0);
        
    }
    
    public function setText($text) {

        return $this
            ->setHeader('Content-Disposition', 'inline', 1)
            ->setHeader('Content-Type', 'text/plain; charset="utf-8"', 1)
            ->setHeader('Content-Transfer-Encoding', '8bit', 1)
            ->setBody($text, 1);
 
    }
    
    public function setHtml($html) {

        return $this
            ->setHeader('Content-Disposition', 'inline', 2)
            ->setHeader('Content-Type', 'text/html; charset="utf-8"', 2)
            ->setHeader('Content-Transfer-Encoding', '8bit', 2)
            ->setBody($html, 2);
 
    }

    public function addAttachment($file) {

        $key = $this->getNbAttachments() + 3;
    
        $this->parts[$key]['headers'] = array(
            'Content-Disposition' => 'attachment; filename="'.basename($file).'"',
            'Content-Type' => $this->mimeType($file).'; charset="utf-8"',
            'Content-Transfer-Encoding' => 'base64',
            'Content-ID' => '<'.basename($file).'>',
        );
    
        $this->parts[$key]['body'] = file_get_contents($file);
    
        return $this;
         
    }
    
    public function getNbAttachments() {

        $attachments = 0;
    
        foreach($this->parts as $key => $part)
            if(2 < $key) $attachments++;
    
        return $attachments;
        
    }
    
    public function getText() {

        return $this->getBody(1);
        
    }  
    
    public function getHtml() {

        return $this->getBody(2);
        
    }  

    public function setHeader($headerName, $headerValue, $part = 0) {
        
        $this->parts[$part]['headers'][$headerName] = $headerValue;
        
        return $this;
                        
    }

    public function getHeader($header, $part = 0, $specificParameter = null) {

        if(!array_key_exists($part, $this->parts))
            throw new \Exception('part "'.$part.'" is not available');

        if(!array_key_exists('headers', $this->parts[$part]))
            throw new \Exception('headers of part "'.$part.'" are not set');

        foreach($this->parts[$part]['headers'] as $headerName => $headerValue) {
  
            if(preg_match('#^'.preg_quote($header, '#').'$#i', $headerName)) {
                
                if($specificParameter === null) 
                    return trim($headerValue);
    
                if(preg_match('#'.preg_quote($specificParameter, '#').'\s*=\s*["\s]?([^"\s]+)["\s]?#s', $headerValue, $match)) 
                    return trim($match[1]);
                
                throw new \Exception('unable to get header "'.$header.'" parameter "'.$specificParameter.'"');
                
            }
  
        }
        
        throw new \Exception('unable to get header "'.$header.'"');
        
    }

    public function unsetHeader($header, $part = 0) {

        if(!array_key_exists($part, $this->parts))
            throw new \Exception('part "'.$part.'" is not available');

        if(!array_key_exists('headers', $this->parts[$part]))
            throw new \Exception('headers of part "'.$part.'" are not set');

        foreach($this->parts[$part]['headers'] as $headerName => $headerValue) {
  
            if(preg_match('#^'.preg_quote($header, '#').'$#i', $headerName)) {
                
                unset($this->parts[$part]['headers'][$headerName]);
                
                return $this;
                
            }
  
        }
        
        throw new \Exception('unable to remove header "'.$header.'"');
        
    }

    public function isMultipart() {
        
		return 2 < count($this->parts);
        
    }

	public function isAlternative() {
        
        return isset($this->parts[1]) && isset($this->parts[2]);

	}

	public function isMixed() {
        
        $nb = 1;
        
        if(isset($this->parts[1])) $nb++;
        if(isset($this->parts[2])) $nb++;

		return $nb < count($this->parts);

	}
    
    public function toHeaders() {

        try {
            
            $this->getHeader('MIME-Version');
            
        }catch(\Exception $e) {
            
            $this->setHeader('MIME-Version', '1.0');
            
        }

        try {
            
            $this->getHeader('Return-Path');
            
        }catch(\Exception $e) {
            
            $this->setHeader('Return-Path', $this->getHeader('From'));
            
        }

        try {
            
            $this->getHeader('Date');
            
        }catch(\Exception $e) {
            
            $this->setHeader('Date', date('D, j M Y H:i:s O'));
            
        }
        
        $headers = $this->partHeadersToString(0);
        
		if($this->isMultipart()) {

			if($this->isMixed()) {

				$headers .= 'Content-Type: multipart/mixed; boundary="'.$this->boundary.'"'.self::EOL;

			} else {

				$headers .= 'Content-Type: multipart/alternative; boundary="'.$this->boundary.'"'.self::EOL;

			}

		} else {
         
			foreach($this->parts as $key => $part)
                if(0 < $key)
                    $headers .= $this->partHeadersToString($key).self::EOL;
            
        }
        
        return $headers;
        
    }
    
    public function toParts() {
        
		if(!$this->isMultipart()) {

			foreach($this->parts as $key => $part)
                if(0 < $key)
                    return $this->partBodyToString($key);

		} else {

            $parts = 'This is a message with multiple parts in MIME format.'.self::EOL.self::EOL;
             
        }

		if($this->isMixed() && $this->isAlternative()) {

			$parts .= '--'.$this->boundary.self::EOL; 

			$alternative_boundary = '-----='.md5($this->boundary);

			$parts .= 'Content-Type: multipart/alternative; boundary="'.$alternative_boundary.'"'.self::EOL.self::EOL;

			$parts .= '--'.$alternative_boundary.self::EOL;
			$parts .= $this->partHeadersToString(1).self::EOL;
			$parts .= $this->partBodyToString(1).self::EOL;

			$parts .= '--'.$alternative_boundary.self::EOL;
			$parts .= $this->partHeadersToString(2).self::EOL;
			$parts .= $this->partBodyToString(2).self::EOL;

			$parts .= '--'.$alternative_boundary.'--'.self::EOL;

			foreach($this->parts as $key => $part) {

				if($key < 3) continue;
                
                $parts .= '--'.$this->boundary.self::EOL;
                $parts .= $this->partHeadersToString($key).self::EOL;
                $parts .= $this->partBodyToString($key).self::EOL;

			}

		} else {

			foreach($this->parts as $key => $part) {
                
                if(0 === $key) continue;
                
                $parts .= '--'.$this->boundary.self::EOL;
                $parts .= $this->partHeadersToString($key).self::EOL;
                $parts .= $this->partBodyToString($key).self::EOL;
                
                
            }

		}

		return $parts.'--'.$this->boundary.'--';

    }
    
    public function toData() {

        return $this->toHeaders().self::EOL.$this->toParts();
        
    }

	public function send() {

        $to = $this->getHeader('To');
        $subject = $this->encodeHeader($this->getHeader('Subject'), $this->headersEncoding, $this->headersCharset);

        $this->unsetHeader('To');
        $this->unsetHeader('Subject');

        $headers = $this->toHeaders();

        $this->setHeader('To', $to);
        $this->setHeader('Subject', $subject);
        
		if(!mail($to, $subject, $this->toParts(), $headers)) 
            throw new \Exception('unable to send mail');

		return $this;

	}

    private function partHeadersToString($key) {
        
        $part = $this->parts[$key];

        if(!isset($part['headers']))
            throw new \Exception('no headers for part "'.$key.'"');
        
        $data = '';
  
        foreach($part['headers'] as $headerName => $headerValue) {
            
            $data .= $headerName.': ';
            
            if($key === 0)
                $headerValue = $this->encodeHeader($headerValue, $this->headersEncoding, $this->headersCharset);
                
            $data .= $headerValue.self::EOL;

        }

        return $data;
        
    }
    
    private function partBodyToString($key) {
        
        $part = $this->parts[$key];

        if(!isset($part['body']))
            throw new \Exception('no body for part "'.$key.'"');
        
        $data = '';
        
        $encoding = $this->getHeader('content-transfer-encoding', $key);
        $charset = $this->getHeader('content-type', $key, 'charset');

        $data .= $this->encodeBody($part['body'], $encoding, $charset);

        return $data;
        
    }
    
    private function decodeBody($body, $encoding, $charset) {

        if($encoding == 'base64')
			return base64_decode(preg_replace('#(\r\n|\r|\n)#s', '', $body));

		if($encoding == 'quoted-printable') {
            return quoted_printable_decode(preg_replace('#(=\r\n|=\r|=\n)#s', '', $body));
        }
        return $body;
        
    }
    
    private function decodeHeader($headerValue, $encoding, $charset) {
        
        return $headerValue;
        
    }
    
    private function encodeBody($body, $encoding, $charset) {
 
		if($encoding == 'base64')
			return chunk_split(base64_encode($body), $this->bodySplit);

		if($encoding == 'quoted-printable') {

			$emulateImap8bit = true;

			$regexp = '#[^\x09\x20\x21-\x3C\x3E-\x7E]#e';

			if($emulateImap8bit)
				$regexp = '#[^\x20\x21-\x3C\x3E-\x7E]#e';

			$lines = preg_split('#(\r\n|\r|\n)#', $body);

			foreach($lines as $lineNumber => $line) {

				if(strlen($line) === 0) 
					continue;

				$line = preg_replace($regexp, 'sprintf("=%02X", ord("$0"));', $line); 

				$lineLength = strlen($line);
				$lastChar = ord($line[$lineLength - 1]);

				if(!($emulateImap8bit && ($lineNumber == count($lines) - 1))) {
					if(($lastChar == 0x09) || ($lastChar == 0x20)) {
						$line[$lineLength - 1] = '=';
						$line .= $lastChar == 0x09 ? '09' : '20';					
					}
				}

				if($emulateImap8bit) 
					$line = str_replace(' =0D', '=20=0D', $line);

				preg_match_all('#.{1,'.($this->bodySplit - 3).'}([^=]{0,2})?#', $line, $match);

				$line = implode('='.self::EOL, $match[0]);

				$lines[$lineNumber] = $line;

			}

			return implode(self::EOL, $lines);

		}

		return $body;
        
    }
    
    private function encodeHeader($headerValue, $encoding, $charset) {
        
        $regexp = '#([\\x00-\\x1F\\x3D\\x3F\\x7F-\\xFF])#e';

		if(!preg_match($regexp, $headerValue))
			return $headerValue;

		if(!in_array($encoding, array('base64', 'quoted-printable')))
			return $headerValue;

		$prefix = '=?'.$charset.'?'.strtoupper(substr($encoding, 0, 1)).'?';
		$suffix = '?=';

		$lineLength = $this->bodySplit - strlen($prefix) - strlen($suffix);

		$headerValue = trim($headerValue);

		if($encoding == 'quoted-printable') {

			$headerValue = preg_replace($regexp, '"=".strtoupper(dechex(ord("\1")))', $headerValue);
			$headerValue = preg_replace('#\s#', '_', $headerValue);
	
			preg_match_all('#.{1,'.($lineLength - 2).'}([^=]{0,2})?#', $headerValue, $headerValue);
			$headerValue = $headerValue[0];

			foreach($headerValue as $key => $value)
				$headerValue[$key] = $prefix.$value.$suffix;
			
		} elseif($encoding == 'base64') {

			$headerValue = base64_encode($headerValue);
			
			$headerValue = str_split($headerValue, $lineLength);
			
			foreach($headerValue as $key => $value)
				$headerValue[$key] = $prefix.$value.$suffix;

		}

		return implode(self::EOL."\t", $headerValue);
        
    }

    private function setBody($body, $part) {
        
        $this->parts[$part]['body'] = (string) $body;
        
        return $this;
                        
    }
    
    private function getBody($part) {
          
        if(!array_key_exists($part, $this->parts))
            throw new \Exception('part "'.$part.'" is not available');
        
        return $this->parts[$part]['body'];
                        
    }
    
}

// Do not clause PHP tags unless it is really necessary
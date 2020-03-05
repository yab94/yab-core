<?php

namespace Yab\Core;

if(!defined('LOG_LOCAL0'))
    define('LOG_LOCAL0', 0);

class Logger {

    use Factory;

    static protected $priorities = array(
        LOG_EMERG => 'LOG_EMERG', // 	système inutilisable
        LOG_ALERT => 'LOG_ALERT', // 	une décision doit être prise immédiatement
        LOG_CRIT => 'LOG_CRIT', // 	condition critique
        LOG_ERR => 'LOG_ERR', // 	condition d'erreur
        LOG_WARNING => 'LOG_WARNING', // 	condition d'alerte
        LOG_NOTICE => 'LOG_NOTICE', // 	condition normale, mais significative
        LOG_INFO => 'LOG_INFO', // 	message d'information
        LOG_DEBUG => 'LOG_DEBUG', // 	message de déboguage
    );

    protected $priority = LOG_ERR;
    protected $ident = '';
    protected $prefix = '';
    protected $display = false;
    protected $options = LOG_CONS | LOG_ODELAY | LOG_PID;
    protected $facility = LOG_LOCAL0;

    protected $debugMessages = [];
    
    public function setIdent($ident) {

        $this->ident = (string) $ident;
    }

    public function setPrefix($prefix) {

        $this->prefix = (string) $prefix;
    }

    public function setOptions($options) {

        $this->options = $options;
    }

    public function setDisplay($display) {

        $this->display = (bool) $display;

    }

    public function setFacility($facility) {

        $this->facility = $facility;

    }

    public function setPriority($priority) {

        $this->priority = (int) $priority;
    }

    public function arbiter($priority) {

        return !(bool) ($this->priority < $priority);
    }

    public function write($priority, $message) {

        if (!$this->arbiter($priority))
            return false;

        $message = self::$priorities[$priority] . ' ' . $message;
        
        if ($this->arbiter(LOG_DEBUG)) 
            $this->debugMessages[] = $this->ident . ' ' . $this->prefix . ' '. $message; 

        if($this->display)
            echo $message;

        openlog($this->ident . ' ' . $this->prefix, $this->options, (int) $this->facility);
        syslog($priority, $message);
        closelog();

        return true;
    }
    
    public function getDebugMessages() {
        
        return $this->debugMessages;
        
    }

    public function debug($message) {

        return $this->write(LOG_DEBUG, $message);
    }

    public function info($message) {

        return $this->write(LOG_INFO, $message);
    }

    public function notice($message) {

        return $this->write(LOG_NOTICE, $message);
    }

    public function warning($message) {

        return $this->write(LOG_WARNING, $message);
    }

    public function error($message) {

        return $this->write(LOG_ERR, $message);
    }

    public function critical($message) {

        return $this->write(LOG_CRIT, $message);
    }

    public function alert($message) {

        return $this->write(LOG_ALERT, $message);
    }

    public function emerg($message) {

        return $this->write(LOG_EMERG, $message);
    }

}


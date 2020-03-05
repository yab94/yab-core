<?php

namespace Yab\Core;

class Application {

    const EVENT_PRE_ROUTE = 'Application::preRoute';
    const EVENT_POST_ROUTE = 'Application::postRoute';
    
    static protected $contextSetted = false;
    static protected $namespaces = [];

    protected $config = null;

    static public function setContext() {
        
        if(self::$contextSetted)
            return;

        self::$contextSetted = true;

        if(!defined('YAB_ROOT'))
            define('YAB_ROOT', dirname(dirname(dirname(__DIR__))));

        spl_autoload_register([__CLASS__, 'loadClass']);

        self::addNamespace(__NAMESPACE__, __DIR__);

        set_error_handler([__CLASS__, 'errorHandler']);
        set_exception_handler([__CLASS__, 'exceptionHandler']);
        register_shutdown_function([__CLASS__, 'shutdownFunction']);
        
    }

    static public function addNamespace($namespace, $directory) {

        if(!isset(self::$namespaces[$namespace]))
            self::$namespaces[$namespace] = [];
        
        self::$namespaces[$namespace][$directory] = $directory;

    }

    static private function loadClass($class) {

        foreach (self::$namespaces as $namespace => $autoloadDirectories) {
          
            $namespace = ltrim($namespace, '\\').'\\';
        
            if(strpos($class, $namespace) !== 0)
                continue;
            
            $classPath = substr(str_replace('\\', DIRECTORY_SEPARATOR, $class), strlen($namespace)).".php";
        
            foreach($autoloadDirectories as $autoloadDirectory) {
        
                $filePath = $autoloadDirectory . DIRECTORY_SEPARATOR . $classPath;
                
                if (!file_exists($filePath)) {
                    
                    continue;
                }
                
                include_once $filePath;
                
                if (!class_exists($class, false) && !interface_exists($class) && !trait_exists($class, false)) {
                    throw new \Exception('beware of the file "' . $filePath . '" that dont define class "' . $class . '"');
                }

                return true;
                
            }    

        }

    }

    static public function addDirectory($namespace, $directory) {

        if(!isset(self::$namespaces[$namespace]))
            self::$namespaces[$namespace] = [];
        
        self::$namespaces[$namespace][$directory] = $directory;

    }

    static public function errorHandler($errno, $errstr, $errfile, $errline) {

        return self::exceptionHandler(new \Exception($errno . ': ' . $errstr . ' (' . $errfile . ':L' . $errline . ')'));
    }

    static public function exceptionHandler(\Throwable $e) {

        return Logger::getLogger()->error("Type: " . get_class($e) . "; Message: {$e->getMessage()}; File: {$e->getFile()}; Line: {$e->getLine()};");
      
    }

    static public function shutdownFunction() {

        $error = error_get_last();
        
        if(!is_array($error))
            return;

        if (in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR, E_RECOVERABLE_ERROR, E_CORE_WARNING, E_COMPILE_WARNING, E_PARSE])) {
            return self::errorHandler($error["type"], $error["message"], $error["file"], $error["line"]);
        }
    }
    
    static public function debug(Request $request, Response $response, \Throwable $e = null) {
    
        $view = new View();
        
        $view->setTemplate('templates/debug.php');
        
        $view->request = $request;
        $view->response = $response;
        
        if($e !== null) {

            $traces = $e->getTrace();
            
            $trace = [
                'file' => $e->getFile(), 
                'line' => $e->getLine(), 
                'class' => '', 
                'function' => '', 
                'args' => []
            ];
            
            array_unshift($traces, $trace);

            $view->traces = $traces;
            $view->title = 'A '.get_class($e).' has been caught!';
            $view->message = $e->getMessage();

        } else {
            
            $view->title = '[YDB] - Yab Debug Bar';
            $view->message = '';

            $view->traces = [];

        }
        
        $response->appendBody($view->getRender());
        
    }

    public function __construct() {
        
        self::setContext();

        $this->config = Config::getConfig();

        $this->configure(Config::fromIniFile(dirname(__DIR__).'/config/config.ini'));

    }

    public function configure(Config $config) {

        $this->config->fusion($config);

        // Prise en compte de fichiers de configuration additionnels
        foreach ($config->getParam('application.additionnal_config_files', []) as $additional_config_file) 
            $this->configure(Config::fromIniFile($additional_config_file));

        return $this;

    }

    public function applyConfig() {

        // Paramétrage de PHP
        foreach ($this->config->getParam('php', []) as $param => $value) 
            ini_set($param, $value);

        // Paramétrage de l'Autoload
        foreach ($this->config->getParam('autoload', []) as $namespace => $directory) 
            self::$namespaces[(string) $namespace][] = (string) $directory;
            
        // Paramétrage de l'include path
        foreach ($this->config->getParam('include.directories', []) as $directory)
            set_include_path($directory.PATH_SEPARATOR.get_include_path());

        // Paramétrage des loggers
        foreach ($this->config->getParam('logger', []) as $logger => $params) {

            if(!Logger::issetLogger($logger))
                Logger::setLogger(new Logger(), $logger);
            
            foreach($this->config->getParam('logger.'.$logger, []) as $key => $value) {
                $setter = 'set'.ucfirst($key);
                Logger::getLogger($logger)->$setter($value);
            }

        }
        
        // Paramétrage des base de données
        foreach ($this->config->getParam('database', []) as $name => $params) {

            $database = new Database(
                $this->config->getParam('database.' . $name . '.dsn'), 
                $this->config->getParam('database.' . $name . '.username'), 
                $this->config->getParam('database.' . $name . '.password'), 
                $this->config->getParam('database.' . $name . '.driver_options', [])
            );
            
            Database::setDatabase($database, $name);

        }
        
        // Paramétrage des évenements
        foreach ($this->config->getParam('event', []) as $event => $listener) 
            Event::getEvent()->addListeners($event, $listener);

        return $this;

    }

    public function route(Request $request = null, Response $response = null) {
 
        if($request === null)
            $request = Request::fromGlobals();

        if($response === null)
            $response = new Response(404);

        try {

            $this->applyConfig();

            Event::getEvent()->fire(self::EVENT_PRE_ROUTE, ['request' => $request, 'response' => $response]);

            if(!Controller::route($request, $response))
                throw new \OutOfBoundsException('unable to route request');

            Event::getEvent()->fire(self::EVENT_POST_ROUTE, ['request' => $request, 'response' => $response]);

            if (Logger::getLogger()->arbiter(LOG_DEBUG)) 
                $this->debug($request, $response);

        } catch(\Throwable $e) {

            $this->exceptionHandler($e);
            $this->exceptionResponseHandler($response, $e);

            if (Logger::getLogger()->arbiter(LOG_DEBUG)) 
                $this->debug($request, $response, $e);

        } 
        
        Response::flush($response);

    }

    public function exceptionResponseHandler(Response $response, \Throwable $exception) {

        $traces = $exception->getTrace();
        
        $trace = [
            'file' => $exception->getFile(), 
            'line' => $exception->getLine(), 
            'class' => '', 
            'function' => '', 
            'args' => []
        ];
        
        array_unshift($traces, $trace);
    
        $view = new View();

        if($exception instanceof \OutOfBoundsException) {

            $response->setCode(404);
            $view->setTemplate('templates/not_found.php');

        } else {

            $response->setCode(500);
            $view->setTemplate('templates/server_error.php');

        }
        
        $view->exception = $exception;
        $view->traces = $traces;

        $response->appendBody($view->getRender());

    }

    public function launch(array $stdin = [], $stdout = 'php://output') {

        try {

            $this->applyConfig();

            Command::launch($stdin, $stdout);

        } catch(\Throwable  $e) {

            Command::writeToStream($stdout, $e->getMessage());

            $this->exceptionHandler($e);

        }

	}

}
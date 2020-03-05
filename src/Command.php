<?php

namespace Yab\Core;
        
abstract class Command {

    use Tool;

    static private $styles = [
		'bold'		=>  '1',
		'italic'	=>  '3',
		'blink'	    =>  '5',
		'underline'	=>  '4',
		'green'		=>  '0;32',
		'red'		=>  '0;31',
		'yellow'	=>  '1;33',
		'blue'		=>  '0;34',
		'cyan'		=>  '0;36',
		'purple'	=>  '0;35',
		'black'		=>  '0;30',
		'white'		=>  '1;37',
		'gray'		=>  '1;30',
		'bg-green'	=>  '42',
		'bg-red'	=>  '41',
		'bg-yellow'	=>  '43',
		'bg-blue'   =>  '44',
		'bg-cyan'	=>  '46',
		'bg-magenta'=>  '45',
		'bg-black'	=>  '40'
	];

    protected $inputStream = null;
    protected $outputStream = 'php://output';

    protected $command = '';
    protected $params = [];

    static public function getCommands() {

        foreach(Config::getConfig()->getParam('commands') as $command => $closure)
            yield $command => $closure;

    }

    static public function launch(array $argv, $outputStream) {

        if(!count($argv))
            $argv = $_SERVER['argv'];

        $executed = false;

        foreach(self::getCommands() as $command => $closure) {

            if(self::dispatch($argv, $outputStream, $command, $closure))
                $executed = true;

            if($executed)
                break;

        }

        if(!$executed) {

            $usage = 'Usage: '.implode(' ', $argv).' [COMMAND]'.PHP_EOL;
            $usage .= "List of available commands:".PHP_EOL;
    
            foreach(Command::getCommands() as $command => $closure)
                $usage .= " - ".$command.PHP_EOL; 

            throw new \Exception(trim($usage));

        }

    }

    static protected function dispatch(array $argv, $outputStream, $command, $closure) {

        if (!isset($argv[1]) || ($command != $argv[1]))
            return false;

        $closure = self::getClosure($argv, $outputStream, $closure);

        $closure();

        return true;

    }

    static protected function getClosure(array $argv, $outputStream, $closure) {

        if($closure instanceof \Closure)
            return $closure;

        $parts = preg_split('#\s+#', $closure);
        $commandAction = array_shift($parts);
        $commandAction = explode('.', $commandAction);
        $command = array_shift($commandAction);
        $action = array_shift($commandAction);

        if (!class_exists($command)) {
            throw new \Exception('unexisting class "' . $command . '"');
        }

        if (!is_subclass_of($command, 'Yab\Core\Command', true)) {
            throw new \Exception('bad command class "' . $command . '"');
        }

        return function() use($command, $action, $argv, $outputStream, $parts) {

            $command = new $command($argv, $outputStream);
            
            $command->before();
            
            $command->$action(...$parts);
            
            $command->after();

        };

    }

    final public function __construct(array $stdin, $outputStream) {

        $this->command = array_shift($stdin);
        $this->params = $stdin;
        $this->outputStream = (string) $outputStream;

    }

    public function getCommand() {

        return $this->command;

    }

    public function getParam($index, $default = null) {

        return isset($this->params[$index]) ? $this->params[$index] : $default;

    }

    public function getParams() {

        return $this->params;

    }

    public function read($hide = false) {
    
        if(!$hide) 
            return rtrim(fgetc(STDIN), PHP_EOL);

        $oldStyle = shell_exec('stty -g');
        
        shell_exec('stty -icanon -echo min 1 time 0');

        $param = '';
        
        while (true) {
            
            $char = fgetc(STDIN);

            if ($char === "\n") {
                break;
            } else if (ord($char) === 127) {
                if (strlen($param) > 0) {
                    fwrite(STDOUT, "\x08 \x08");
                    $param = substr($param, 0, -1);
                }
            } else {
                fwrite(STDOUT, "*");
                $param .= $char;
            }
        }
        
        shell_exec('stty ' . $oldStyle);
        
        return rtrim($param, PHP_EOL);
        
    }

    static public function writeToStream($stream, $message, $newLine = true) {

        if($newLine)
            $message .= PHP_EOL;
    
        $handler = fopen($stream, 'w');
        
        if(!$handler)
            throw new \Exception('unable to open php std output');
        
        $written = fwrite($handler, $message);
        
        if($written < strlen($message))
            throw new \Exception('unable to fully write into std output');
        
        fclose($handler);
        
        return true;
        
    }

    protected function before() {
        
    }

    protected function after() {
        
    }

    protected function write($message, $newLine = true) {

        if($newLine)
            $message .= PHP_EOL;
    
        $handler = fopen($this->outputStream, 'w');
        
        if(!$handler)
            throw new \Exception('unable to open php std output');
        
        $written = fwrite($handler, $message);
        
        if($written < strlen($message))
            throw new \Exception('unable to fully write into std output');
        
        fclose($handler);
        
        return true;
        
    }

    protected function startDaemon($name, $startClosure, $stopClosure) {

        $pidFile = Config::getConfig()->getParam('general.pidDir', '/var/run') . DIRECTORY_SEPARATOR . $name . Config::getConfig()->getParam('general.pidExt', '.pid');

        if (file_exists($pidFile))
            throw new \Exception('can not start daemon because it is already started (' . $pidFile . ':' . file_get_contents($pidFile) . ')');

        $pid = pcntl_fork();

        if ($pid == -1) {

            throw new \Exception('fork error');
        } elseif ($pid) {

            return Logger::getLogger()->info('daemon started');
        }

        posix_setsid();

        $pid = posix_getpid();

        file_put_contents($pidFile, $pid);

        declare(ticks = 1);

        pcntl_signal(SIGTERM, function($sig) use($pidFile, $stopClosure) {

            $this->runClosure($stopClosure);

            if (file_exists($pidFile) && !unlink($pidFile)) {
                Logger::getLogger()->warning('failed to delete pid file "' . $pidFile . '"');
            }

            Logger::getLogger()->info('daemon stopped');

            exit();
        });

        chdir('/');

        umask(0);

        return $this->runClosure($startClosure);
    }

    protected function stopDaemon($name) {

        $pidFile = Config::getConfig()->getParam('general.pidDir', '/var/run') . DIRECTORY_SEPARATOR . $name . Config::getConfig()->getParam('general.pidExt', '.pid');

        if (!file_exists($pidFile))
            throw new \Exception('can not stop daemon because it is not started');

        $pid = file_get_contents($pidFile);

        if (!posix_kill($pid, SIGTERM))
            Logger::getLogger()->warning('failed to stop process "' . $pid . '"');

        if (file_exists($pidFile) && !unlink($pidFile))
            Logger::getLogger()->warning('failed to delete pid file "' . $pidFile . '"');

        return true;
    }
    
	protected function decorate($message, array $options) {
        
        $decorations = '';
            
        foreach($options as $option) {

            if(array_key_exists($option, self::$styles)) 
                $decorations .= "\033[" . self::$styles[$option] . "m";
            
                
        }
        
		return $decorations.$message."\033[0m";
        
	}
    
    protected function askPassword($question) {

        $message = $question.' ? ';
        
        $this->write($message, false);

        $param = $this->input->read(true);
        
        $this->write(PHP_EOL);
        
        return $param;
        
    }
    
    protected function askParam($question, $defaultParam = null, $retry = 0) {
        
        $message = $question.' ? ';
        
        if($defaultParam)
            $message .= '(default: '.$defaultParam.') ';
        
        $this->write($message, false);

        $param = $this->read();

        if($param) 
            return $param;

        if($defaultParam !== null)
            return $defaultParam;
        
        return $this->askParam($question, $defaultParam, $retry++);
        
    }

}
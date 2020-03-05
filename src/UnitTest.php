<?php

namespace Yab\Core;

use ReflectionMethod;

class UnitTest extends Command {

    protected $assertions = 0;
    protected $fails = 0;
    protected $success = 0;
    protected $errors = [];

    protected function beforeAll() {}
    protected function beforeEach() {}
    protected function afterAll() {}
    protected function afterEach() {}

    protected function assertTrue(bool $assertion) {

        return $this->results($assertion);

    }

    protected function assertFalse(bool $assertion) {

        return $this->results(!$assertion);

    }

    protected function assertEquals($exprA, $exprB) {

        if($exprA !== $exprB)
            return $this->results(false, '"'.$exprA.'" ('.gettype($exprA).') differs from "'.$exprB.'" ('.gettype($exprA).')');

        return $this->results(true);

    }

    protected function results(bool $success, string $additionnalMessage = '') {

        $this->assertions++;

        if($success) {
            $this->success++;
        } else {
            $this->fails++;
        }

        $debugBacktrace = debug_backtrace();

        $message = '- '.get_class($this).':L'.$debugBacktrace[1]['line'];
        $message .= ' '.$debugBacktrace[2]['function'];
        $message .= ' '.$debugBacktrace[1]['function'];
        $message .= ' '.($success ? 'success' : 'failed');
        $message .= ' '.$additionnalMessage;

        if($success)
            return $this->write($this->decorate('+', array('green', 'bold')), false);

        $this->errors[] = $message;

        return $this->write($this->decorate('-', array('red', 'bold')), false);

    }

    public function run() {

        $this->write($this->decorate('Testing "'.get_class($this).'": ', array('white', 'bold')), false);

        $this->beforeAll();

        $reflexion = new \ReflectionClass($this);

        $tests = 0;

        foreach($reflexion->getMethods() as $method) {

            if(!$method->isPublic() || !preg_match('#^test#', $method->getName()))
                continue;

            $methodName = $method->getName();

            $this->beforeEach();

            $this->$methodName();

            $tests++;

            $this->afterEach();

        }

        $this->afterAll();

        $this->write($this->decorate(PHP_EOL.'Performed '.$tests.' test(s), '.$this->assertions.' assertion(s), '.$this->success.' success, '.$this->fails.' fail(s), '.count($this->errors).' error(s) ', array('white', 'bold')), true);

        foreach($this->errors as $error)
            $this->write($this->decorate($error, array('red', 'bold')), true);

    }

    public function sequence(string $directory, string $class) {

        $this->write($this->decorate('Sequencing tests from "'.$directory.'":', array('white', 'bold')));

        if(!is_dir($directory))
            throw new \Exception('invalid tests directory "'.$directory.'"');

        $dh = opendir($directory);
 
        while($file = readdir($dh)) {

            if(preg_match('#^\.#', $file))
                continue;
           
            if(preg_match('#^\_#', $file))
                continue;

            if(is_dir($directory.DIRECTORY_SEPARATOR.$file))
                continue;
              
            $className = $class.'\\'.preg_replace('#\.[a-z0-9_]+$#', '', $file);

            $test = new $className(array($this->command) + $this->params, $this->outputStream);

            $test->run();

        }

    }

    public function coverage(string $srcDirectory, string $srcNamespace, string $testsNamespace) {

        $this->write($this->decorate('Calculating test-coverage from "'.$srcDirectory.'":', array('white', 'bold')));

        if(!is_dir($srcDirectory))
            throw new \Exception('invalid srcDirectory "'.$srcDirectory.'"');

        $dh = opendir($srcDirectory);
 
        while($file = readdir($dh)) {

            if(preg_match('#^\.#', $file))
                continue;
           
            if(preg_match('#^\_#', $file))
                continue;

            if(is_dir($srcDirectory.DIRECTORY_SEPARATOR.$file))
                continue;
              
            $className = $srcNamespace.'\\'.preg_replace('#\.[a-z0-9_]+$#', '', $file);
            $testClassName = $testsNamespace.'\\'.preg_replace('#\.[a-z0-9_]+$#', '', $file).'Test';

            if(!class_exists($className) && !trait_exists($className) && !interface_exists($className)) {
                $this->write($this->decorate('invalid file in srcDirectory "'.$file.'" not defining class "'.$className.'"', array('red', 'bold')));
                continue;
            }

            if(!class_exists($testClassName)) {
                $this->write($this->decorate('missing testing class "'.$testClassName.'"', array('red', 'bold')));
                continue;
            }

            $this->write($this->decorate('testing class "'.$testClassName.'" found for src class "'.$className.'"', array('green', 'bold')));
            
            $srcReflect = new \ReflectionClass($className);
            $testReflect = new \ReflectionClass($testClassName);

            $srcMethods = $srcReflect->getMethods();

            foreach($srcMethods as $srcMethod) {

                try {

                    $testMethod = $testReflect->getMethod('test'.ucfirst($srcMethod->getName()));
                    
                    $this->write($this->decorate('- "'.$testMethod->getName().'" tests "'.$srcMethod->getName().'"', array('green', 'bold')));
            
                } catch(\ReflectionException $e) {

                    $this->write($this->decorate('- missing testing method for "'.$srcMethod->getName().'"', array('red', 'bold')));
            
                }

            }












            
        }


        

    }

}
<?php

namespace Yab\Core;

trait Singleton {

    static protected $instance = null;

    static public function getInstance() {

        if(self::$instance === null)
            self::$instance = new static();

        return self::$instance;

    }

    static public function __callStatic(string $method, array $arguments = []) {

        $class = static::class;
		$class = explode('\\', $class);
		$class = array_pop($class);

        if(preg_match('#^get'.preg_quote(ucfirst($class), '#').'$#', $method))
            return self::getInstance(...$arguments);

        throw new \BadMethodCallException('bad static method call "'.$method.'"');

    }

    final protected function __construct() {

    }

}
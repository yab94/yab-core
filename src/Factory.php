<?php

namespace Yab\Core;

trait Factory {

    static protected $instances = [];

    protected $instanceName = '';

    static public function __callStatic(string $method, array $arguments = []) {

        $class = static::class;
		$class = explode('\\', $class);
		$class = array_pop($class);

        if(preg_match('#^get'.preg_quote(basename($class), '#').'$#', $method))
            return self::getInstance(...$arguments);

        if(preg_match('#^set'.preg_quote(basename($class), '#').'$#', $method))
            return self::setInstance(...$arguments);

        if(preg_match('#^isset'.preg_quote(basename($class), '#').'$#', $method))
            return self::issetInstance(...$arguments);

        if(preg_match('#^get'.preg_quote(ucfirst(Tool::pluralize($class)), '#').'$#', $method))
            return self::getInstances();

        throw new \BadMethodCallException('bad static method call "'.$method.'"');

    }

    static public function issetInstance($instanceName = 'default') {

        $class = static::class;

        return isset(self::$instances[$class][$instanceName]);

    }

    static public function setInstance($instance, $instanceName = 'default') {

        $class = static::class;

        if(!($instance instanceof $class))
            throw new \Exception('bad class instance "'.get_class($instance).'" for factory');

        self::$instances[$class][$instanceName] = $instance;

    }

    static public function getInstance($instanceName = 'default') {

        $class = static::class;

        if($instanceName == 'default' && !isset(self::$instances[$class][$instanceName]))
            self::$instances[$class][$instanceName] = new $class();

        if(!isset(self::$instances[$class][$instanceName])) 
            throw new \Exception('no "'.$instanceName.'" instance for class "'.$class.'"');

        self::$instances[$class][$instanceName]->instanceName = (string) $instanceName;

        return self::$instances[$class][$instanceName];

    }

    static public function getInstances() {

        $class = static::class;

        return isset(self::$instances[$class]) ? self::$instances[$class] : [];

    }

    public function getInstanceName() {

        return $this->instanceName;

    }

}
<?php

namespace Yab\Core;

use Yab\Core\Application;
use Yab\Core\Statement;
use Yab\Core\View;

trait Tool {

    static public function toClosure($mixed) {
        
        if(preg_match('#^([A-Za-z0-9_\\\]+)\.([A-Za-z0-9_]+)$#', $mixed, $match)) {

            $class = $match[1];
            $method = $match[2];
            
            return function(...$params) use($class, $method) {

                $instance = new $class();

                return $instance->$method(...$params);

            };

        }
        
        throw new \Exception('invalid closure synthax "'.$mixed.'"');

    }
		
    static public function html($mixed) {

        return htmlentities(self::scalarOrNullOrFail($mixed), ENT_QUOTES, 'UTF-8'); 
    }

    static public function htmlAttributes(array $mixed) {

        $html = '';

        foreach ($mixed as $key => $value)
            $html .= ' ' . self::html($key) . '="' . self::html($value) . '"';

        return $html;
    }

    static public function humanReadableSize($value) {

        if (!is_numeric($value))
            throw new \Exception('a scalar value is needed');

        $units = array('B', 'KB', 'MB', 'GB', 'TB', 'PB');

        for ($i = 0; $value > 1024; $i++)
            $value /= 1024;

        return round($value, 2) . ' ' . $units[$i];
    }

    static public function snakeCase($mixed) {

        self::scalarOrNullOrFail($mixed);

        preg_match_all('#[^a-z0-9]+#', $mixed, $match);

        foreach ($match[0] as $value)
            $mixed = str_replace($match[0], '_' . strtolower($match[0]), $mixed);

        return $mixed;
    }

    static public function pascalCase($mixed) {

        self::scalarOrNullOrFail($mixed);

        preg_match_all('#[^a-zA-Z0-9]+([a-zA-Z0-9]?)#', $mixed, $match);

        foreach ($match[0] as $key => $value)
            $mixed = str_replace($value, ucfirst($match[1][$key]), $mixed);

        return ucfirst($mixed);
    }

    static public function camelCase($mixed) {

        self::scalarOrNullOrFail($mixed);

        preg_match_all('#[^a-zA-Z0-9]+([a-zA-Z0-9]?)#', $mixed, $match);

        foreach ($match[0] as $key => $value)
            $mixed = str_replace($value, ucfirst($match[1][$key]), $mixed);

        return lcfirst($mixed);
    }

    static public function arrayize($value) {

        if(is_array($value))
            return $value;

        if($value instanceof \Iterator)
            return $value;

        return array($value);

    }

    static public function isAssociativeArray(array $array) {
        foreach($array as $key => $value) {
            if(is_numeric($key)) {
                return false;
            }
        }
        return true;
    }

    static public function singularize($mixed) {

        self::scalarOrNullOrFail($mixed);

        $singulars = array(
            '/(quiz)zes$/i' => '$1',
            '/(matr)ices$/i' => '$1ix',
            '/(vert|ind)ices$/i' => '$1ex',
            '/^(ox)en$/i' => '$1',
            '/(alias)es$/i' => '$1',
            '/(octop|vir)i$/i' => '$1us',
            '/(cris|ax|test)es$/i' => '$1is',
            '/(shoe)s$/i' => '$1',
            '/(o)es$/i' => '$1',
            '/(bus)es$/i' => '$1',
            '/([m|l])ice$/i' => '$1ouse',
            '/(x|ch|ss|sh)es$/i' => '$1',
            '/(m)ovies$/i' => '$1ovie',
            '/(s)eries$/i' => '$1eries',
            '/([^aeiouy]|qu)ies$/i' => '$1y',
            '/([lr])ves$/i' => '$1f',
            '/(tive)s$/i' => '$1',
            '/(hive)s$/i' => '$1',
            '/(li|wi|kni)ves$/i' => '$1fe',
            '/(shea|loa|lea|thie)ves$/i' => '$1f',
            '/(^analy)ses$/i' => '$1sis',
            '/((a)naly|(b)a|(d)iagno|(p)arenthe|(p)rogno|(s)ynop|(t)he)ses$/i' => '$1$2sis',
            '/([ti])a$/i' => '$1um',
            '/(n)ews$/i' => '$1ews',
            '/(h|bl)ouses$/i' => '$1ouse',
            '/(corpse)s$/i' => '$1',
            '/(us)es$/i' => '$1',
            '/s$/i' => '',
        );

        $irregulars = array(
            'move' => 'moves',
            'foot' => 'feet',
            'goose' => 'geese',
            'sex' => 'sexes',
            'child' => 'children',
            'man' => 'men',
            'tooth' => 'teeth',
            'person' => 'people',
        );

        $uncountables = array(
            'sheep',
            'fish',
            'deer',
            'series',
            'species',
            'money',
            'rice',
            'information',
            'equipment',
        );

        if (in_array(strtolower($mixed), $uncountables))
            return $mixed;

        foreach ($irregulars as $pattern => $result) {

            $pattern = '/' . $pattern . '$/i';

            if (preg_match($pattern, $mixed))
                return preg_replace($pattern, $result, $mixed);
        }

        foreach ($singulars as $pattern => $result) {

            if (preg_match($pattern, $mixed))
                return preg_replace($pattern, $result, $mixed);
        }

        return $mixed;
    }

    static public function pluralize($mixed) {

        self::scalarOrNullOrFail($mixed);

        $plurals = array(
            '/(quiz)$/i' => '$1zes',
            '/^(ox)$/i' => '$1en',
            '/([m|l])ouse$/i' => '$1ice',
            '/(matr|vert|ind)ix|ex$/i' => '$1ices',
            '/(x|ch|ss|sh)$/i' => '$1es',
            '/([^aeiouy]|qu)y$/i' => '$1ies',
            '/(hive)$/i' => '$1s',
            '/(?:([^f])fe|([lr])f)$/i' => '$1$2ves',
            '/(shea|lea|loa|thie)f$/i' => '$1ves',
            '/sis$/i' => 'ses',
            '/([ti])um$/i' => '$1a',
            '/(tomat|potat|ech|her|vet)o$/i' => '$1oes',
            '/(bu)s$/i' => '$1ses',
            '/(alias)$/i' => '$1es',
            '/(octop)us$/i' => '$1i',
            '/(ax|test)is$/i' => '$1es',
            '/(us)$/i' => '$1es',
            '/s$/i' => 's',
            '/$/' => 's',
        );

        $irregulars = array(
            'move' => 'moves',
            'foot' => 'feet',
            'goose' => 'geese',
            'sex' => 'sexes',
            'child' => 'children',
            'man' => 'men',
            'tooth' => 'teeth',
            'person' => 'people',
        );

        $uncountables = array(
            'sheep',
            'fish',
            'deer',
            'series',
            'species',
            'money',
            'rice',
            'information',
            'equipment',
        );

        if (in_array(strtolower($mixed), $uncountables))
            return $mixed;

        foreach ($irregulars as $pattern => $result) {

            $pattern = '/' . $pattern . '$/i';

            if (preg_match($pattern, $mixed))
                return preg_replace($pattern, $result, $mixed);
        }

        foreach ($plurals as $pattern => $result) {

            if (preg_match($pattern, $mixed))
                return preg_replace($pattern, $result, $mixed);
        }

        return $mixed;
    }

    static public function resolve($verb, $controllerAction, array $params = [], array $queryParams = []) {

        foreach (Controller::getRoutes() as $route) 
            if(Controller::routeTargets($route, $verb, $controllerAction))
                return Controller::resolveRoute($route, $params, $queryParams);

        throw new \Exception('unable to resolve route url for "'.$verb.' : ' . $controllerAction . '"');

    }

    static public function arrayOrFail($var) {

        if (!is_array($var))
            throw new \Exception('var should be an array, "' . gettype($var) . '" given');

        return $var;
    }

    static public function stringOrFail($var) {

        if (!is_string($var))
            throw new \Exception('var should be a string, "' . gettype($var) . '" given');

        return $var;
    }

    static public function scalarOrNullOrFail($var) {

        if (!is_scalar($var) && !is_null($var))
            throw new \Exception('var should be a scalar or null, "' . gettype($var) . '" given');

        return $var;
    }

    static public function urlRewrite($value) {
  
        $delimiter = '-';
      
        $value = preg_replace("/&(.)(grave|acute|cedil|circ|ring|tilde|uml|[0-9]{3});/", '\\1', strtolower(htmlentities($value, ENT_QUOTES, 'UTF-8')));
        $value = preg_replace("/([^a-z0-9]+)/", $delimiter, html_entity_decode($value));
    
        while(is_numeric(strpos($value, $delimiter.$delimiter)))
          $value = str_replace($delimiter.$delimiter, $delimiter, $value);
    
        $value = trim($value, $delimiter);
    
        return $value;
      
      }

}

// Do not clause PHP tags unless it is really necessary

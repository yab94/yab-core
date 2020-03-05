<?php

namespace Yab\Core;

use Yab\Core\Statement;
use Yab\Core\Tool;
use Yab\Core\View;
use Yab\Core\Form;

class Filter extends Form {

    protected $request = null;
    protected $response = null;
    protected $prefix = null;
    
    public function __construct(Request $request, Response $response, $prefix = '') {

        $this->request = $request;
        $this->response = $response;

        $this->prefix = (string) $prefix;

        parent::__construct(['method' => 'GET']);

    }

    public function getResetUrl() {

        $params = [];

        foreach($this->fields as $field) 
            $params[$field->name] = '';

        $request = clone $this->request;

        $request->addQueryParams($params);

        return $request->getUri();

    }

    public function process() {

        if(!$this->prefix)
            return parent::process($this->request);
        
        $prefix = $this->prefix;

        $cookies = $this->request->getCookies();

        $keys = array_keys($cookies);

        $keys = array_map(function($key) use($prefix) {
            return preg_replace('#^'.preg_quote($prefix, '#').'#', '', $key);
        }, $keys);

        $cookies = array_combine($keys, $cookies);
   
        return parent::process($this->request, $cookies);

    }

    public function filter(Statement $statement) {

        $this->process();

        array_map(function($field) use($statement) {

            if(in_array($field->type, ['button', 'submit'])) 
                return;

            if($this->prefix) 
                $this->response->setCookie($this->prefix.$field->name, $field->value);

            if(!$field->value) 
                return;
 
            if(isset($field->callback)) {
                $callback = $field->callback;
                return $callback($statement, $field->value);
            }

            if(!isset($field->filter)) 
                $field->filter = $field->name;

            if(!is_array($field->filter))
                return $statement->whereEq($field->filter, $field->value);

            foreach($field->filter as $column)
                $statement->orWhereLike($column, $field->value);

        }, $this->getFields());
        
    }

}
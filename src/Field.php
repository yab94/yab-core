<?php

namespace Yab\Core;

class Field extends View {

    //protected $form = null;
    
    protected $validable = true;
    protected $valuable = true;

    protected $rules = [];

    protected $onRequest = null;
    protected $onView = null;

    protected $errors = [];
   
    public function __construct(/*Form $form, */string $name, string $template, array $attributes = [], array $rules = []) {

        //$this->form = $form;

        $this->set('name', $name);
        $this->set('value', '');

        $attributes['id'] = $attributes['id'] ?? $name;

        $this->setTemplate($template);

        foreach($attributes as $name => $value)
            $this->set($name, $value);

        foreach($rules as $rule)
            $this->addRule($rule);

    }

    public function onRender(): View {

        $this->set('errors', $this->errors);

        $onView = $this->onView ?? function($value) { return $value; };

        $this->set('value', $onView($this->get('value')));
        
        return $this;

    }

    public function onRequest(\Closure $onRequest = null) {

        $this->onRequest = $onRequest;

        return $this;

    }

    public function onView(\Closure $onView = null) {

        $this->onView = $onView;

        return $this;

    }

    public function setValidable(bool $validable) {

        $this->validable = (bool) $validable;

        return $this;

    }

    public function isValidable() {

        return $this->validable;

    }

    public function setValuable(bool $valuable) {

        $this->valuable = (bool) $valuable;

        return $this;

    }

    public function isValuable() {

        return $this->valuable;

    }
/*
    public function getForm() {

        return $this->form;

    }*/

    public function getErrors() {

        return $this->errors;

    }
/*
    public function getRequestValue(Request $request) {

        $name = $this->get('name');

        if(in_array($this->get('type'), ['file'])) 
            return $request->getFileParam($name);

        if(strtoupper($this->form->get('method') == 'GET'))
            return $request->getQueryParam($name);

        return $request->getBodyParam($name);

    }*/

    public function process($requestValue) {

        $validation = true;

        try {

            $value = $requestValue;

            $onRequest = $this->onRequest ?? function($value) { return $value; };

            $value = $onRequest($value);

        } catch(\Exception $e) {

            if(!in_array($this->get('type'), ['checkbox']))
                return !(bool) $this->validable;

        }

        $this->set('value', $value);
        
        $this->errors = [];

        foreach($this->rules as $index => $closure) {

            try {

                $closure($this->get('value'));
                
            } catch(\Exception $e) {

                $this->errors[] = $e->getMessage();

                $validation = false;

            }
            
        }

        return $validation;
    }

    final public function addMatchingRule($regexp, $error) {

        return $this->addRule(function($value) use ($regexp, $error) { if(!preg_match($regexp, $value)) throw new \Exception($error); });
    
	}

    final public function addOptionsRule(array $options, $error) {

        return $this->addRule(function($value) use ($options, $error) { if(!in_array($value, $options)) throw new \Exception($error); });
    
	}

    final public function addRequiredRule($error) {

        return $this->addRule(function($value) use ($error) { if($value == '') throw new \Exception($error); });
    
	}

    final public function removeRules() {

        $this->rules = [];

        return $this;
    }

    final public function addRule(\Closure $closure) {

        $this->rules[] = $closure;

        return $this;
    }

}

// Do not clause PHP tags unless it is really necessary
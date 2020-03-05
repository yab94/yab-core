<?php

namespace Yab\Core;

class Form extends View {

    protected $template = 'templates/form.php';

    protected $vars = [
        'method' => 'POST',
        'action' => '',
    ];

    protected $fields = [];

    protected function onRender(): View {

        $this->set('fields', array_map(function($fields) { return $fields->onRender(); }, $this->fields));
        $this->set('errors', $this->getErrors());
        
        return $this;

    }

    public function getErrors() {

        return array_map(function($field) { return $field->getErrors(); }, $this->fields);

    }
    
    public function getFields() {

        return $this->fields;

    }

    public function getField($name) {
        
        if(!isset($this->fields[$name]))
            throw new \Exception('unknown form field "'.$name.'"');

        return $this->fields[$name];
    }

    public function getValues() {

        return array_map(function($field) { 
            
            return $field->get('value'); 

        }, array_filter($this->fields, function($field) { 

            return $field->isValuable(); 
            
        }));

    }

    public function getValue($name) {

        return $this->getField($name)->get('value');

    }

    public function getRequestValue(Request $request, Field $field) {

        if(in_array($field->type, ['file'])) 
            return $request->getFileParam($field->name);

        if(strtoupper($this->method == 'GET'))
            return $request->getQueryParam($field->name);

        return $request->getBodyParam($field->name);

    }

    public function process(Request $request, array $defaultValues = []) {

        if($this->method != $request->getVerb())
            return false;

        $process = true;

        foreach ($this->fields as $field) {
                
            if(!$field->isValidable())
                continue;

            try {

                $value = $this->getRequestValue($request, $field);
    
            } catch(\Exception $e) {

                $value = $defaultValues[$field->name] ?? null;

            }

            if (!$field->process($value))
                $process = false;

        }

        return $process;
    }

    public function addField(Field $field) {

        $field->type = $field->type ?? 'field';

        $this->fields[$field->name] = $field;

        return $this;
    }

    public function addText($name, array $attributes = [], $template = 'templates/form/text.php') {

        $attributes['type'] = 'text';

        $field = new Field($name, $template, $attributes);

        return $this->addField($field);

    }

    public function addFile($name, array $attributes = [], $template = 'templates/form/file.php') {

        $this->set('enctype', 'multipart/form-data');

        $attributes['type'] = 'file';

        $field = new Field($name, $template, $attributes);

        $field->addRule(function($value) use ($attributes) {
	
            $destination = $attributes['destination'] ?? '';
            $mimes = $attributes['allowedMimeTypes'] ?? '';
            $size = $attributes['maxSize'] ?? '';

            if(!is_dir($destination) || !is_writable($destination))
                throw new \Exception('invalid destination folder for upload');

            if(preg_match('#[\x00-\x1F\x7F-\x9F/\\\\]#', $value['name']))
                throw new \Exception('bad file name invalid character');

            if(!is_file($value['tmp_name']) || !is_uploaded_file($value['tmp_name']))
                throw new \Exception('can not upload file, no tmp file found');

            if(count($mimes) && !in_array($value['type'], $mimes))
                throw new \Exception('can not upload file, invalid MIME type');

            if($size && $size < $value['size'])
                throw new \Exception('can not upload file, file too big');

            $destination = $destination.DIRECTORY_SEPARATOR.$value['name'];

            if(!move_uploaded_file($value['tmp_name'], $destination))
                throw new \Exception('failed to upload file');
            
        });

        return $this->addField($field);
		
    }

    public function addTextarea($name, array $attributes = [], $template = 'templates/form/textarea.php') {

        $attributes['type'] = 'textarea';

        $field = new Field($name, $template, $attributes);

        return $this->addField($field);
        
    }

    public function addSelect($name, array $attributes = [], $template = 'templates/form/select.php') {

        $attributes['type'] = 'select';

        $field = new Field($name, $template, $attributes);

        return $this->addField($field);
        
    }

    public function addRadio($name, array $attributes = [], $template = 'templates/form/radio.php') {

        $attributes['type'] = 'radio';

        $field = new Field($name, $template, $attributes);

        return $this->addField($field);
        
    }

    public function addPassword($name, array $attributes = [], $template = 'templates/form/password.php') {

        $attributes['type'] = 'password';

        $field = new Field($name, $template, $attributes);

        return $this->addField($field);

    }

    public function addSubmit($name, array $attributes = [], $template = 'templates/form/submit.php') {

        $attributes['type'] = 'submit';
        $attributes['value'] = $name;

        $field = new Field($name, $template, $attributes);

        $field->setValidable(false);
        $field->setValuable(false);

        return $this->addField($field);

    }

    public function addButton($url, array $attributes = [], $template = 'templates/form/button.php') {

        $attributes['type'] = 'button';
        $attributes['url'] = $url;
        $attributes['name'] = $attributes['name'] ?? $url;
        $attributes['value'] = $attributes['value'] ?? $attributes['name'];
        $attributes['label'] = $attributes['label'] ?? $attributes['value'];

        $field = new Field($attributes['name'], $template, $attributes);

        $field->setValidable(false);
        $field->setValuable(false);

        return $this->addField($field);

    }

    public function addHidden($name, array $attributes = [], $template = 'templates/form/hidden.php') {

        $attributes['type'] = 'hidden';

        $field = new Field($name, $template, $attributes);

        return $this->addField($field);

    }

    public function addCheckbox($name, array $attributes = [], $template = 'templates/form/checkbox.php') {
        
        $attributes['type'] = 'checkbox';

        $field = new Field($name, $template, $attributes);

        return $this->addField($field);

    }

    public function addRule($name, \Closure $closure) {

        return $this->getField($name)->addRule($closure);

    }

}

// Do not clause PHP tags unless it is really necessary
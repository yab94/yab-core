<?php

namespace Yab\Core;

use Yab\Core\Statement;
use Yab\Core\Tool;
use Yab\Core\View;
use Yab\Core\Form;

class Sorter {
	
    use Tool;

    protected $request = null;
    protected $response = null;
    protected $prefix = null;

    protected $sortVar = 's';
    
    public function __construct(Request $request, Response $response, $prefix = '') {

        $this->request = $request;
        $this->response = $response;

        $this->prefix = (string) $prefix;

    }

    public function sort(Statement $statement) {
        
        if($this->prefix) 
            $this->response->setCookie($this->prefix.$this->sortVar, $this->getSort());

        if($this->getSort()) 
            $statement->orderBy($this->getSortField().' '.$this->getSortDir());

    }

    public function getResetUrl() {

        $request = clone $this->request;

        return $request->addQueryParam($this->prefix.$this->sortVar, '')->getUri();

    }

    public function getSortUrl($column) {

        $request = clone $this->request;

        return $request->addQueryParam($this->prefix.$this->sortVar, ($this->getSortDir() == 'ASC' ? '-' : '').$column)->getUri();

    }
    
    public function getSortField() {

        if(substr($this->getSort(), 0, 1) == '-') 
            return substr($this->getSort(), 1);
            
        return $this->getSort();

    }
    
    public function getSortDir() {

        if(substr($this->getSort(), 0, 1) == '-') 
            return 'DESC';
            
        return 'ASC';

    }
    
    public function getSort() {
        
        return (string) $this->getQueryStringOrCookie($this->sortVar);
        
    }
    
    protected function getQueryStringOrCookie($param, $default = '') {
        
        return (string) $this->request->getQueryParam($this->prefix.$param, $this->prefix ? $this->request->getCookie($this->prefix.$param, $default) : $default);
        
    }
    
    public function getSortLink($field, $label) : View {

        $view = new View();

        $view->setTemplate('templates/sorter-link.php');

        $view->url = $this->getSortUrl($field);
        $view->field = $field;
        $view->label = $label;
        $view->sort = $this->getSort();
        $view->sortDir = $this->getSortDir();
        $view->sortField = $this->getSortField();
        $view->label = $label;
        $view->resetUrl = $this->getResetUrl();

        return $view;

    }

}
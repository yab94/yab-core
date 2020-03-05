<?php

namespace Yab\Core;

use Yab\Core\Statement;
use Yab\Core\Tool;
use Yab\Core\View;
use Yab\Core\Form;

class Pager extends View {
	
    use Tool;

    protected $template = 'templates/pager.php';

    protected $request = null;
    protected $response = null;
    protected $prefix = null;
    
    protected $rowcount = null;

    protected $page = 1;
    protected $displayPages = 5;
    protected $defaultPerPage = 25;
    protected $sortVar = 's';
    protected $pageVar = 'p';
    protected $perPageVar = 'pp';
    
    public function __construct(Request $request, Response $response, $prefix = '') {

        $this->request = $request;
        $this->response = $response;

        $this->prefix = (string) $prefix;

    }

    public function paginate(Statement $statement) {

        $this->rowCount = $statement->count();

        if($this->prefix) {

            $this->response->setCookie($this->prefix.$this->pageVar, $this->getPage());
            $this->response->setCookie($this->prefix.$this->perPageVar, $this->getPerPage());

        }

        $statement->limit(($this->getPage() - 1) * $this->getPerPage(), $this->getPerPage());

    }

    public function onRender(): View {

        $table = $this;

        $this->set('rowcount', $this->rowcount);
        $this->set('sortVar', $this->sortVar);
        $this->set('pageVar', $this->pageVar);
        $this->set('perPageVar', $this->perPageVar);
            
        $this->set('displayPages', $this->displayPages);  
        $this->set('page', $this->getPage());
        $this->set('perPages', $this->getPerPages());
        $this->set('defaultPerPage', $this->defaultPerPage);
            
        $this->set('firstPage', $this->getFirstPage());
        $this->set('firstPageUrl', $this->getFirstPageUrl());
    
        $this->set('lastPage', $this->getLastPage());
        $this->set('lastPageUrl', $this->getLastPageUrl());
    
        $this->set('nextPage', $this->getNextPage());
        $this->set('nextPageUrl', $this->getNextPageUrl());
    
        $this->set('previousPage', $this->getPreviousPage());
        $this->set('previousPageUrl', $this->getPreviousPageUrl());
    
        $this->set('getPageUrl', function($page) use($table) { return $table->getPageUrl($page); });
        $this->set('getPerPageUrl', function($page) use($table) { return $table->getPerPageUrl($page); });

        return $this;

    }

    protected function getPerPages() {

        $perPages = [];

        $perPage = 0;

        while($perPage < $this->rowCount) {
            $perPages[] = $perPage ? $perPage : 1;
            $perPage += (10 * count($perPages));
        }

        $perPages[] = $this->rowCount;

        return $perPages;

    }

    public function getPerPageUrl($perPage) {

        $request = clone $this->request;

        return $request->addQueryParam($this->prefix.$this->perPageVar, $perPage)->getUri();

    }
    
    public function getFirstPageUrl() {

        return $this->getPageUrl($this->getFirstPage());

    }
    
    public function getLastPageUrl() {

        return $this->getPageUrl($this->getLastPage());

    }
    
    public function getNextPageUrl() {
        
        return $this->getPageUrl($this->getNextPage());
  
    }
    
    public function getPreviousPageUrl() {
        
        return $this->getPageUrl($this->getPreviousPage());
        
    }
    
    public function getPageUrl($page) {

        $request = clone $this->request;

        return $request->addQueryParam($this->prefix.$this->pageVar, $page)->getUri();

    }
    
    protected function getQueryStringOrCookie($param, $default = '') {
        
        return (string) $this->request->getQueryParam($this->prefix.$param, $this->prefix ? $this->request->getCookie($this->prefix.$param, $default) : $default);
        
    }
    
    public function getNextPage() {
        
        return  min($this->getPage() + 1, $this->getLastPage());
  
    }
    
    public function getPreviousPage() {
        
        return max($this->getPage() - 1, 1);
        
    }
    
    public function getPage() {
 
        return min($this->getLastPage(), max(1, (int) $this->getQueryStringOrCookie($this->pageVar, $this->getFirstPage())));

    }
    
    public function getFirstPage() {

        return 1;

    }
        
    public function getLastPage() {

        return max(1, ceil($this->getRowCount() / $this->getPerPage()));
        
    }
    
    public function getPerPage() {
        
        return max(1, (int) $this->getQueryStringOrCookie($this->perPageVar, $this->defaultPerPage));
        
    }
    
    public function getRowCount() {
        
        if($this->rowCount === null)
            throw new \Exception('can not use getRowCount when table is not applied on statement, use apply() method before');
        
        return $this->rowCount;
        
    }
    
}
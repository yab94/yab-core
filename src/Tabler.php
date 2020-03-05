<?php

namespace Yab\Core;

use Yab\Core\Statement;
use Yab\Core\Tool;
use Yab\Core\View;

class Tabler extends View {
	
    use Tool;

    protected $template = 'templates/tabler.php';
  
    protected $datas = [];
    protected $headers = [];

    protected $callback = null;

    public function __construct($datas) {
 
        $this->datas = $datas;

        $this->callback = function(array $datas) {
            return $datas;
        };

    }

    public function setCallback(\Closure $closure) {

        $this->callback = $closure;

        if($this->datas instanceof Statement) {

            $this->datas->fetchValue($this->callback);

        } else {

            $this->datas = array_map($this->callback, $this->datas);

        }

        return $this;
        
    }

    public function setHeaders(array $headers) {

        $this->headers = $headers;

        return $this;

    }

    public function getLink($label, $controllerAction, array $params = [], $method = 'GET') {

        $href = Tool::resolve($method, $controllerAction, $params);

        return new View([
            'href' => $href, 
            'label' => $label,
        ], 'templates/tabler-link.php');

    }

    protected function onRender(): View {

        $this->set('datas', $this->datas);
        $this->set('headers', $this->headers);
        $this->set('callback', $this->callback);
        
        return $this;

    }

}
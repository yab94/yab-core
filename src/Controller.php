<?php

namespace Yab\Core;

abstract class Controller {

    use Tool;

    protected $views = [];
    
    final public function __construct(Request $request, Response $response) {

        $this->request = $request;
        $this->response = $response;

        if($this->getFlashMessages()) 
            $this->clearFlashMessages();
       
    }

    final public function forward($controllerAction, array $params = []) {
		
        return $this->redirect($this->resolve('GET', $controllerAction, $params));

    }

    final public function redirect($url) {
		
        $this->response->redirect($url);
        Response::flush($this->response);
        exit();

    }

    public function getFlashMessages() {

        $cookieJson =  $this->request->getCookie(Config::getConfig()->getParam('application.flashMessageVar', 'FLASH_MESSAGE_VAR'), '{}');
    
        $flashMessages = json_decode($cookieJson, true);

        return $flashMessages;
    }

    public function clearFlashMessages() {

        return $this->response->setCookie(Config::getConfig()->getParam('application.flashMessageVar', 'FLASH_MESSAGE_VAR'), '{}');
    
    }

    public function addFlashMessage($message, $type = 'success') {

        $cookieJson = $this->response->getCookie(Config::getConfig()->getParam('application.flashMessageVar', 'FLASH_MESSAGE_VAR'), '{}');

        $cookie = json_decode($cookieJson, true);
        $cookie[$type] = $cookie[$type] ?? [];
        $cookie[$type][] = $message;
        $cookieJson = json_encode($cookie);

        return $this->response->setCookie(Config::getConfig()->getParam('application.flashMessageVar', 'FLASH_MESSAGE_VAR'), $cookieJson);
    
    }

    final public function createView(string $template, array $vars = []) {

        return $this->fillViewContext(new View($vars, $template));

    }

    final protected function fillViewContext(View $view): View {

        $view->request = $this->request;
        $view->response = $this->response;
        $view->routeClosure = $this->request->getUri();
        $view->flashMessages = $this->getFlashMessages();
        $view->baseUrl = Config::getConfig()->getParam('application.baseUrl', '');

        return $view;

    }

    final protected function render($template = null, array $vars = [], $withLayout = true) {

        $withLayout = $this->request->isAjax() ? false : $withLayout;

        $view = ($template instanceof View) ? $this->fillViewContext($template) : $this->createView($template, $vars);
 
		if(!$withLayout) {

            $this->response->appendBody($view->getRender());

            return $this;
            
        }

		$layout = $this->createView(
			Config::getConfig()->getParam('application.layout'), 
			array('view' => $view) + $vars
		);
 
		$this->response->appendBody($layout->getRender());

        return $this;
    }

    static public function getRoutes() {

        foreach(Config::getConfig()->getParam('http') as $verb => $routes) 
            foreach($routes as $route => $closure) 
                yield array('verb' => $verb, 'route' => $route, 'closure' => $closure);

    }

    static public function routeTargets($route, $verb, $controllerAction) {
 
        if(strtolower($route['verb']) != strtolower($verb))
            return false;

        if(!is_string($route['closure']))
            return false;

        $routeControllerAction = preg_split('#\s+#', trim($route['closure']));
        $routeControllerAction = array_shift($routeControllerAction);

        if($routeControllerAction != $controllerAction)
            return false;
    
        return true;

    }

    static public function resolveRoute(array $route, array $params = [], array $queryParams = []) {

        $route = $route['route'];

        foreach ($params as $key => $value)
            $route = str_replace(':' . $key, $value, $route);

        $request = new Request('GET', Config::getConfig()->getParam('application.baseUrl').$route);

        if(count($queryParams)) 
            $request->setQueryParams($queryParams);
      
        return $request->getUri();
        
    }

    static public function routeMatch(array $route, Request $request, array &$params = []) {

        if(strtolower($route['verb']) != strtolower($request->getVerb()))
            return false;

        $regexp = '#^'.Config::getConfig()->getParam('application.baseUrl').$route['route'].'$#';

        preg_match_all('#:([a-zA-Z0-9_]+)#', $regexp, $matchKeys);

        $matchKeys = $matchKeys[1];

        foreach($matchKeys as $var)
            $regexp = str_replace(':'.$var, '([^/]+)', $regexp);

        Logger::getLogger()->debug('route try "'.$regexp.' =~ '.$request->getPath().'"');

        if(!preg_match($regexp, $request->getPath(), $matchValues))
            return false;

        array_shift($matchValues);

		foreach(array_combine($matchKeys, $matchValues) as $key => $value)
			$params[$key] = $value;
 
        return true;

    }

    static public function route(Request $request, Response $response) {

        foreach(self::getRoutes() as $route) {

            $params = [];
            
            if(!self::routeMatch($route, $request, $params))
                continue;
    
            $response->setCode(200);
    
            Logger::getLogger()->debug('route match "'.$route['verb'].':'.$route['route'].'"');

            $closure = self::routeClosure($route, $request, $response, $params);
    
            $closure();
    
            return true;

        }

        $response->setCode(404);

        return false;

    }

    static protected function routeClosure(array $route, Request $request, Response $response, array $params) {

        if($route['closure'] instanceof \Closure)
            return $route['closure'];

        $parts = preg_split('#\s+#', $route['closure']);
        $controllerAction = array_shift($parts);
        $controllerAction = explode('.', $controllerAction);
        $controller = array_shift($controllerAction);
        $action = array_shift($controllerAction);
        $params = array_intersect_key($params, array_flip($parts));

        if (!class_exists($controller)) 
            throw new \Exception('unexisting class "' . $controller . '"');
        
        if (!is_subclass_of($controller, 'Yab\Core\Controller', true)) 
            throw new \Exception('bad controller class "' . $controller . '"');
        
        return function() use($controller, $action, $params, $request, $response) {

            $controller = new $controller($request, $response);

            $params = array_values($params);

            $controller->before();
            $controller->$action(...$params);
            $controller->after();

        };

    }

    protected function before() {}

    protected function after() {}
 
}
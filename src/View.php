<?php
 
namespace Yab\Core;

require_once 'Tool.php';

use Yab\Core\Tool;

class View { 

    use Tool;
    
    static protected $blocks = [];
    
    protected $buffer = null;
    
    protected $vars = [];

    protected $openedBlock = null;

    protected $template = null;

    protected $fallbackTemplates = [];

    public function __construct(array $vars = [], $template = null) {

        foreach($vars as $name => $value)
            $this->vars[$name] = $value;
        
        if($template)
            $this->setTemplate($template);

    }

    protected function onRender(): View { return $this; }
    
    public function __toString() {

        return $this->getRender();

    }

    final public function __set($name, $value) {
        
        return $this->set($name, $value);
        
    }
    
    final public function __get($name) {
        
        return $this->get($name);
        
    }
    
    final public function __isset($name) {
        
        return $this->has($name);
        
    }
    
    final public function __unset($name) {
        
        return $this->unset($name);
        
    }
    
    final public function get($name) {
        
        if(!$this->has($name)) 
            throw new \Exception('unable to find var "'.$name.'"');

        return $this->vars[$name];
        
    }
    
    final public function set($name, $value): View {
        
        $this->vars[$name] = $value;
        
        return $this;
        
    }
    
    final public function unset($name): View {
        
        if(!$this->has($name))
            throw new \Exception('unable to find var "'.$name.'"');
        
        unset($this->vars[$name]);

        return $this;
        
    }
    
    final public function has($name) {
        
        return array_key_exists($name, $this->vars);
        
    }
    
    final public function setTemplate($template) {
        
        if($this->template)
            $this->fallbackTemplates[] = $this->template;

        $this->template = (string) $template;
        
        return $this;
        
    }
    
    final public function getTemplate() {
        
        return $this->template;
        
    }

    final protected function partial($template, array $vars = []) {

		$view = new View($this->vars, $template);

        foreach($vars as $name => $value)
            $view->set($name, $value);

		$view->render();
        
        return $view;
        
    }

    final protected function openBlock($block) {

		ob_start();
        
        $this->openedBlock = (string) $block;
        
        return $this;

    }

    final protected function closeBlock() {

		$block = ob_get_clean();
        
        if(!isset(self::$blocks[$this->openedBlock]))
            self::$blocks[$this->openedBlock] = '';
        
        self::$blocks[$this->openedBlock] .= (string) $block;
        
        $this->openedBlock = null;
        
        return $this;

    }

    final public function getBlockRender($block, $defaultBody = '') {

        if($this->buffer === null) {
            
            Logger::getLogger()->debug('PRE RENDERING FOR BLOCK "' . $block.'"');
            
            $this->getRender();
            
        }

		return isset(self::$blocks[$block]) ? self::$blocks[$block] : $defaultBody;

    }

    final public function getRender($template = null) {

        if($this->buffer !== null)
            return $this->buffer;
        
		ob_start();
		
		try {
			
			$this->render($template);
			
		} catch(\Exception $e) {
			
            ob_end_clean();

			throw $e;
			
		}
		
        $this->buffer = (string) ob_get_clean();
        
        return $this->buffer;

    }

    final public function render($template = null, array $vars = []) {

        if($this->buffer !== null) {
            
            echo $this->buffer;
            
            return $this;
            
        }

        $template = $template ?? $this->template;

		if(!$template)
			return;

        $filePath = stream_resolve_include_path($template);

        if(!$filePath) {

            $fallbackTemplate = array_pop($this->fallbackTemplates);

            if(!$fallbackTemplate)
                throw new \Exception('can not find template "'.$template.'"');

            Logger::getLogger()->debug('FALLBACK VIEW "' . $template.'" => "'.$fallbackTemplate.'"');
            
            return $this->render($fallbackTemplate);

        }
        
        $this->onRender();

        extract($this->vars);
        extract($vars);

		include $filePath;

        return $this;
            
    }
    
    final protected function renderBlock($block, $defaultBody = '') {
        
        echo $this->getBlockRender($block, $defaultBody);
        
    }
 
}

// Do not clause PHP tags unless it is really necessary
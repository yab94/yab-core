<?php

namespace Yab\Test\Unit;

use Yab\Test\TestCase;
use Yab\Core\View;

class ViewTest extends TestCase {
    
    public function testVars() {
        
        $vars = array(
            'var1' => 1,
            'var2' => 2,
            'var3' => 3,
        );
        
        $view = new View($vars);

        $view->var4 = 4;
        
        foreach($vars as $var => $value) {
            
            $this->assertTrue(isset($view->$var));
            
            $this->assertEquals($view->$var, $value);
            
            $this->assertEquals($view->get($var), $value);
            
        }
            
        $this->assertEquals($view->get('var4'), 4);

    }
   
}
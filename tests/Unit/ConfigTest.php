<?php

namespace Yab\Test\Unit;

use Yab\Test\TestCase;
use Yab\Core\Config;
	
class ConfigTest extends TestCase {
	
	public function testFusion() {
		
		$configA = new Config(array('paramA' => 'A'));
		$configB = new Config(array('paramB' => 'B'));
		
		$configA->fusion($configB);
		
		$this->assertEquals($configA->getParam('paramB'), $configB->getParam('paramB'));
		
	}
	
	public function testDigConfigDotParams() {
		
		$params = array(
			'paramA.subParamA.subSubParamA' => 'A',
			'paramB' => 'B',
			'paramC' => 'C',
		);
		
		$diggedParams = array(
			'paramA' => array('subParamA' => array('subSubParamA' => 'A')),
			'paramB' => 'B',
			'paramC' => 'C',
		);
		
		$config = new Config($params);
		
		$getParams = $config->getParams();
		
		$this->assertEquals($getParams, $diggedParams);
		
	}
	
	public function testGetParams() {
		
		$params = array(
			'paramA' => 'A',
			'paramB' => 'B',
			'paramC' => 'C',
		);
		
		$config = new Config($params);
		
		$getParams = $config->getParams();
		
		$this->assertEquals($getParams, $params);
		
	}
	
	public function testGetParam() {
		
		$params = array(
			'paramA' => 'A',
			'paramB' => 'B',
			'paramC' => 'C',
		);
		
		$config = new Config($params);
		
		foreach($params as $param => $value) 
			$this->assertEquals($config->getParam($param), $value);
		
	}
	
}
<?php

namespace Yab\Test\Unit;

use Yab\Test\TestCase;
use Yab\Core\Form;
use Yab\Core\View;
use Yab\Core\Request;
use Yab\Core\Logger;

class FormTest extends TestCase {
	
	public function testGetValues() {
		
		$values = array(
			'textA' => 'valueA',
			'textB' => 'valueB',
			'textC' => 'valueC',
		);
		
		$request = new Request('POST', '/');
		$request->setBodyParams($values);
		
		$form = new Form();
		$form->set('method', 'post');
		$form->addText('textA');
		$form->addText('textB');
		$form->addText('textC');
		
		$process = $form->process($request);

		$this->assertEquals($process, true);

		$formValues = $form->getValues();

		$this->assertEquals($values, $formValues);
		
	}
	
	public function testGetValue() {
		
		$values = array(
			'textA' => 'valueA',
		);
		
		$request = new Request('POST', '/');
		$request->setBodyParams($values);
		
		$form = new Form();
		$form->set('method', 'post');
		$form->addText('textA');

		$form->process($request);

		$this->assertEquals($form->getValue('textA'), $values['textA']);
		
	}
	
	public function testGetView() {
		
		$form = new Form();
		$form->set('method', 'post');
		$form->addText('textA');

		foreach($form->getFields() as $field)
			$this->assertTrue($field instanceof View);
		
	}
	
	public function testProcess() {
		
		$values = array(
			'textA' => 'valueA',
			'textB' => 'valueB',
			'textC' => 'valueC',
		);
		
		$request = new Request('POST', '/');
		$request->setBodyParams($values);
		
		$form = new Form();
		$form->set('method', 'post');
		$form->addText('textA');
		$form->addText('textB');
		$form->addText('textC');
		
		$this->assertTrue($form->process($request));
		
		$values = array(
			'textA' => 'valueA',
			'textB' => 'valueB',
			'textC' => 'valueC',
		);
		
		$request = new Request('POST', '/');
		$request->setBodyParams($values);
		
		$form = new Form();
		$form->set('method', 'post');
		$form->addText('textA');
		$form->addText('textB');
		$form->addText('textC');
		$form->addText('textD');
		
		$this->assertFalse($form->process($request));
		
	}
	
}
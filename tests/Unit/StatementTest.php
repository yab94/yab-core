<?php

namespace Yab\Test\Unit;

use Yab\Test\TestCase;
use Yab\Core\Database;

class StatementTest extends TestCase {
	
	protected $useDatabase = true;
	
	public function testExecute() {
		
		$stmt = Database::getDatabase()->statement('SHOW DATABASES;');
		
		$this->assertFalse($stmt->isExecuted());
        
		$stmt->execute();
		
		$this->assertTrue($stmt->isExecuted());
		
	}
	
	public function testReset() {
		
		$stmt = Database::getDatabase()->statement('SHOW DATABASES;');
		
		$this->assertFalse($stmt->isExecuted());
        
		$stmt->execute();
		
		$this->assertTrue($stmt->isExecuted());
        
		$stmt->reset();
		
		$this->assertFalse($stmt->isExecuted());
		
	}
	
}
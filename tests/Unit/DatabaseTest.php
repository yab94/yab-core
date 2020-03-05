<?php

namespace Yab\Test\Unit;

use Yab\Test\TestCase;
use Yab\Core\Database;

class DatabaseTest extends TestCase {
	
	protected $useDatabase = true;
	
	public function testConnexion() {
		
		$stmt = Database::getDatabase()->statement('SHOW DATABASES;');
		
		$stmt->execute();
		
		$records = $stmt->toArray();
		
		$this->assertTrue(0 < count($records));
		
	}
	
	public function testDatabase() {
		
		try {
			
			Database::getDatabase()->statement('USE '.$this->database)->execute();
			
			$this->assertTrue(true);
			
		} catch(\Exception $e) {
			
			$this->assertTrue(false);
			
		}
		
	}
	
}
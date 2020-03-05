<?php

namespace Yab\Test;

use Yab\Core\Database;
use Yab\Core\UnitTest;

class TestCase extends UnitTest {

	protected $useDatabase = false;
	protected $database = 'testing';
	
	protected function beforeAll() {
		
		if(!$this->useDatabase)
			return;
		
		$db = Database::getDatabase();
		
		$db->statement('DROP DATABASE IF EXISTS '.$this->database)->execute();
        
		$db->statement('CREATE DATABASE '.$this->database.';')->execute();
		$db->statement('USE '.$this->database.';')->execute();
		$db->statement('SET NAMES \'utf8\';')->execute();
        
		$db->statement('
            CREATE TABLE `customer` (
                `customerId` int(11) unsigned NOT NULL AUTO_INCREMENT,
                `first_name` varchar(255) DEFAULT NULL,
                `last_name` varchar(255) DEFAULT NULL,
                PRIMARY KEY (`customerId`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
		')->execute();

		$db->statement('
            CREATE TABLE `bill` (
                `billId` int(11) unsigned NOT NULL AUTO_INCREMENT,
				`foreignCustomerId` INT(11) unsigned NULL,
                `label` varchar(255) DEFAULT NULL,
                `amount` float DEFAULT NULL,
                `date` datetime DEFAULT NULL,
                PRIMARY KEY (`billId`),
				KEY(`foreignCustomerId`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
		')->execute();

		$db->statement('
            CREATE TABLE `group` (
                `groupId` int(11) unsigned NOT NULL AUTO_INCREMENT,
				`name` varchar(255) DEFAULT NULL,
                PRIMARY KEY (`groupId`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
		')->execute();

		$db->statement('
            CREATE TABLE `customer_group` (
                `throughCustomerId` INT(11) unsigned NOT NULL,
				`throughGroupId` INT(11) unsigned NOT NULL,
                PRIMARY KEY (`throughCustomerId`, `throughGroupId`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
		')->execute();
		
	}	
	
	protected function beforeEach() {
		
		if(!$this->useDatabase)
			return;
		
		$db = Database::getDatabase();

		$db->statement('TRUNCATE TABLE `customer`')->execute();
		$db->statement('TRUNCATE TABLE `bill`')->execute();
		$db->statement('TRUNCATE TABLE `group`')->execute();
		$db->statement('TRUNCATE TABLE `customer_group`')->execute();

	}
	
	protected function afterAll() {
		
		if(!$this->useDatabase)
			return;
		
		Database::getDatabase()->statement('DROP DATABASE IF EXISTS '.$this->database)->execute();
		
	}
	
}
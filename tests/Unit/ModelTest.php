<?php

namespace Yab\Test\Unit;

use Yab\Test\TestCase;
use Yab\Core\Logger;
use Yab\Core\Database;
use Yab\Core\Event;
use Yab\Core\Statement;
use Yab\Test\Unit\ModelTest\Customer;
use Yab\Test\Unit\ModelTest\Bill;
use Yab\Test\Unit\ModelTest\Group;

class ModelTest extends TestCase {
	
	protected $useDatabase = true;
	
	public function testSelectWithHasManyToMany() {
        
        $this->createXCustomers(10);
        $this->createXGroups(10);
        
        Foreach(Customer::select() as $customer)
            $customer->addGroups(Group::select());

        $queryCount = 0;

        Event::getEvent()->addListener(Database::EVENT_QUERY, function($params) use(&$queryCount) { $queryCount++; }, 'queryCount');

        foreach(Customer::with('groups') as $customer)
            $this->assertEquals(count($customer->groups), 10);

        foreach(Group::with('customers') as $group) 
            $this->assertEquals(count($group->customers), 10);

        Event::getEvent()->removeListener(Database::EVENT_QUERY, 'queryCount');

        $this->assertEquals($queryCount, 4);
 
	}

    public function testInstance() {
        
        $this->assertTrue(Customer::instance() instanceof Customer);
    
    }
    
    public function testDatabase() {
        
        $this->assertTrue(Customer::database() instanceof Database);
    
    }
    
    public function testTable() {
        
        $this->assertEquals(Customer::table(), 'customer');
    
    }
    
    public function testPrimary() {
        
        $this->assertEquals(Customer::primary(), array('customerId'));
    
    }
    
    public function testFind() {
		
		$customer = new Customer();
		$customer->set('first_name', 'Yann');
		$customer->set('last_name', 'BELLUZZI');
		$customer->forceInsert();
		
        $id = $customer->get('customerId');
        
		$this->assertTrue(is_numeric($id));
        
        $yann = Customer::find($id);
        
		$this->assertEquals($customer->last_name, $yann->last_name);
        
    }
    
    public function testFindBy() {
		
		$customer = new Customer();
		$customer->set('first_name', 'YannUnik1234');
		$customer->set('last_name', 'BELLUZZI');
		$customer->forceInsert();
        
        $yann = Customer::findBy(array('first_name' => 'YannUnik1234'));
        
		$this->assertEquals($customer->last_name, $yann->last_name);
        
    }
    
    public function testCheckUnicity() {
		
        $uniqueValue = 'YannUnik1234gdgfdhg4dsg';
        
		$customer = new Customer();
		$customer->set('first_name', $uniqueValue);
		$customer->set('last_name', $uniqueValue);
        
		$this->assertTrue($customer->checkUnicity('first_name', $uniqueValue));
        
		$customer->forceInsert();
        
		$customer = new Customer();
		$this->assertFalse($customer->checkUnicity('first_name', $uniqueValue));
        
    }

	public function testForceInsert() {

		$customer = new Customer();
		$customer->set('first_name', 'Yann');
		$customer->set('last_name', 'BELLUZZI');
		$customer->forceInsert();
		
        $id = $customer->get('customerId');
        
		$this->assertTrue(is_numeric($id));
        
        $yann = Customer::find($id);
        
		$this->assertEquals($customer->get('first_name'), $yann->get('first_name'));
		$this->assertEquals($customer->get('last_name'), $yann->get('last_name'));
        
		$customers = Customer::select();
		
		$this->assertEquals(1, count($customers));
		
	}
	
	public function testForceUpdate() {

		$customer = new Customer();
		$customer->set('first_name', 'Yann');
		$customer->set('last_name', 'BELLUZZI');
		$customer->forceInsert();
		
        $id = $customer->get('customerId');
        
        $yann = Customer::findBy(['customerId' => $id]);
        
        $yann->set('first_name', 'Yannou')->forceUpdate(); 
        
        $yannou = Customer::findBy(['customerId' => $id]);
        
		$this->assertEquals($yannou->get('first_name'), $yann->get('first_name'));
		
	}
	
	public function testExists() {
		
		$customer = new Customer();
        
		$customer->set('first_name', 'Yann');
		$customer->set('last_name', 'BELLUZZI');
		
        $this->assertFalse($customer->exists());
        
        $customer->save();
		
        $this->assertTrue($customer->exists());
		
	}
	
	public function testSave() {
		
		$customer = new Customer();
		$customer->set('first_name', 'Yann');
		$customer->set('last_name', 'BELLUZZI');
		$customer->save(); // insert
		
        $id = $customer->get('customerId');
        
        $yann = Customer::find($id);
        
        $yann->set('first_name', 'Yannou')->save(); // update
        
        $yannou = Customer::find($id);
        
		$this->assertEquals($yannou->get('first_name'), $yann->get('first_name'));
		
	}
	
	public function testSet() {
		
		$customer = new Customer();
        
        $value = 'yann';
        
		$customer->set('first_name', $value);
        
        $this->assertEquals($customer->getFirst_name(), $value);
        $this->assertEquals($customer->first_name, $value);
        $this->assertEquals($customer->get('first_name'), $value);

	}
	
	public function testGet() {
		
		$customer = new Customer();
        
        $value = 'yann';
        
		$customer->set('first_name', $value);
        
        $this->assertEquals($customer->getFirst_name(), $value);
        $this->assertEquals($customer->first_name, $value);
        $this->assertEquals($customer->get('first_name'), $value);
        
        try {
            
            $customer->get('first_NAme'); // case sensitive attributes
            
            $this->assertTrue(false);
            
        } catch(\Exception $e) {
            
            $this->assertTrue(true);
            
        }

	}
	
	public function testHasMany() {
		
		$customer = new Customer();
		$customer->set('first_name', 'Yann');
		$customer->set('last_name', 'BELLUZZI');
		$customer->save();
        
        $billA = new \Yab\Test\Unit\ModelTest\Bill();
        $billA->set('label', 'billA');
        $billA->set('amount', '199.99');
        $billA->set('date', date('Y-m-d H:i:s'));
        $billA->save();
        
        $billB = new \Yab\Test\Unit\ModelTest\Bill();
        $billB->set('label', 'billB');
        $billB->set('amount', '299.99');
        $billB->set('date', date('Y-m-d H:i:s'));
        $billB->save();
                
        // Event::getEvent()->addListener(Database::EVENT_QUERY, function($params) { echo $params['query'].PHP_EOL; }, 'sqlListener');

        $customer->bills->add($billA)->add($billB);
        
        $bills = $customer->bills;
        
        $this->assertEquals(2, count($bills));

        $customer->removeBills($billA);

        $bills = $customer->bills;
        
		$this->assertEquals(1, count($bills));

        $bill = $bills->next();
        
		$this->assertEquals($bill->amount, $billB->amount);
        
        $customer->getBills()->removeAll();
        
		$this->assertEquals(0, count($customer->bills));

	}
	
	public function testHasOne() {
		
		$customer = new Customer();
		$customer->set('first_name', 'Yann');
		$customer->set('last_name', 'BELLUZZI');
		$customer->save();
        
        $billA = new \Yab\Test\Unit\ModelTest\Bill();
        $billA->set('label', 'billA');
        $billA->set('amount', '199.99');
        $billA->set('date', date('Y-m-d H:i:s'));
        $billA->save();
        
        $billA->setCustomer($customer);
        
		$this->assertEquals($customer->id, $billA->customer->id);
		$this->assertEquals($customer->id, $billA->getCustomer()->id);
		$this->assertEquals($customer->id, $billA->get('customer')->id);
        
        $billA->removeCustomer();
        
        try {
            
            $billA->customer;
            
            $this->assertTrue(false);
            
        } catch(\Exception $e) {
            
            $this->assertTrue(true);
            
        }

	}
	
	public function testHasManyToMany() {

        $vars = array('A', 'B', 'C');
        
        foreach($vars as $var) {
		
            $group = Group::instance()->set('name', 'group_'.$var)->save();
            
            $customer = Customer::instance()->set('first_name', 'first_name_'.$var)->set('last_name', 'last_name_'.$var)->save();
                
        }

        foreach(Customer::select() as $customer) {
            
            foreach(Group::select() as $group) {
                
                $customer->addGroups($group);
                
            }
            
        }

        // Event::getEvent()->addListener(Database::EVENT_QUERY, function($params) { echo $params['query'].PHP_EOL; }, 'sqlListener');

        foreach(Customer::select() as $customer) {
            
            $this->assertEquals(count($customer->groups), count(Group::select()));
            
        }
        
        $customerA = Customer::findBy(array('first_name' => 'first_name_A'));
        $groupB = Group::findBy(array('name' => 'group_B'));
        
        $customerA->removeGroups($groupB);
         
        $this->assertEquals(count($customerA->groups), count(Group::select()) - 1);
        
        $customerA->removeAllGroups();
         
        $this->assertEquals(count($customerA->groups), 0);

	}
	
	public function testSelectWithHasOne() { 
        
        $vars = array('A', 'B', 'C');
        $vars2 = array(100, 2000, 30000); 

        foreach($vars as $var) {

            $customer = Customer::instance()->set('first_name', $var)->set('last_name', 'last_name_'.$var)->save();

            foreach($vars2 as $var2) { 
            
                $bill = Bill::instance()->set('label', $var)->set('amount', $var2);
                
                $customer->addBills($bill);

            }
                
        }
        
        $queryCount = 0;
        
        Event::getEvent()->addListener(Database::EVENT_QUERY, function($params) use(&$queryCount) { 
            $queryCount++; 
            // echo $params['query'].PHP_EOL;
        }, 'queryCount');

        foreach(Bill::with('customer') as $bill) 
            $this->assertEquals($bill->customer->first_name, $bill->label);
        
        Event::getEvent()->removeListener(Database::EVENT_QUERY, 'queryCount');

        $queryCount = 0;
        
        Event::getEvent()->addListener(Database::EVENT_QUERY, function($params) use(&$queryCount) { 
            $queryCount++; 
            // echo $params['query'].PHP_EOL;
        }, 'queryCount');

        foreach(Bill::with('customer')->whereEq('amount', 2000) as $bill) 
            $this->assertEquals($bill->customer->first_name, $bill->label);
        
        Event::getEvent()->removeListener(Database::EVENT_QUERY, 'queryCount');

        $this->assertEquals($queryCount, 2);
        
	}
	
	public function testSelectWithHasMany() {
        
        $vars = array('A', 'B', 'C');
        $vars2 = array(100, 2000, 30000); 
        
        foreach($vars as $var) {

            $customer = Customer::instance()->set('first_name', 'first_name_'.$var)->set('last_name', 'last_name_'.$var)->save();
        
            foreach($vars2 as $var2) {
                 
                $customer->addBills(Bill::instance()->set('label', 'bill_'.$var)->set('amount', $var2));
                
            }
                
        }
        
        $queryCount = 0;
        
        Event::getEvent()->addListener(Database::EVENT_QUERY, function($params) use(&$queryCount) { $queryCount++; }, 'queryCount');

        foreach(Customer::with('bills') as $customer) {

            $this->assertTrue(is_array($customer->bills));
            $this->assertEquals(count($customer->bills), count($vars2));

        }
        
        Event::getEvent()->removeListener(Database::EVENT_QUERY, 'queryCount');

        $this->assertEquals($queryCount, 2);

    }
    
    protected function createXCustomers($x) {

        for($i = 1; $i <= $x; $i++) {

            Customer::instance()
                ->set('first_name', 'first_name_'.$x)
                ->set('last_name', 'last_name_'.$x)
                ->save();
        
        }

        return $this;

    }
    
    protected function createXGroups($x) {

        for($i = 1; $i <= $x; $i++) {

            Group::instance()
                ->set('name', 'name_'.$x)
                ->save();
        
        }

        return $this;

    }
	
	public function testSelectWithNested() {
    
        Event::getEvent()->addListener(Database::EVENT_QUERY, function($params) use(&$queryCount) { $queryCount++; }, 'queryCount');
        
        $this->createXCustomers(10);
        $this->createXGroups(10);
        
        Foreach(Customer::select() as $customer) {
            $customer->addGroups(Group::select());
            $customer->addBills(Bill::instance()->setAmount(rand(100, 200)));
        }

        $queryCount = 0;

        foreach(Group::with(array('customers', 'customers.bills')) as $group) {

            $this->assertEquals(count($group->customers), 10);

            foreach($group->customers as $customer)
                $this->assertEquals(count($customer->bills), 1);

        }
        
        Event::getEvent()->removeListener(Database::EVENT_QUERY, 'queryCount');

        $this->assertEquals($queryCount, 3);

	}
    
}
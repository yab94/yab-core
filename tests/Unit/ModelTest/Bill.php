<?php

namespace Yab\Test\Unit\ModelTest;

use Yab\Core\Model;
use Yab\Test\Unit\ModelTest\Customer;

class Bill extends Model {
	
    static protected $table = 'bill';
    static protected $primary = array('billId');
    static protected $sequence = true;

    static protected $hasOne = array(
        'customer' => array(Customer::class, 'foreignCustomerId'),
    );
	
}
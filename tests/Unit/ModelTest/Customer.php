<?php

namespace Yab\Test\Unit\ModelTest;

use Yab\Core\Model;
use Yab\Test\Unit\ModelTest\Bill;

class Customer extends Model {
	
    static protected $table = 'customer';
    static protected $primary = ['customerId'];
    static protected $sequence = true;

    static protected $hasMany = [
        'bills' => [Bill::class, 'foreignCustomerId'],
    ];

    static protected $hasManyToMany = [
        'groups' => [Group::class, 'customer_group', 'throughGroupId', 'throughCustomerId'],
    ];

}
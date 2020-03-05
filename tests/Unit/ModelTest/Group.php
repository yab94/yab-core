<?php

namespace Yab\Test\Unit\ModelTest;

use Yab\Core\Model;
use Yab\Test\Unit\ModelTest\CustomerGroup;

class Group extends Model {
	
    static protected $table = 'group';
    static protected $primary = array('groupId');
    static protected $sequence = true;

    static protected $hasManyToMany = [
        'customers' => [Customer::class, 'customer_group', 'throughCustomerId', 'throughGroupId'],
    ];
	
}
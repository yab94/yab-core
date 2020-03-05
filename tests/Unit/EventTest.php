<?php

namespace Yab\Test\Unit;

use Yab\Test\TestCase;

use Yab\Core\Event;

class EventTest extends TestCase {
    
    const EVENT_NAME = 'fake_testing_event';

    public function beforeEach() {

        Event::getEvent()->removeAllListeners();

    }

    public function testFire() {
        
        $trigger = 0;
        $inc = 5;

        Event::getEvent()->addListener(self::EVENT_NAME, function(array $params) use(&$trigger) { $trigger += $params['inc']; });

        Event::getEvent()->fire(self::EVENT_NAME, ['inc' => $inc]);

        $this->assertEquals($trigger, $inc);

    }

    public function testAddListener() {
        
        Event::getEvent()->addListener(self::EVENT_NAME, function(array $params) use(&$trigger) { $trigger += $params['inc']; }, 'test_listener1');
        Event::getEvent()->addListener(self::EVENT_NAME, function(array $params) use(&$trigger) { $trigger += $params['inc']; }, 'test_listener2');
        Event::getEvent()->addListener(self::EVENT_NAME, function(array $params) use(&$trigger) { $trigger += $params['inc']; }, 'test_listener3');
        Event::getEvent()->addListener(self::EVENT_NAME, function(array $params) use(&$trigger) { $trigger += $params['inc']; }, 'test_listener3');

        $listeners = Event::getEvent()->getListeners(self::EVENT_NAME);

        $this->assertEquals(count($listeners), 3);

    }

    public function testRemoveListener() {
        
        Event::getEvent()->addListener(self::EVENT_NAME, function(array $params) use(&$trigger) { $trigger += $params['inc']; }, 'test_listener1');
        Event::getEvent()->addListener(self::EVENT_NAME, function(array $params) use(&$trigger) { $trigger += $params['inc']; }, 'test_listener2');
        Event::getEvent()->addListener(self::EVENT_NAME, function(array $params) use(&$trigger) { $trigger += $params['inc']; }, 'test_listener3');
        Event::getEvent()->addListener(self::EVENT_NAME, function(array $params) use(&$trigger) { $trigger += $params['inc']; }, 'test_listener3');

        $listeners = Event::getEvent()->getListeners(self::EVENT_NAME);

        $this->assertEquals(count($listeners), 3);

        Event::getEvent()->removeListener(self::EVENT_NAME, 'test_listener2');

        $listeners = Event::getEvent()->getListeners(self::EVENT_NAME);

        $this->assertEquals(count($listeners), 2);

    }

    public function testRemoveAllListener() {
        
        Event::getEvent()->addListener(self::EVENT_NAME, function(array $params) use(&$trigger) { $trigger += $params['inc']; }, 'test_listener1');
        Event::getEvent()->addListener(self::EVENT_NAME, function(array $params) use(&$trigger) { $trigger += $params['inc']; }, 'test_listener2');
        Event::getEvent()->addListener(self::EVENT_NAME, function(array $params) use(&$trigger) { $trigger += $params['inc']; }, 'test_listener3');
        Event::getEvent()->addListener(self::EVENT_NAME, function(array $params) use(&$trigger) { $trigger += $params['inc']; }, 'test_listener3');

        $listeners = Event::getEvent()->getListeners(self::EVENT_NAME);

        $this->assertEquals(count($listeners), 3);

        Event::getEvent()->removeAllListeners();

        $listeners = Event::getEvent()->getListeners(self::EVENT_NAME);

        $this->assertEquals(count($listeners), 0);

    }
    
}
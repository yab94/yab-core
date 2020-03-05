<?php

namespace Yab\Core;

class Event {

    use Singleton;

    protected $listeners = [];

    public function fire($event, array $params = []) {

        $event = strtoupper($event);

        Logger::getLogger()->debug('firing event "'.$event.'"');
        
        if(!isset($this->listeners[$event]))
            return $this;

        foreach($this->listeners[$event] as $listenerId => $closure) {

            Logger::getLogger()->debug('triggering listener "'.$event.':'.$listenerId.'"');

            $closure($params);

        }
            
        return $this;
        
    }

    public function getEventListeners() {

        return $this->listeners;

    }

    public function getListeners($event) {

        return $this->listeners[strtoupper($event)] ?? [];

    }

    public function getListener($event, $listenerId) {

        $event = strtoupper($event);

        if(!isset($this->listeners[$event]))
            throw new \Exception('unknown event "'.$event.'"');

        if(!isset($this->listeners[$event][$listenerId]))
            throw new \Exception('unknown event listener "'.$listenerId.'"');

        return $this->listeners[$event][$listenerId];

    }
    
    public function removeAllListeners() {

        $this->listeners = [];

        return $this;
        
    }
    
    public function removeListeners($event) {

        $this->listeners[strtoupper($event)] = [];

        return $this;
        
    }
    
    public function removeListener($event, $listenerId) {

        $event = strtoupper($event);

        if(!isset($this->listeners[$event]))
            throw new \Exception('unknown event "'.$event.'"');

        if(!isset($this->listeners[$event][$listenerId]))
            throw new \Exception('unknown event listener "'.$listenerId.'"');

        unset($this->listeners[$event][$listenerId]);

        return $this;
        
    }
    
    public function addListeners($event, array $listeners) {

        foreach($listeners as $listenerId => $closure)
            $this->addListener($event, $closure, $listenerId);

        return $this;
        
    }
    
    public function addListener($event, $closure, $listenerId = null) {

        $event = strtoupper($event);

        if(!isset($this->listeners[$event]))
            $this->listeners[$event] = [];

        if(!($closure instanceof \Closure))
            $closure = Tool::toClosure($closure);

        if($listenerId === null)
            $listenerId = $event.'_'.(count($this->listeners[$event]) + 1);

        $this->listeners[$event][$listenerId] = $closure;

        return $this;
        
    }

}
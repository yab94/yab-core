<?php
 
namespace Yab\Core;

use Yab\Core\Database;
use Yab\Core\Statement;
use Yab\Core\Tool;

abstract class Model implements \IteratorAggregate{

    use Tool;

    const ON_DELETE_CASCADE = 1;
    const ON_DELETE_BLOCK = 2;

    static protected $options = [
        self::ON_DELETE_CASCADE,
        self::ON_DELETE_BLOCK,
    ];

    static protected $database = 'default';
    static protected $primary = null;
    static protected $sequence = true;
    static protected $table = null;
	
    static protected $hasOne = [];
    static protected $hasMany = [];
    static protected $hasManyToMany = [];

    protected $loaded = false;
    protected $updates = [];
    protected $attributes = [];
	
    final public function __construct(array $datas = []) {

        $this->attributes = $datas;

        if($this->exists()) 
            $this->loaded = true;

        $this->init();

    }

    public function getIterator() {

        return new \ArrayIterator($this->attributes);

    }
    
    protected function init() {}
    
    final static public function instance(array $datas = []) {

        return new static($datas);
 
    }  
    
    final static public function new(array $datas = []) {

        return new static($datas);
 
    }  
    
    final static public function database() {

        return Database::getDatabase(static::$database);
 
    }
    
    final static public function table() {

        if(static::$table === null)
            throw new \InvalidArgumentException('static protected attribute "table" must be initialized in class "'.static::class.'"');

        return static::$table;
 
    }   
    
    final static public function primary() {

        if(static::$primary === null)
            throw new \InvalidArgumentException('static protected attribute "primary" must be initialized in class "'.static::class.'"');

        return self::arrayize(static::$primary);
 
    }
    
    final static public function find($value) {

        $statement = self::select();
        
        foreach(array_combine(self::primary(), Tool::arrayize($value)) as $primary => $value)
            $statement->whereEq($primary, $value);

        foreach($statement as $model)
            return $model; 
 
        throw new \OutOfBoundsException('can not find any model');
 
    }
    
    final static public function findBy(array $values) {

        $statement = self::select();
        
        foreach($values as $key => $value)
            $statement->whereEq($key, $value);

        foreach($statement as $model)
            return $model; 
 
        throw new \OutOfBoundsException('can not findBy any model');
 
    }
    
    final public function checkUnicity($field, $value) {

        $select = self::select('COUNT(1) as nb'); 

        if($this->exists()) {

            foreach(self::primary() as $primary)
                $select->whereNe($primary, $this->attributes[$primary]);
        }

        $nb = (int) $select->whereEq($field, $value)->fetchValue('nb')->execute()->next();

        return (bool) ($nb === 0);

    }

    final static public function all($select = null) {

		return self::select($select);

    }

    final static public function select($select = null) {

        $statement = self::database()->select(self::table())->fetchValue(static::class);
		
		if($select !== null)
			$statement->select($select);
		
		return $statement;

    }

    final static public function update() {

        return self::database()->update(self::table());

    }
    
    final static public function insert() {

        return self::database()->insert(self::table());

    }
    
    final static public function deleteFrom() {

        return self::database()->delete(self::table());
 
    }

    final public function get($key, $default = null) {

        if(array_key_exists($key, $this->attributes))
            return $this->attributes[$key];

        if(array_key_exists($key, static::$hasOne))
            return $this->getHasOne($key);

        if(array_key_exists($key, static::$hasMany))
            return $this->getHasMany($key);

        if(array_key_exists($key, static::$hasManyToMany))
            return $this->getHasManyToMany($key);

        if($default !== null)
            return $default;

		throw new \OutOfRangeException('call to undefined attribute "'.$key.'"');

    }
    
    /*
    final public function unset($key) {

        unset($this->attributes[$key]);
        
        return $this;

    }*/
    
    final public function set($key, $value) {

        if(array_key_exists($key, static::$hasOne))
            return $this->setHasOne($key, $value);

        if($this->loaded && (!isset($this->attributes[$key]) || $this->attributes[$key] !== $value))
            $this->updates[] = $key; 

        $this->attributes[$key] = $value;
        
        return $this;

    }

    final public function has($key, $value) {

        if(array_key_exists($key, static::$hasOne))
            return $this->hasHasOne($key, $value);

        if(array_key_exists($key, static::$hasMany))
            return $this->hasHasMany($key, $value);

        if(array_key_exists($key, static::$hasManyToMany))
            return $this->hasHasManyToMany($key, $value);

		throw new \OutOfRangeException('call to undefined attribute "'.$key.'"');

    }
    
    final public function add($key, $value) {

        if(array_key_exists($key, static::$hasMany)) {

            $value = $this->arrayize($value);

            foreach($value as $model)
                $this->addToHasMany($key, $model);
            
            return $this;

        }

        if(array_key_exists($key, static::$hasManyToMany)) {

            $value = $this->arrayize($value);

            foreach($value as $model)
                $this->addToHasManyToMany($key, $model);
            
            return $this;

        }

        throw new \DomainException('can not add on undefined relation "'.$key.'"');

    }
    
    final public function remove($key, $value) {

        if(array_key_exists($key, static::$hasOne))
            return $this->removeFromhasOne($key, $value);

        if(array_key_exists($key, static::$hasMany)) {

            $value = $this->arrayize($value);

            foreach($value as $model)
                $this->removeFromHasMany($key, $model);
            
            return $this;

        }

        if(array_key_exists($key, static::$hasManyToMany)) {

            $value = $this->arrayize($value);

            foreach($value as $model)
                $this->removeFromHasManyToMany($key, $model);
            
            return $this;

        }

        throw new \DomainException('can not remove on undefined relation "'.$key.'"');

    }
    
    final public function removeAll($key) {

        if(array_key_exists($key, static::$hasMany))
            return $this->removeAllFromHasMany($key);

        if(array_key_exists($key, static::$hasManyToMany))
            return $this->removeAllFromHasManyToMany($key);

        throw new \DomainException('can not removeAll on undefined relation "'.$key.'"');

    }
    
    final public function __call($method, array $args) {

        if(preg_match('#^get([a-zA-Z0-9_\-]+)$#i', $method, $match))
            return $this->get(lcfirst($match[1]), array_shift($args));

        if(preg_match('#^set([a-zA-Z0-9_\-]+)$#i', $method, $match))
            return $this->set(lcfirst($match[1]), array_shift($args));

        if(preg_match('#^has([a-zA-Z0-9_\-]+)$#i', $method, $match))
            return $this->has(lcfirst($match[1]), array_shift($args));

        if(preg_match('#^unset([a-zA-Z0-9_\-]+)$#i', $method, $match))
            return $this->unset(lcfirst($match[1]), array_shift($args));

        if(preg_match('#^add([a-zA-Z0-9_\-]+)$#i', $method, $match))
            return $this->add(lcfirst($match[1]), array_shift($args));

        if(preg_match('#^removeAll([a-zA-Z0-9_\-]+)$#i', $method, $match))
            return $this->removeAll(lcfirst($match[1]), array_shift($args));

        if(preg_match('#^remove([a-zA-Z0-9_\-]+)$#i', $method, $match))
            return $this->remove(lcfirst($match[1]), array_shift($args));

        throw new \BadMethodCallException('call to undefined method "'.$method.'" on "'.get_class($this).'"');
    
    }
    
    final public function __get($key) {

        return $this->get($key, '');
    }
    
    final public function __set($key, $value) {
        
        return $this->set($key, $value);
        
    }

    final public function __isset($key) {

        if(isset($this->attributes[$key]) || isset(static::$hasOne[$key]) || isset(static::$hasMany[$key]) || isset(static::$hasManyToMany[$key])) 
			return true;

        return false;

    }

    final public function __unset($key) {

        if(isset($this->attributes[$key])) {
			
            unset($this->attributes[$key]);
			
			return $this;
			
		}

		throw new \DomainException('call to undefined attribute "'.$key.'"');

    }
    
    final public function feed(array $values) {
        
        foreach($values as $key => $value)
            $this->set($key, $value);

        return $this;
        
    }
    
    final public function unsetPrimary() {

        foreach($this->getPrimary() as $key => $value)
            $this->set($key, null);

        return $this;
        
    }
    
    final public function getPrimary() {

        return $this->getAttributes(self::primary());
        
    }
    
    final public function getAttributes($keys = null) {

        $keys = !is_array($keys) ? ($keys ? array($keys) : []) : $keys;

        if(!count($keys))
            return $this->attributes;

        $attributes = [];
            
        foreach($keys as $key)
            $attributes[$key] = $this->attributes[$key];
            
        return $attributes;
        
    }

    public function postSave() {}
    public function preSave() {}

    final public function save($keys = null) {

        if($this->exists())
            return $this->forceUpdate($keys);
            
        return $this->forceInsert($keys);
 
    }

    final public function forceUpdate($keys = null) {

        $this->preSave();
        
        if($keys === null && $this->loaded)
            $keys = $this->updates;

        if(is_array($keys) && !count($keys)) {

            $this->postSave();
            
            return $this;

        }

        $update = self::update();

        foreach($this->getAttributes($keys) as $key => $value)
            if(!in_array($key, self::primary()))
                $update->update($key, $value);

        foreach(self::primary() as $primary)
            $update->whereEq($primary, $this->attributes[$primary]);

        $update->execute();

        $this->postSave();

        return $this;
 
    }

    final public function forceInsert($keys = null) {

        $this->preSave();
        
        $insert = self::insert();

        foreach($this->getAttributes($keys) as $key => $value) 
            $insert->insert($key, $value);

        $insert->execute();

        $lastInsertId = self::database()->lastInsertId(self::$sequence);

        if($lastInsertId) 
            $this->set(reset(self::primary()), $lastInsertId);

        $this->postSave();

        return $this;
 
    }
	
    public function postDelete() {}
    public function preDelete() {}
    
    final public function delete() {
		
        $this->preDelete();

        foreach(static::$hasMany as $hasMany => $infos) {

            $hasManyInfos = $this->hasManyInfo($hasMany);

            if(in_array(self::ON_DELETE_BLOCK, $hasManyInfos['options'])) {

                $count = count($this->getHasMany($hasMany));

                if(0 < $count)
                    throw new \Exception('unable to delete cause constraint ON_DELETE_BLOCK on relation "'.$hasMany.'"');

            }

        }

        foreach(static::$hasMany as $hasMany => $infos) {

            $hasManyInfos = $this->hasManyInfo($hasMany);

            if(in_array(self::ON_DELETE_CASCADE, $hasManyInfos['options'])) {

                $this->dropAllFromHasMany($hasMany);

            } else {

                $this->removeAllFromHasMany($hasMany);

            }

        }
            
        foreach(static::$hasManyToMany as $hasManyToMany => $infos) {

            $this->removeAllFromHasManyToMany($hasManyToMany);

        }

        $delete = self::deleteFrom();
            
        foreach(self::primary() as $primary)
            $delete->whereEq($primary, $this->attributes[$primary]);

        $delete->execute();
		
        $this->postDelete();

        return $this;
    
    }

    final public function exists() {

        // If one of primary is null or !isset record doesnt exists
        foreach(self::primary() as $primary)
            if(!isset($this->attributes[$primary]) || $this->attributes[$primary] === null) 
                return false;

        // If all pk exists and are setted and if it is an autoincrement key so record MUST exists
        if(self::$sequence === true)
            return true;

        // In other cases, we check directly in db
        $statement = self::select(1);
        
        foreach($this->getPrimary() as $primary => $value)
            $statement->whereEq($primary, $value);

        return (bool) ! (count($statement) === 0);
    
    }
    
    // Relations

    static final protected function formatWith($relations, $closure = null) {
 
        if(!is_array($relations)) {

            if(is_array($closure) && is_array($closure['with']) && array_key_exists('closure', $closure))  {

                return [$relations => $closure];

            }

            $relations = $relations ? [$relations => $closure] : [];

        }

        $nestedRelations = [];

        foreach($relations as $relationName => $closure) {

            if(is_array($closure) && is_array($closure['with']) && array_key_exists('closure', $closure))  {

                $nestedRelations[$relationName] = $closure;

                continue;

            }

            if(is_numeric($relationName)) {

                $relationName = $closure;

                $closure = null;

            }

            $subRelations = explode('.', $relationName);

            $relationName = array_shift($subRelations);

            $subRelationName = implode('.', $subRelations);

            if(isset($nestedRelations[$relationName])) {

                if($subRelationName)
                    $nestedRelations[$relationName]['with'] += self::formatWith($subRelationName, $closure);

            } else {

                $nestedRelations[$relationName] = [
                    'closure' => ! $subRelationName ? $closure : null, 
                    'with' => $subRelationName ? self::formatWith($subRelationName, $closure) : [],
                ];

            }

        }

        return $nestedRelations;

    }

    final static public function with($relations, \Closure $closure = null) {

        $model = new static();
    
        $select = self::select();

        $closures = [];
        
        $nestedRelations = self::formatWith($relations, $closure);

        foreach($nestedRelations as $relationName => $nestedRelation) {

            if(array_key_exists($relationName, $model::$hasOne)) {
                $closures[] = $model->withHasOne($select, $relationName, $nestedRelation['closure'], $nestedRelation['with']);
                continue;
            }

            if(array_key_exists($relationName, $model::$hasMany)) {
                $closures[] = $model->withHasMany($select, $relationName, $nestedRelation['closure'], $nestedRelation['with']);
                continue;
            }
      
            if(array_key_exists($relationName, $model::$hasManyToMany)) {
                $closures[] = $model->withHasManyToMany($select, $relationName, $nestedRelation['closure'], $nestedRelation['with']);
                continue;
            }

            throw new \DomainException('unknown relation "'.$relationName.'" on model "'.get_class($model).'"');

        }
            
        if(!count($closures))
            return $select;

        $select->fetchValue(function($line) use($closures) {
            
            $model = self::new($line);

            foreach($closures as $closure)
                $closure($model);
            
            return $model;
            
        });
        
        return $select;

    }

    final public function withHasOne(Statement $select, $hasOne, \Closure $closure = null, array $with = []) {

        extract($this->hasOneInfo($hasOne));
        
        $calcul = function() use($select, $foreignClass, $foreignKeys, $localKeys, $closure, $with) {

            $statement = clone $select;

            $concatKey = count($localKeys) < 2 ? implode('', $localKeys) : 'CONCAT('.implode(', \'-\' ,', $localKeys).')';
        
            $concatValue = count($foreignKeys) < 2 ? implode('', $foreignKeys) : 'CONCAT('.implode(', \'-\' ,', $foreignKeys).')';

            $query = $foreignClass::with($with)->select('*')->whereIn($concatKey, $statement->select($concatValue));

            if($closure) 
                $closure($query);

            $datas = [];

            foreach($query as $model) {

                $key = implode('-', $model->getAttributes($localKeys));

                if(!isset($datas[$key]))
                    $datas[$key] = [];

                $datas[$key] = $model;

            }
            
            return $datas;
            
        };

        return function(Model $model) use($calcul, $hasOne, $foreignKeys) {

            static $datas;
            
            if(!$datas)
                $datas = $calcul();
        
            $key = implode('-', $model->getAttributes($foreignKeys));

            if(isset($datas[$key])) 
                $model->attributes[$hasOne] = $datas[$key]; 

        };

    }

    final public function withHasMany(Statement $select, $hasMany, \Closure $closure = null, array $with = []) {

        extract($this->hasManyInfo($hasMany));

        $calcul = function() use($select, $foreignClass, $foreignKeys, $localKeys, $closure, $with) {

            $statement = clone $select;

            $concatValue = count($localKeys) < 2 ? implode('', $localKeys) : 'CONCAT('.implode(', \'-\' ,', $localKeys).')';
        
            $concatKey = count($foreignKeys) < 2 ? implode('', $foreignKeys) : 'CONCAT('.implode(', \'-\' ,', $foreignKeys).')';

            $query = $foreignClass::with($with)->select('*')->whereIn($concatKey, $statement->select($concatValue));

            if($closure) 
                $closure($query);

            $datas = [];

            foreach($query as $model) {

                $key = implode('-', $model->getAttributes($foreignKeys));

                if(!isset($datas[$key]))
                    $datas[$key] = [];

                $datas[$key][] = $model;

            }
            
            return $datas;
            
        };

        return function(Model $model) use($calcul, $hasMany, $localKeys) {

            static $datas;
            
            if(!$datas)
                $datas = $calcul();
        
            $key = implode('-', $model->getAttributes($localKeys));

            if(isset($datas[$key])) 
                $model->attributes[$hasMany] = $datas[$key]; 

        };

    }

    final public function withHasManyToMany(Statement $select, $hasManyToMany, \Closure $closure = null, array $with = array()) {

        extract($this->hasManyToManyInfo($hasManyToMany));
        
        $calcul = function() use(
            $select, 
            $throughTable, 
            $throughForeignKeys, 
            $throughLocalKeys, 
            $foreignClass, 
            $foreignKeys, 
            $localKeys, 
            $closure, 
            $with
        ) {

            $statement = clone $select;
            $concatLocal = count($localKeys) < 2 ? implode('', $localKeys) : 'CONCAT('.implode(', \'-\' , ', $localKeys).')';
            $statement->select($concatLocal);

            $concatThroughLocal = count($throughLocalKeys) < 2 ? 't2.'.implode('', $throughLocalKeys) : 'CONCAT(t1.'.implode(', \'-\' , t2.', $throughLocalKeys).')';

            $query = $foreignClass::with($with)->select('t1.*, '.$concatThroughLocal)->alias('t1')->innerJoin($throughTable, 't2');
 
            foreach(array_combine($throughForeignKeys, $foreignKeys) as $throughKey => $foreignKey) {
                $query->onEq('t2', 't2.'.$throughKey, 't1.'.$foreignKey);
            }

            $query->whereIn($concatThroughLocal, $statement);

            if($closure) 
                $closure($query); 

            $datas = [];

            foreach($query as $model) {

                $key = implode('-', $model->getAttributes($foreignKeys));

                if(!isset($datas[$key]))
                    $datas[$key] = [];

                $datas[$key][] = $model;

            }
            
            return $datas;
            
        };

        return function(Model $model) use($calcul, $hasManyToMany, $localKeys) {

            static $datas;

            if(!$datas)
                $datas = $calcul();
           
            $key = implode('-', $model->getAttributes($localKeys));

            if(isset($datas[$key])) 
                $model->attributes[$hasManyToMany] = $datas[$key]; 

        };

    }

    final protected function hasOneInfo($hasOne) {

        if(!isset(static::$hasOne[$hasOne]))
            throw new \DomainException('call to undefined hasOne relation "'.$hasOne.'"');

        if(!isset(static::$hasOne[$hasOne][0]))
            throw new \Exception('bad hasOne relation synthax  index 0 should define the foreignClass in "'.$hasOne.'"');

		$foreignClass = static::$hasOne[$hasOne][0];
		
        if(!isset(static::$hasOne[$hasOne][1]))
            throw new \Exception('bad hasOne relation synthax  index 1 should define the keys in "'.$hasOne.'"');

        $keys = $this->arrayize(static::$hasOne[$hasOne][1]);

        if(!$this->isAssociativeArray($keys)) 
            $keys = array_combine($keys, $foreignClass::primary());

        if(!class_exists($foreignClass))
            throw new \InvalidArgumentException('call to undefined foreignClass "'.$foreignClass.'" in relation "'.$hasOne.'"');

        if(!(is_a($foreignClass, __CLASS__, true)))
            throw new \InvalidArgumentException('foreignClass "'.$foreignClass.'" does not extends "'.__CLASS__.'" in relation "'.$hasOne.'"');

		return [
            'foreignClass' => $foreignClass, 
            'foreignKeys' => array_keys($keys), 
            'localKeys' => $keys,
            'options' => [],
        ];
        
    }

	final public function removeFromhasOne($hasOne) {

		extract($this->hasOneInfo($hasOne));
        
        foreach($foreignKeys as $foreignKey)
            $this->set($foreignKey, null);

        return $this->save();
        
    }

    final public function setHasOne($hasOne, Model $model) {

		extract($this->hasOneInfo($hasOne));
        
        if(!($model instanceof $foreignClass))
            throw new \InvalidArgumentException('invalid foreignClass "'.get_class($model).'" insteadof "'.$foreignClass.'"');
        
        $values = array_combine($foreignKeys, $model->getAttributes($localKeys));
        
        $save = ! $this->exists();

        foreach($values as $foreignKey => $primaryKey) {

            try {

                $key = $this->get($foreignKey);

                if($key != $primaryKey) {
    
                    $this->set($foreignKey, $primaryKey);
    
                    $save = true;
    
                }

            } catch(\Exception $e) {

                $this->set($foreignKey, $primaryKey);

                $save = true;

            }

        }

        if($save)
            $this->save();

        return $this;
        
    }
    
    final public function hasHasOne($hasOne, Model $model) {

        extract($this->hasOneInfo($hasOne));

        if(!$model->exists()) 
            return false;
        
        $checkKeys = array_combine($foreignKeys, $localKeys);

        if(!count($checkKeys)) 
            return false;
  
        foreach($checkKeys as $foreignKey => $localKey) 
            if($model->get($foreignKey) != $this->get($localKey)) 
                return false;
 
        return true;

    }
    
    final public function getHasOne($hasOne) {
	
		extract($this->hasOneInfo($hasOne));
		
		return $foreignClass::findBy(array_combine($localKeys, $this->getAttributes($foreignKeys)));
        
    }

    final protected function hasManyInfo($hasMany) {

        if(!isset(static::$hasMany[$hasMany]))
            throw new \DomainException('call to undefined hasMany relation "'.$hasMany.'"');

        if(!isset(static::$hasMany[$hasMany][0]))
            throw new \OutOfRangeException('bad hasMany relation synthax index 0 should define the foreignClass in "'.$hasMany.'"');

		$myForeignClass = static::$hasMany[$hasMany][0];		

        if(!isset(static::$hasMany[$hasMany][1]))
            throw new \OutOfRangeException('bad hasMany relation synthax index 1 should define the keys in "'.$myForeignClass.'"');

        $keys = $this->arrayize(static::$hasMany[$hasMany][1]);
        
        if(!$this->isAssociativeArray($keys)) 
            $keys = array_combine($keys, self::primary());

        $options = $this->arrayize(static::$hasMany[$hasMany][2] ?? []);

        if(!empty(array_diff($options, self::$options)))
            throw new \InvalidArgumentException('bad hasMany relation synthax index 2 should be empty or should only list valid options');

        if(!class_exists($myForeignClass))
            throw new \InvalidArgumentException('call to undefined foreignClass "'.$myForeignClass.'" in relation "'.$hasMany.'" of "'.static::class.'"');

        if(!is_subclass_of($myForeignClass, __CLASS__))
            throw new \InvalidArgumentException('call to undefined foreignClass "'.$myForeignClass.'" in relation "'.$hasMany.'"');

		return [
			'foreignClass' => $myForeignClass,
			'foreignKeys' => array_keys($keys),
            'localKeys' => $keys, 
            'options' => $options,
        ];
		
    }

    final public function addToHasMany($hasMany, Model $model) {

		extract($this->hasManyInfo($hasMany));
        
        if(!($model instanceof $foreignClass))
            throw new \InvalidArgumentException('invalid foreignClass "'.get_class($model).'" insteadof "'.$foreignClass.'"');
    
        $foreignValues = array_combine($foreignKeys, $this->getAttributes($localKeys));

        if($model->exists()) {

            $update = $foreignClass::update();
    
            foreach($model->getPrimary() as $key => $value) 
                $update->whereEq($key, $value);
    
            foreach($foreignValues as $key => $value) 
                $update->update($key, $value);
                
            $update->execute();

        } else {
    
            foreach($foreignValues as $key => $value) 
                $model->set($key, $value);

            $model->save();
        }

        return $this;
        
    }
    
    final public function removeFromHasMany($hasMany, Model $model) {

		extract($this->hasManyInfo($hasMany));
        
        if(!($model instanceof $foreignClass))
            throw new \InvalidArgumentException('invalid foreignClass "'.get_class($model).'" insteadof "'.$foreignClass.'"');
     
        $update = $foreignClass::update();

        foreach($model->getPrimary() as $key => $value) 
            $update->whereEq($key, $value);

        foreach($foreignKeys as $key) 
            $update->update($key, null);
            
        $update->execute();
            
        return $this;
        
    }
    
    final public function removeAllFromHasMany($hasMany) {

		extract($this->hasManyInfo($hasMany));

        $update = $foreignClass::update();
        
        $values = array_combine($foreignKeys, $this->getAttributes($localKeys));
        
        foreach($values as $key => $value) 
            $update->update($key, null)->whereEq($key, $value);
  
        $update->execute();
            
        return $this;
        
    }
    
    final public function dropAllFromHasMany($hasMany) {

		extract($this->hasManyInfo($hasMany));

        $delete = $foreignClass::deleteFrom();
        
        $values = array_combine($foreignKeys, $this->getAttributes($localKeys));
        
        foreach($values as $key => $value) 
            $delete->whereEq($key, $value);
  
        $delete->execute();
            
        return $this;
        
    }
    
    final public function hasHasMany($hasMany, Model $model) {

        extract($this->hasManyInfo($hasMany));
        
        if(!($model instanceof $foreignClass))
            throw new \InvalidArgumentException('invalid foreignClass "'.get_class($model).'" insteadof "'.$foreignClass.'"');
    
        if(!$model->exists()) 
            return false;
        
        $checkKeys = array_combine($foreignKeys, $localKeys);

        if(!count($checkKeys)) 
            return false;
  
        foreach($checkKeys as $foreignKey => $localKey) 
            if($model->get($foreignKey) != $this->get($localKey)) 
                return false;
 
        return true;
        
    }
    
    final public function getHasMany($hasMany) {
		
		extract($this->hasManyInfo($hasMany));
		
		$statement = $foreignClass::select();

		foreach(array_combine($foreignKeys, $this->getAttributes($localKeys)) as $key => $value)
			$statement->whereEq($key, $value);
			
		return $statement->bindCollection($this, $hasMany); ;
		
    }

    final protected function hasManyToManyInfo($hasManyToMany) {
        
        if(!isset(static::$hasManyToMany[$hasManyToMany]))
            throw new \DomainException('call to undefined hasManyToMany relation "'.$hasManyToMany.'"');

        if(!isset(static::$hasManyToMany[$hasManyToMany][0]))
            throw new \OutOfRangeException('bad hasMany relation synthax index 0 should define the foreignClass in "'.static::class.'::'.$hasManyToMany.'"');

        $foreignClass = static::$hasManyToMany[$hasManyToMany][0];

        if(!isset(static::$hasManyToMany[$hasManyToMany][1]))
            throw new \OutOfRangeException('bad hasManyToMany relation synthax index 1 should define the throughTable in "'.static::class.'::'.$hasManyToMany.'"');

        $throughTable = static::$hasManyToMany[$hasManyToMany][1];

        if(!isset(static::$hasManyToMany[$hasManyToMany][2]))
            throw new \OutOfRangeException('bad hasManyToMany relation synthax index 2 should define the throughForeignKeys in "'.static::class.'::'.$hasManyToMany.'"');

        $throughForeignKeys = $this->arrayize(static::$hasManyToMany[$hasManyToMany][2]);

        if(!isset(static::$hasManyToMany[$hasManyToMany][3]))
            throw new \OutOfRangeException('bad hasManyToMany relation synthax index 3 should define the throughLocalKeys in "'.static::class.'::'.$hasManyToMany.'"');

        $throughLocalKeys = $this->arrayize(static::$hasManyToMany[$hasManyToMany][3]);

        $options = $this->arrayize(static::$hasManyToMany[$hasManyToMany][4] ?? []);

        if(!$this->isAssociativeArray($throughForeignKeys)) 
            $throughForeignKeys = array_combine($throughForeignKeys, $foreignClass::primary());

        if(!$this->isAssociativeArray($throughLocalKeys)) 
            $throughLocalKeys = array_combine($throughLocalKeys, self::primary());

        return [
            'foreignClass' => $foreignClass,
            'throughTable' => $throughTable,
            'throughForeignKeys' => array_keys($throughForeignKeys),
            'foreignKeys' => $throughForeignKeys,
            'throughLocalKeys' => array_keys($throughLocalKeys),
            'localKeys' => $throughLocalKeys,
            'options' => $options,
        ];

    }
    
    final public function addToHasManyToMany($hasManyToMany, Model $model) {

		extract($this->hasManyToManyInfo($hasManyToMany));
         
        if(!($model instanceof $foreignClass))
            throw new \InvalidArgumentException('invalid foreignClass "'.get_class($model).'" insteadof "'.$foreignClass.'"');
    
        $insert = self::database()->insert($throughTable);

		foreach(array_combine($throughLocalKeys, $this->getAttributes($localKeys)) as $key => $value) {
			$insert->insert($key, $value);
		}
        
		foreach(array_combine($throughForeignKeys, $model->getAttributes($foreignKeys)) as $key => $value) {
			$insert->insert($key, $value);
		}
        
        $insert->execute();
        
        return $this;
        
    }
    
    final public function removeFromHasManyToMany($hasManyToMany, Model $model) {

		extract($this->hasManyToManyInfo($hasManyToMany));
          
        if(!($model instanceof $foreignClass))
            throw new \InvalidArgumentException('invalid foreignClass "'.get_class($model).'" insteadof "'.$foreignClass.'"');
    
        $delete = self::database()->delete($throughTable);
        
		foreach(array_combine($throughLocalKeys, $this->getAttributes($localKeys)) as $throughKey => $localValue) {
			$delete->whereEq($throughKey, $localValue);
		}
        
		foreach(array_combine($throughForeignKeys, $model->getAttributes($foreignKeys)) as $throughKey => $foreignValue) {
			$delete->whereEq($throughKey, $foreignValue);
		}
        
        $delete->execute();
        
        return $this;
        
        
    } 
    
    final public function removeAllFromHasManyToMany($hasManyToMany) {

		extract($this->hasManyToManyInfo($hasManyToMany));
        
        $delete = self::database()->delete($throughTable);

		foreach(array_combine($throughLocalKeys, $this->getAttributes($localKeys)) as $key => $value) {
			$delete->whereEq($key, $value);
		}
        
        $delete->execute();
        
        return $this;
        
    }

    final public function hasHasManyToMany($hasManyToMany, Model $model) {

        extract($this->hasManyToManyInfo($hasManyToMany));

        $select = self::database()->select($throughTable)->select('COUNT(1) as nb');

        foreach(array_combine($throughForeignKeys, $foreignKeys) as $troughKey => $foreignKey) 
            $select->whereEq($troughKey, $model->get($foreignKey));

        foreach(array_combine($throughLocalKeys, $localKeys) as $troughKey => $localKey) 
            $select->whereEq($troughKey, $this->get($localKey));
        
        $nb = (int) $select->fetchValue('nb')->execute()->next();

        return (bool) (0 < $nb);

    }

    final public function getHasManyToMany($hasManyToMany) {

		extract($this->hasManyToManyInfo($hasManyToMany));

		$statement = $foreignClass::select('t1.*')->alias('t1')->innerJoin($throughTable, 't2');
        
		foreach(array_combine($throughForeignKeys, $foreignKeys) as $key => $value) {
			$statement->onEq('t2', 't2.'.$key, 't1.'.$value);
		}
        
		foreach(array_combine($throughLocalKeys, $this->getAttributes($localKeys)) as $key => $value) {
			$statement->whereEq('t2.'.$key, $value);
		}

		return $statement->bindCollection($this, $hasManyToMany); 
        
    }
    
}

// Do not clause PHP tags unless it is really necessary

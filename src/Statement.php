<?php

namespace Yab\Core;

class Statement implements \Iterator, \Countable {

    const LEFT_PACK_BOUNDARY = '-L_-';
    const RIGHT_PACK_BOUNDARY = '-_R-';

    private $database = null;
    private $result = null;
    private $sql = '';
    private $fetchKey = null;
    private $fetchValue = null;
    private $key = null;
    private $current = null;
    private $packs = [];

    private $collectionModel = null;
    private $collectionRelation = null;

    public function __construct(Database $database, $sql) {

        $this->database = $database;

        $this->sql = (string) $sql;
    }

    public function formFilter(Form $form, array $bindings = []) {

        foreach($bindings as $field => $closure)
            $closure($this, $field);
            
        return $this;

    }

    public function bindCollection(Model $model, string $collectionRelation) {
        
        $this->collectionFrom = $model;
        $this->collectionRelation = $collectionRelation;

        return $this;

    }

    public function isCollection() : bool {
        
        return (bool) ($this->collectionFrom instanceof Model);

    }

    public function add(Model $model) {
        
        if(!$this->isCollection())
            throw new \Exception('can not add models in a "non-collection" statement');

        $this->collectionFrom->add($this->collectionRelation, $model);

        return $this->reset();

    }

    public function remove(Model $model) {

        if(!$this->isCollection())
            throw new \Exception('can not remove models from a "non-collection" statement');

        $this->collectionFrom->remove($this->collectionRelation, $model);

        return $this->reset();

    }

    public function removeAll() {

        if(!$this->isCollection())
            throw new \Exception('can not remove all models from a "non-collection" statement');

        $this->collectionFrom->removeAll($this->collectionRelation);

        return $this->reset();
    }

    public function __toString() {

        return $this->sql;
    }

    public function count() {
        
        $statement = clone $this;
        
        $statement->reset()->select('COUNT(*) as total')->fetchValue('total')->execute();
        
        return $statement->next();
    }

    public function fetchKey($fetchKey = null) {

		if($fetchKey instanceof \Closure) {

            $this->fetchKey = $fetchKey;
			
        } elseif($fetchKey !== null) {
	
			$fetchKey = explode('.', (string) $fetchKey);

			$this->fetchKey = array_pop($fetchKey);
			
		} else {
				
			$this->fetchKey = null;
			
		}

        return $this;
    }

    public function fetchValue($fetchValue = null) {

        if($fetchValue instanceof Model) {
			
            $this->fetchValue = function(array $datas) use($fetchValue) { $model = clone $fetchValue; $model->feed($datas); return $model; };

        } elseif(is_subclass_of($fetchValue, Model::class)) {

            $this->fetchValue = function(array $datas) use($fetchValue) { return $fetchValue::new($datas); };
            
        } elseif(is_string($fetchValue)) {

            $fetchValue = explode('.', (string) $fetchValue);
            $fetchValue = array_pop($fetchValue);

            $this->fetchValue = function(array $datas) use($fetchValue) { return $datas[$fetchValue] ?? null; };
            
        } elseif(is_int($fetchValue)) {
			
            $this->fetchValue = function(array $datas) use($fetchValue) { for($i = 0; $i < $fetchValue; $i++) { array_shift($datas); } return current($datas); };

        } elseif($fetchValue === null) {
			
			$this->fetchValue = null;
			
        } elseif($fetchValue instanceof \Closure) {

            $this->fetchValue = $fetchValue;
			
        } else {

            throw new \Exception('fetchValue must be an instance of Fetched or a string');
        }

        return $this;
    }

    public function reset() {

        $this->result = null;
        $this->current = null;

        return $this;
    }
    
    public function isExecuted() {
        
        return (bool) ($this->result !== null);
        
    }

    public function execute() {

        if($this->isExecuted()) 
            return $this; 

        $this->result = $this->database->query($this->sql);

        Logger::getLogger()->debug('SQL_' . ($this->isExecuted() ? 'OK' : 'KO') . ' ' . $this->sql);

        return $this;
    }

    public function valid() {

        return is_array($this->current);
    }

    public function next() {

        $this->key++;

        if ($this->execute()->result)
            $this->current = $this->execute()->result->fetch(\PDO::FETCH_ASSOC);

        return $this->current();
    }

    public function current() {

		if(!$this->current)
			return $this->current;

        if($this->fetchValue === null) 
            return $this->current;

        $closure = $this->fetchValue;

        return $closure($this->current);

    }

    public function key() {

        if($this->fetchKey instanceof \Closure) {

            $closure = $this->fetchKey;
            
            return $closure($this->current);
            
        } elseif ($this->fetchKey !== null) {

            if (!array_key_exists($this->fetchKey, $this->current))
                throw new \Exception('invalid fetchKey "' . $this->fetchKey . '"');

            return $this->current[$this->fetchKey];
        }

        return $this->key;
    }

    public function rewind() {

        $this->key = -1;

        $this->reset();
        $this->execute();

        return $this->next();
    }

    public function toArray($key = null, $value = null) {

        $statement = clone $this;

        if($key !== null)
            $statement->fetchKey($key);
            
        if($value !== null)
            $statement->fetchValue($value);

        $array = [];

        foreach ($statement as $key => $value) 
            $array[$key] = $value;

        return $array;
    }

    public function __call($method, $args) {

        if (preg_match('#^(or)?where(exists|like|llike|rlike|notlike|notllike|notrlike|eq|ne|lt|lte|gt|gte|in|notin|null|notnull|between|notbetween)$#i', $method, $match)) {

            $operator = $match[1] ? 'OR' : 'AND';
            $field = (string) array_shift($args);
            $value1 = array_shift($args);
            $value2 = array_shift($args);

            switch (strtolower($match[2])) {

                case 'eq' : return $this->where($this->database->identifier($field) . ' = ' . $this->database->quote($value1), $operator);
                case 'ne' : return $this->where($this->database->identifier($field) . ' != ' . $this->database->quote($value1), $operator);
                case 'lt' : return $this->where($this->database->identifier($field) . ' < ' . $this->database->quote($value1), $operator);
                case 'lte' : return $this->where($this->database->identifier($field) . ' <= ' . $this->database->quote($value1), $operator);
                case 'gt' : return $this->where($this->database->identifier($field) . ' > ' . $this->database->quote($value1), $operator);
                case 'gte' : return $this->where($this->database->identifier($field) . ' >= ' . $this->database->quote($value1), $operator);
                case 'in' : 
                
                    if($value1 instanceof self) {
                        
                        $value1 = (string) $value1;

                    } elseif(is_array($value1)) {
                        
                        $value1 = implode(', ', array_map(array($this->database, 'quote'), $value1));

                    } else {

                        $value1 = $this->database->quote($value1);

                    }
                    
                    return $this->where($this->database->identifier($field) . ' IN (' . $value1 . ')', $operator);
                case 'notin' : return $this->where($this->database->identifier($field) . ' NOT IN (' . ($value1 instanceof self ? $value1 : (is_array($value1) ? implode(', ', array_map(array($this->database, 'quote'), $value1)) : $this->database->quote($value1))) . ')', $operator);
                case 'null' : return $this->where($this->database->identifier($field) . ' IS NULL', $operator);
                case 'notnull' : return $this->where($this->database->identifier($field) . ' IS NOT NULL', $operator);
                case 'like' : return $this->where($this->database->identifier($field) . ' LIKE ' . $this->database->quote('%' . $value1 . '%'), $operator);
                case 'llike' : return $this->where($this->database->identifier($field) . ' LIKE ' . $this->database->quote($value1 . '%'), $operator);
                case 'rlike' : return $this->where($this->database->identifier($field) . ' LIKE ' . $this->database->quote('%' . $value1), $operator);
                case 'notlike' : return $this->where($this->database->identifier($field) . ' NOT LIKE ' . $this->database->quote('%' . $value1 . '%'), $operator);
                case 'notllike' : return $this->where($this->database->identifier($field) . ' NOT LIKE ' . $this->database->quote($value1 . '%'), $operator);
                case 'notrlike' : return $this->where($this->database->identifier($field) . ' NOT LIKE ' . $this->database->quote('%' . $value1), $operator);
                case 'between' : return $this->where($this->database->identifier($field) . ' BETWEEN ' . $this->database->quote($value1) . ' AND ' . $this->database->quote($value2), $operator);
                case 'notbetween' : return $this->where($this->database->identifier($field) . ' NOT BETWEEN ' . $this->database->quote($value1) . ' AND ' . $this->database->quote($value2), $operator);
                case 'exists' : return $this->where('EXISTS(' . $value1 . ')', $operator);
            }
        }

        if (preg_match('#^(or)?on(eq|ne|lt|lte|gt|gte)$#i', $method, $match)) {

            $operator = $match[1] ? 'OR' : 'AND';
            $alias = (string) array_shift($args);
            $field1 = (string) array_shift($args);
            $field2 = (string) array_shift($args);

            switch (strtolower($match[2])) {

                case 'eq' : return $this->on($alias, $this->database->identifier($field1) . ' = ' . $this->database->identifier($field2), $operator);
                case 'ne' : return $this->on($alias, $this->database->identifier($field1) . ' != ' . $this->database->identifier($field2), $operator);
                case 'lt' : return $this->on($alias, $this->database->identifier($field1) . ' < ' . $this->database->identifier($field2), $operator);
                case 'lte' : return $this->on($alias, $this->database->identifier($field1) . ' <= ' . $this->database->identifier($field2), $operator);
                case 'gt' : return $this->on($alias, $this->database->identifier($field1) . ' > ' . $this->database->identifier($field2), $operator);
                case 'gte' : return $this->on($alias, $this->database->identifier($field1) . ' >= ' . $this->database->identifier($field2), $operator);
            }
        }

        if (preg_match('#^(natural|cross|inner|left|)join$#i', $method, $match)) {

            $join = $match[1];
            $table = array_shift($args);
            $alias = array_shift($args);

            return $this->join($join, $table, $alias);
        }

        throw new \BadMethodCallException('call to undefined method "' . $method . '"');
    }

    public function where($where, $operator = 'AND') {

        if (!$where)
            return $this;

        return $this->sqlReplace(array(
            '#\sWHERE\s#i' => ' WHERE (' . $where . ') ' . $operator . ' ',
            '#\sGROUP\s+BY\s#i' => ' WHERE (' . $where . ') GROUP BY ',
            '#\sHAVING\s#i' => ' WHERE (' . $where . ') HAVING ',
            '#\sORDER\s+BY\s#i' => ' WHERE (' . $where . ') ORDER BY ',
            '#\sLIMIT\s#i' => ' WHERE (' . $where . ') LIMIT ',
            '#$#i' => ' WHERE ' . $where,
        ));
    }

    public function noWhere() {

        return $this->sqlReplace(array(
            '#\sWHERE\s.+\sGROUP\s+BY\s#i' => ' GROUP BY ',
            '#\sWHERE\s.+\sHAVING\s#i' => ' HAVING ',
            '#\sWHERE\s.+\sORDER\s+BY\s#i' => ' ORDER BY ',
            '#\sWHERE\s.+\sLIMIT\s#i' => ' LIMIT ',
            '#\sWHERE\s.+$#i' => '',
        ));
    }

    public function on($alias, $on, $operator = 'AND') {

        if (!$on)
            return $this;

        return $this->sqlReplace(array(
            '#\sjoin\s+([^\s]+)\s+' . preg_quote($alias, '#') . '\s+on\s+(.+)\s+(and|inner|left|where|group\s+by|having|order\s+by|limit)(.*)$#is' => ' JOIN $1 ' . $alias . ' ON $2 ' . $operator . ' ' . $on . ' $3 $4',
            '#\sjoin\s+([^\s]+)\s+' . preg_quote($alias, '#') . '\s+on\s+(and|inner|left|where|group\s+by|having|order\s+by|limit)(.*)$#is' => ' JOIN $1 ' . $alias . ' ON ' . $on . ' $2 $3',
            '#\sjoin\s+([^\s]+)\s+' . preg_quote($alias, '#') . '\s+on\s*$#is' => ' JOIN $1 ' . $alias . ' ON ' . $on . ' $2 $3',
        ));
    }

    public function select($select) {

        return $this->sqlReplace(array(
            '#^\s*select\s+.*\s+from\s+(.+)$#is' => 'SELECT ' . (is_array($select) ? implode(', ', $select) : $select) . ' FROM $1',
        ));
    }

    public function andSelect($select) {

        return $this->sqlReplace(array(
            '#^\s*select\s+(.*)\s+from\s+(.+)$#is' => 'SELECT $1, ' . (is_array($select) ? implode(', ', $select) : $select) . ' FROM $2',
        ));
    }

    public function fromTable() {

        return $this->sqlMatch(array(
            '#^\s*select\s+.*\s+from\s+([^\s]+).*$#is' => 1,
        ));
    }

    public function fromAlias() {

        try {

            return $this->sqlMatch(array(
                '#^\s*select\s+.*\s+from\s+[^\s]+\s+(where|group|inner\s+join|left\s+join|join|natural\s+join|order|having|limit).+$#is' => null,
                '#^\s*select\s+.*\s+from\s+[^\s]+\s+([^\s]+)\s+.*$#is' => 1,
            ));
        } catch (\Exception $e) {

            return $this->fromTable();
        }
    }

    public function alias($alias) {

        return $this->sqlReplace(array(
            '#^\s*(select\s+.*\s+from)\s+([^\s]+)\s+(where|limit|group|order)(.+)$#is' => '$1 $2 ' . $alias . ' $3 $4',
            '#^\s*(select\s+.*\s+from)\s+([^\s]+)\s+[^\s]+\s+(where|limit|group|order)(.+)$#is' => '$1 $2 ' . $alias . ' $3 $4',
            '#^\s*(select\s+.*\s+from)\s+([^\s]+)\s*$#is' => '$1 $2 ' . $alias,
            '#^\s*update\s+([^\s]+)\s+[^\s]+\s+set\s*(.*)$#is' => 'UPDATE $1 ' . $alias . ' SET $2',
            '#^\s*(delete\s+.*\s+from)\s+([^\s]+)\s+(where.+)$#is' => '$1 $2 ' . $alias . ' $3',
            '#^\s*(delete\s+.*\s+from)\s+([^\s]+)\s+[^\s]+\s+(where.+)$#is' => '$1 $2 ' . $alias . ' $3',
            '#^\s*(delete\s+.*\s+from)\s+([^\s]+)\s+#is' => '$1 $2 ' . $alias,
        ));
    }

    public function join($join, $table, $alias) {

        $on = in_array(strtolower($join), array('left', 'inner', '')) ? 'ON' : '';

        $join = strtoupper($join);

        if ($table instanceof Statement) {

            return $this->sqlReplace(array(
                '#\sWHERE\s#i' => ' ' . $join . ' JOIN (' . $table . ') ' . $alias . ' ' . $on . ' WHERE ',
                '#\sGROUP\s+BY\s#is' => ' ' . $join . ' JOIN (' . $table . ') ' . $alias . ' ' . $on . ' GROUP BY ',
                '#\sHAVING\s#i' => ' ' . $join . ' JOIN (' . $table . ') ' . $alias . ' ' . $on . ' HAVING ',
                '#\sORDER\s+BY\s#is' => ' ' . $join . ' JOIN (' . $table . ') ' . $alias . ' ' . $on . ' ORDER BY ',
                '#\sLIMIT\s#i' => ' ' . $join . ' JOIN (' . $table . ') ' . $alias . ' ' . $on . ' LIMIT ',
                '#$#i' => ' ' . $join . ' JOIN (' . $table . ') ' . $alias . ' ' . $on,
            ));
        }

        return $this->sqlReplace(array(
            '#\sWHERE\s#i' => ' ' . $join . ' JOIN ' . $this->database->identifier($table) . ' ' . $alias . ' ' . $on . ' WHERE ',
            '#\sGROUP\s+BY\s#is' => ' ' . $join . ' JOIN ' . $this->database->identifier($table) . ' ' . $alias . ' ' . $on . ' GROUP BY ',
            '#\sHAVING\s#i' => ' ' . $join . ' JOIN ' . $this->database->identifier($table) . ' ' . $alias . ' ' . $on . ' HAVING ',
            '#\sORDER\s+BY\s#is' => ' ' . $join . ' JOIN ' . $this->database->identifier($table) . ' ' . $alias . ' ' . $on . ' ORDER BY ',
            '#\sLIMIT\s#i' => ' ' . $join . ' JOIN ' . $this->database->identifier($table) . ' ' . $alias . ' ' . $on . ' LIMIT ',
            '#$#i' => ' ' . $join . ' JOIN ' . $this->database->identifier($table) . ' ' . $alias . ' ' . $on,
        ));
    }

    public function update($key, $value) {

        $update = $this->database->identifier($key) . ' = ' . $this->database->quote($value);

        return $this->sqlReplace(array(
            '#^\s*update\s+(.+)\s+set\s*([^\s]+)\s*WHERE#is' => 'UPDATE $1 SET $2, ' . $update . ' WHERE',
            '#^\s*update\s+(.+)\s+set\s*WHERE#is' => 'UPDATE $1 SET ' . $update . ' WHERE',
            '#^\s*update\s+(.+)\s+set\s*([^\s].+)$#is' => 'UPDATE $1 SET $2, ' . $update,
            '#^\s*update\s+(.+)\s+set\s*$#is' => 'UPDATE $1 SET ' . $update,
        ));
    }

    public function insert($key, $value) {
        return $this->sqlReplace([
            '#^\s*insert\s+into\s+([^\s]+)\s*\(([^\)]+)\)\s*values\s*\(([^\)]+)\)\s*$#is' => 'INSERT INTO $1 ($2, ' . $this->database->identifier($key) . ') VALUES ($3, ' . $this->database->quote($value) . ')',
            '#^\s*insert\s+into\s+([^\s]+)\\s*$#is' => 'INSERT INTO $1 (' . $this->database->identifier($key) . ') VALUES (' . $this->database->quote($value) . ')',
        ], false, true);
    }

    public function onDuplicateKeyUpdate($key, $value) {

        return $this->sqlReplace(array(
            '#^\s*insert\s+into\s+([^\s]+)\s*\(([^\)]+)\)\s*values\s*\(([^\)]+)\)\s*on\s*duplicate\s*key\s*update\s*(.+=.+)\s*$#is' => 'INSERT INTO $1 ($2) VALUES ($3) ON DUPLICATE KEY UPDATE $4, ' . $this->database->identifier($key) . ' = ' . $this->database->quote($value),
            '#^\s*insert\s+into\s+([^\s]+)\s*\(([^\)]+)\)\s*values\s*\(([^\)]+)\)\s*on\s*duplicate\s*key\s*update\s*$#is' => 'INSERT INTO $1 ($2) VALUES ($3) ON DUPLICATE KEY UPDATE ' . $this->database->identifier($key) . ' = ' . $this->database->quote($value),
            '#^\s*insert\s+into\s+([^\s]+)\s*\(([^\)]+)\)\s*values\s*\(([^\)]+)\)\s*$#is' => 'INSERT INTO $1 ($2) VALUES ($3) ON DUPLICATE KEY UPDATE ' . $this->database->identifier($key) . ' = ' . $this->database->quote($value),
        ), false, true);
    }

    public function orderBy($orderBy) {

        $orderBy = is_array($orderBy) ? implode(', ', $orderBy) : $orderBy;

        return $this->sqlReplace(array(
            '#^(.+)\s+order\s+by\s.*$#is' => '$1 ORDER BY ' . $orderBy,
            '#^(.+)\s+limit\s+(.+)$#is' => '$1 ORDER BY ' . $orderBy . ' LIMIT $2',
            '#^(.+)$#s' => '$1 ORDER BY ' . $orderBy,
        ));
    }

    public function groupBy($groupBy) {

        $groupBy = is_array($groupBy) ? implode(', ', $groupBy) : $groupBy;

        return $this->sqlReplace(array(
            '#^(.+)\s+group\s+by.*(order\s+by|limit)(.*)$#is' => '$1 GROUP BY ' . $groupBy.' $2 $3',
            '#^(.+)\s+(order\s+by|limit)(.*)$#is' => '$1 GROUP BY ' . $groupBy.' $2 $3',
            '#^(.+)$#s' => '$1 GROUP BY ' . $groupBy,
        ));
    }

    public function limit($start, $offset) {

        return $this->sqlReplace(array(
                '#^(.+)\s+LIMIT\s.*$#s' => '$1 LIMIT ' . $start . ', ' . $offset,
                '#^(.+)$#s' => '$1 LIMIT ' . $start . ', ' . $offset,
        ));
    }

    public function noLimit() {

        return $this->sqlReplace(array(
            '#^(.+)\s+LIMIT\s.*$#s' => '$1',
        ));
    }

    public function sqlReplace(array $matches = [], $packBrackets = true, $packStrings = true, $debug = false) {

        $this->pack($packBrackets, $packStrings);

        foreach ($matches as $search => $replace) {

            if (!preg_match($search, $this->sql))
                continue;
                
            $this->sql = preg_replace($search, $replace, $this->sql, 1);
            
            break;
        }

        $this->unpack();

        return $this;
    }

    public function sqlMatch(array $matches, $packBrackets = true, $packStrings = true) {

        $this->pack($packBrackets, $packStrings);

        $matched = null;

        foreach ($matches as $search => $offset) {

            if (!preg_match($search, $this->sql, $match))
                continue;

            if ($offset)
                $matched = $match[$offset];

            break;
        }

        $this->unpack();

        if (!$matched)
            throw new \Exception('no matches found in sql');

        return $matched;
    }

    private function pack($brackets = true, $strings = true) {

        if (count($this->packs))
            throw new \Exception('You can not pack an already packed statement');

        $regexps = [];

        if($brackets)
            $regexps[] = '#(\([^\)\(]*\))#';

        if($strings) {
            $regexps[] = '#(' . preg_quote(substr($this->database->quote('"'), 1, -1), '#') . ')#';
            $regexps[] = '#("[^"]*")#';
            $regexps[] = '#(' . preg_quote(substr($this->database->quote("'"), 1, -1), '#') . ')#';
            $regexps[] = "#('[^']*')#";
        }

        $i = 0;

        foreach ($regexps as $regexp) {

            while (preg_match($regexp, $this->sql, $matches)) {

                $this->packs[$i] = $matches[1];

                $this->sql = str_replace($matches[1], self::LEFT_PACK_BOUNDARY . $i . self::RIGHT_PACK_BOUNDARY, $this->sql);

                $i++;
            }
        }

        return $this;
    }

    private function unpack() {

        while (preg_match('#' . preg_quote(self::LEFT_PACK_BOUNDARY, '#') . '([0-9]+)' . preg_quote(self::RIGHT_PACK_BOUNDARY, '#') . '#is', $this->sql, $match) && array_key_exists($match[1], $this->packs))
            $this->sql = str_replace(self::LEFT_PACK_BOUNDARY . $match[1] . self::RIGHT_PACK_BOUNDARY, $this->packs[$match[1]], $this->sql);

        $this->packs = [];

        return $this;
    }

}

// Do not clause PHP tags unless it is really necessary
<?php

namespace Yab\Core;

class Database extends \PDO {

	use Factory;
    
	const EVENT_QUERY = 'Database::onQuery';

    public function quote($string, $paramtype = null) {
		
		if($string === null)
			return 'NULL';
			
        return parent::quote($string, $paramtype);

    }

    public function query($sql) {
        
        Event::getEvent()->fire(self::EVENT_QUERY, ['query' => $sql]);
        
        $result = parent::query($sql);
		
        if(!$result)
            throw new \Exception('SQL Error: '.implode(' ', $this->errorInfo()));

		return $result;

    }
    
    final public function tablePrimary($table) {

		$primary = [];
	
        foreach($this->tableColumns($table) as $column) {

            if($this->columnIsPrimary($column))
                array_push($primary, $this->columnName($column));
            
        }

        return $primary;

    }
		
    final public function tableColumns($table) {
		
		$driver = $this->getAttribute(\PDO::ATTR_DRIVER_NAME);
		
		switch($driver) {
			
			case 'mysql':
				return $this->statement('DESC '.$this->identifier($table));
			
		}
		
		throw new \Exception('columnIsSequence not implemented for driver "'.$driver.'"');

    }
		
    final public function tableColumnNames($table) {
		
		$driver = $this->getAttribute(\PDO::ATTR_DRIVER_NAME);
		
		switch($driver) {
			
			case 'mysql':
				return $this->tableColumns($table)->fetchValue('Field');
			
		}
		
		throw new \Exception('columnIsSequence not implemented for driver "'.$driver.'"');
        
    }
		
    final public function columnIsPrimary(array $column) {
		
		$driver = $this->getAttribute(\PDO::ATTR_DRIVER_NAME);
		
		switch($driver) {
			
			case 'mysql':
				return $column['Key'] == 'PRI';
			
		}
		
		throw new \Exception('columnIsSequence not implemented for driver "'.$driver.'"');
        
    }
		
    final public function columnName(array $column) {
		
		$driver = $this->getAttribute(\PDO::ATTR_DRIVER_NAME);
		
		switch($driver) {
			
			case 'mysql':
				return $column['Field'];
			
		}
		
		throw new \Exception('columnName not implemented for driver "'.$driver.'"');
        
    }
		
    final public function columnIsSequence(array $column) {
		
		$driver = $this->getAttribute(\PDO::ATTR_DRIVER_NAME);
		
		switch($driver) {
			
			case 'mysql':
				return $column['Extra'] == 'auto_increment';
			
		}
		
		throw new \Exception('columnIsSequence not implemented for driver "'.$driver.'"');

    }
	
	final public function showDatabaseName() {
		
		$driver = $this->getAttribute(\PDO::ATTR_DRIVER_NAME);
		
		switch($driver) {
			
			case 'mysql':
				return $this->statement('SELECT DATABASE();')->fetchValue(0)->next();
			
		}
		
		throw new \Exception('showDatabaseName not implemented for driver "'.$driver.'"');
		
		
		
	}
	
	final public function showDatabases() {
		
		$driver = $this->getAttribute(\PDO::ATTR_DRIVER_NAME);
		
		switch($driver) {
			
			case 'mysql':
				return $this->statement('SHOW DATABASES;')->fetchValue('Database');
			
		}
		
		throw new \Exception('showDatabases not implemented for driver "'.$driver.'"');
		
	}
    
	final public function showTables() {
		
		$driver = $this->getAttribute(\PDO::ATTR_DRIVER_NAME);
		
		switch($driver) {
			
			case 'mysql':
				return $this->statement('SHOW TABLES;')->fetchValue(0);
			
		}
		
		throw new \Exception('showTables not implemented for driver "'.$driver.'"');
		
	}
	
    final public function select($table) {

        return $this->statement('SELECT * FROM ' . $this->identifier($table));
    }

    final public function update($table) {

        return $this->statement('UPDATE ' . $this->identifier($table) . ' SET ');
    }

    final public function insert($table) {

        return $this->statement('INSERT INTO ' . $this->identifier($table));
    }

    final public function delete($table) {

        return $this->statement('DELETE FROM ' . $this->identifier($table));
    }

    final public function statement($sql) {

        return new Statement($this, $sql);
    }

    final public function identifier($value) {

        $values = is_array($value) ? $value : array($value);

        foreach ($values as $key => $value) {

            $prefix = '';
            $alias = '';

            $parts = explode('.', (string) $value);

            if (1 < count($parts))
                $prefix = array_shift($parts) . '.';

            $field = implode('.', $parts);

            $parts = preg_split('#\s+#', $field);

            if (1 < count($parts))
                $alias = ' ' . array_shift($parts);

            $field = implode(' ', $parts);

            $values[$key] = $prefix . ($field == '*' ? '*' : '`' . $field . '`') . $alias;
        }

        return implode(', ', $values);
    }

}

// Do not clause PHP tags unless it is really necessary

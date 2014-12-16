<?php if ( ! defined('SZ_EXEC') ) exit('access_denied');

/**
 * ====================================================================
 * 
 * Seezoo-Framework
 * 
 * A simple MVC/action Framework on PHP 5.1.0 or newer
 * 
 * 
 * MVC model class
 * 
 * @package  Seezoo-Framework
 * @category classes
 * @author   Yoshiaki Sugimoto <neo.yoshiaki.sugimoto@gmail.com>
 * @license  MIT Licence
 * 
 * ====================================================================
 */

class SZ_Kennel
{
	protected $table;
	
	
	public function __construct()
	{
		// Database autoloaded if "db" property is declared
		if ( property_exists($this, 'db') )
		{
			$this->_loadDatabase();
		}
	}
	
	
	public function __get($name)
	{
		if ( $name === 'db' )
		{
			return Seezoo::$Importer->database();
		}
		else if ( preg_match('/'. implode('|', Seezoo::getSuffix('model')) . '$/', $name) )
		{
			return Seezoo::$Importer->model(Seezoo::removePrefix($name));
		}
	}
	
	
	// ---------------------- Short-cut Database SQL methods --------------------------- //
	
	
	/**
	 * Get One column data
	 * 
	 * @access public
	 * @param  string $column
	 * @param  array $conditions
	 * @throws Exception
	 * @return mixed
	 */
	public function findOne($column, $conditions = array())
	{
		$this->_loadDatabase();
		if ( empty($this->table)
		     || ! $this->db->isAllowedTableName($this->table) )
		{
			throw new Exception('Primary table is not specified.');
		}
		
		$column = $this->db->prepColumn($column);
		$query  = $this->_execFindQuery($column, $conditions, 1);
		if ( $query && $query->row() )
		{
			$result = $query->row();
			return $result->{$column};
		}
		return FALSE;
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * Get One result row
	 * 
	 * @access public
	 * @param  mixed $columns
	 * @param  array $conditions
	 * @param  int $fetchMode
	 * @throws Exception
	 * @return mixed
	 */
	public function find($columns    = '*',
	                     $conditions = array(),
	                     $fetchMode  = PDO::FETCH_OBJ)
	{
		$this->_loadDatabase();
		if ( empty($this->table)
		     || ! $this->db->isAllowedTableName($this->table) )
		{
			throw new Exception('Primary table is not specified.');
		}
		
		$columns = $this->db->prepColumn($columns);
		$query = $this->_execFindQuery($columns, $conditions);
		if ( $query && $query->numRows() > 0 )
		{
			return ( $fetchMode == PDO::FETCH_OBJ )
			         ? $query->row()
			         : $query->rowArray();
		}
		return FALSE;
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * Get indexed result row
	 * 
	 * @access public
	 * @param  mixed $columns
	 * @param  array $conditions
	 * @param  int $index
	 * @param  int $fetchMode
	 * @throws Exception
	 * @return mixed
	 */
	public function findAt($index      = 0,
	                       $columns    = '*',
	                       $conditions = array(),
	                       $fetchMode  = PDO::FETCH_OBJ)
	{
		$this->_loadDatabase();
		if ( empty($this->table)
		     || ! $this->db->isAllowedTableName($this->table) )
		{
			throw new Exception('Primary table is not specified.');
		}
		
		$query = $this->_execFindQuery($columns, $conditions, 1, $index);
		if ( $query && $query->numRows() > 0 )
		{
			return ( $fetchMode == PDO::FETCH_OBJ )
			         ? $query->row()
			         : $query->rowArray();
		}
		return FALSE;
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * Get results short cut
	 * 
	 * @access public
	 * @param  mixed $columns
	 * @param  array $conditions
	 * @param  int $fetchMode
	 * @throws Exception
	 * @return mixed
	 */
	public function findAll($columns    = '*',
	                        $conditions = array(),
	                        $fetchMode  = PDO::FETCH_OBJ)
	{
		$this->_loadDatabase();
		if ( empty($this->table)
		     || ! $this->db->isAllowedTableName($this->table) )
		{
			throw new Exception('Primary table is not specified.');
		}
		
		$query = $this->_execFindQuery($columns, $conditions, 0);
		if ( $query && $query->numRows() > 0 )
		{
			return ( $fetchMode == PDO::FETCH_OBJ )
			         ? $query->result()
			         : $query->resultArray();
		}
		return FALSE;
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * Execute shortcut SQL
	 * 
	 * @access protected
	 * @param  mixed $columns
	 * @param  array $conditions
	 * @param  int $limit
	 * @param  int $offset
	 * @return mixed
	 */
	protected function _execFindQuery($columns,
	                                  $conditions,
	                                  $limit  = 1,
	                                  $offset = 0)
	{
		if ( ! is_array($columns) )
		{
			$columns = ( ! $columns ) ? array('*') : explode(',', $columns);
		}
		$columns      = array_map(array($this->db, 'prepColumn'), $columns);
		$selectColumn = ( count($columns) > 0 ) ? implode(', ', $columns) : '*';
		$bindData     = array();
		$sql =
				'SELECT '
				. $selectColumn . ' '
				.'FROM '
				. $this->db->prefix() . $this->table . ' ';
		
		if ( count($conditions) > 0 )
		{
			$where = array();
			foreach ( $conditions as $col => $val )
			{
				$stb = $this->db->buildOperatorStatement($col, $val);
				if ( is_array($stb) )
				{
					$where[] = $stb[0];
					if ( is_array($stb[1]) )
					{
						foreach ( $stb[1] as $bind )
						{
							$bindData[] = $bind;
						}
					}
					else
					{
						$bindData[] = $stb[1];
					}
				}
				else
				{
					$where[] = $stb;
				}
			}
			$sql .= 'WHERE ' . implode(' AND ', $where) . ' ';
		}
		if ( $limit > 0 )
		{
			$sql .= 'LIMIT ' . $limit . ' ';
		}
		if ( $offset > 0 )
		{
			$sql .= 'OFFSET ' . $offset;
		}
		return  $this->db->query($sql, $bindData);
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * Load a database instance
	 * 
	 * @access protected
	 */
	protected function _loadDatabase()
	{
		if ( ! $this->db )
		{
			$this->db = Seezoo::$Importer->database();
		}
	}
}
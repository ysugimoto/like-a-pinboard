<?php if ( ! defined('SZ_EXEC') ) exit('access_denied');

/**
 * ====================================================================
 * 
 * Seezoo-Framework
 * 
 * A simple MVC/action Framework on PHP 5.1.0 or newer
 * 
 * Database forge
 * 
 * @package  Seezoo-Framework
 * @category Libraries
 * @author   Yoshiaki Sugimoto <neo.yoshiaki.sugimoto@gmail.com>
 * @license  MIT Licence
 * 
 * ====================================================================
 */

class SZ_Databaseforge implements Singleton
{
	protected $db;
	
	protected $_columns = array();
	
	protected $_defaultColumn = array(
		'null'    => FALSE,
		'comment' => '',
		'default' => '',
		'index'   => FALSE
	);
	
	public function __construct()
	{
		$this->db = Seezoo::$Importer->database();
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * Preset column info for creata table
	 * 
	 * @access public
	 * @param  array $column
	 * @throws Exception
	 */
	public function setColumn($column)
	{
		if ( ! is_array($column) )
		{
			throw new Exception('Add column data must be array!');
		}
		else if ( isset($column[0]) && is_array($column[0]) )
		{
			return array_map(array($this, 'setColumn'), $column);
		}
		
		if ( ! isset($column['name']) || ! isset($column['type']) )
		{
			throw new Exception('Column info is not enough!');
		}
		
		$this->_columns[] = array_merge($this->_defaultColumn, $column);
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * Create table in database
	 * 
	 * @access public
	 * @param  string $tableName
	 * @param  bool $ifNotExists
	 * @return bool
	 */
	public function createTable($tableName, $ifNotExists = TRUE)
	{
		if ( ! $this->db->isAllowedTableName($tableName) || count($this->_columns) === 0 )
		{
			return FALSE;
		}
		$indexes = array();
		
		$sql[] = 'CREATE TABLE' . (( $ifNotExists ) ? ' IF NOT EXISTS' : '');
		$sql[] = $tableName;
		$sql[] = '(';
		foreach ( $this->_columns as $column )
		{
			$sql[] = $this->db->prepColumn($column['name']);
			$sql[] = strtoupper($column['type']) . (( isset($column['size']) ) ? '(' . $column['size'] . ')' : '');
			$sql[] = ( $column['null'] ) ? 'NULL' : 'NOT NULL';
			if ($column['index'] !== FALSE )
			{
				if ( $column['index'] === 'primary' )
				{
					if ( isset($column['autoincrement']) && $column['autoincrement'] === TRUE )
					{
						$sql[] = 'AUTO_INCREMENT';
					}
					$sql[] = 'PRIMARY KEY';
				}
				else
				{
					$indexes[] = $this->db->prepColumn($column['name']);
				}
			}
			if ( $column['default'] !== '' )
			{
				$sql[] = "DEFAULT '" . $column['default'] . "'";
			}
			
			if ( ! empty($column['comment']) )
			{
				$sql[] = "COMMENT '" . $column['comment'] . "'";
			}
			$sql[] = ',';
		}
		
		if ( count($indexes) > 0 )
		{
			$sql[] = 'INDEX ( ' . implode(',', $indexes) . ' )';
		}
		else
		{
			array_pop($sql);
		}
		
		$sql[] = ');';
		$this->_columns = array();
		return (bool)$this->db->query(implode(' ', $sql));
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * Add column to table
	 * 
	 * @access public
	 * @param  string $table
	 * @param  array $column
	 * @return bool
	 */
	public function addColumn($table, $column = array())
	{
		if ( $this->db->isAllowedTableName($table) || empty($column) )
		{
			return FALSE;
		}
		
		$sql =
		      'ALTER TABLE '
		      . $this->db->prefix() . $table .' '
		      . 'ADD '
		      . $this->_makeColumnData($column);
		
		return (bool)$this->db->query($sql);
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * Column midification
	 * 
	 * @access public
	 * @param  string $table
	 * @param  string $name
	 * @param  array $column
	 * @return bool
	 */
	public function modifyColumn($table, $name, $column = array())
	{
		if ( $this->db->isAllowedTableName($table) || empty($column) )
		{
			return FALSE;
		}
		
		$sql =
		      'ALTER TABLE '
		      . $this->db->prefix() . $table .' '
		      .'CHANGE '
		      . $this->db->prepColumn($name) . ' '
		      . $this->_makeColumnData($column);
		
		return (bool)$this->db->query($sql);
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * Drop column from table
	 * 
	 * @access public
	 * @param  string $table
	 * @param  string $name
	 * @return bool
	 */
	public function dropColumn($table, $name)
	{
		if ( $this->db->isAllowedTableName($table) || empty($column) )
		{
			return FALSE;
		}
		
		$sql =
		      'ALTER TABLE '
		      . $this->db->prefix() . $table . ' '
		      .'DROP '
		      . $this->db->prepColumn($name);
		
		return (bool)$this->db->query($sql);
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * Make add/modify column SQL
	 * 
	 * @access protected
	 * @param  array $column
	 * @return string
	 */
	protected function _makeColumnData($column)
	{
		$columnInfo  = $column['type'];
		$columnInfo .= ( isset($column['size']) ) ? '(' . (int)$columnInfo['size'] . ') ' : ' ';
		$columnInfo .= ( isset($column['null']) && $column['null'] === TRUE ) ? 'NULL ' : 'NOT NULL ';
		$columnInfo .= ( isset($column['default']) ) ? 'DEFAULT \'' . $column['default']  . '\'' : '';
		
		return $columnInfo;
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * Escape character
	 * 
	 * @access protected
	 * @param  string $str
	 * @param  string $char
	 * @return string
	 */
	protected function escape($str, $char = "'")
	{
		return str_replace($char, "\\" . $char, $str);
	}
}
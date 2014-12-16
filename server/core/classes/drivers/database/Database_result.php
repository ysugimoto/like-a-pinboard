<?php if ( ! defined('SZ_EXEC') ) exit('access_denied');

/**
 * ====================================================================
 * 
 * Seezoo-Framework
 * 
 * A simple MVC/action Framework on PHP 5.1.0 or newer
 * 
 * Database Result wrapper class ( PDO driver )
 * 
 * @package  Seezoo-Framework
 * @category Drivers
 * @author   Yoshiaki Sugimoto <neo.yoshiaki.sugimoto@gmail.com>
 * @license  MIT Licence
 * 
 * ====================================================================
 */

class SZ_Database_result implements Iterator, ArrayAccess
{
	/**
	 * PDOStatement
	 * @var PDOStatement
	 */
	protected $_stmt;
	
	protected $_pointer;
	protected $_fetchMode;
	protected $_currentResult;
	
	protected $_resultObject;
	
	
	public function __construct($statement)
	{
		$this->_stmt      = $statement;
		$this->_pointer   = 0;
		$this->_fetchMode = PDO::FETCH_OBJ;
	}
	
	
	// Iterator need implement methods ===========
	
	public function rewind()
	{
		$this->_pointer = 0;
	}
	
	public function current()
	{
		$result = $this->result();
		return $result[$this->_pointer];
	}
	
	public function key()
	{
		return $this->_pointer;
	}
	
	public function next()
	{
		++$this->_pointer;
	}
	
	public function valid()
	{
		$result = $this->result();
		return isset($result[$this->_pointer]);
	}
	
	// ArrayAccess need implement methods ========
	
	public function offsetExists($offset)
	{
		$result = $this->result();
		return isset($result[$offset]);
	}
	
	public function offsetGet($offset)
	{
		$result = $this->result();
		return $result[$offset];
	}
	
	public function offsetSet($offset, $value)
	{
		// nothing to do.
	}
	
	public function offsetUnset($offset)
	{
		// nothing to do.
	}
	
	// --------------------------------------------------
	
	
	/**
	 * Get native PDOStatement object
	 * 
	 * @access public
	 * @return object PDOStatement
	 */
	public function get()
	{
		return $this->_stmt;
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * get result objects
	 * 
	 * @access public
	 * @return array
	 */
	public function result()
	{
		if ( ! $this->_resultObject )
		{
			$this->_resultObject = $this->_stmt->fetchAll(PDO::FETCH_OBJ);
		}
		return $this->_resultObject;
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * get result array
	 * 
	 * @access public
	 * @return array
	 */
	public function resultArray()
	{
		return array_map('get_object_vars', $this->result());
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * get result row count
	 * 
	 * @access public
	 * @return int
	 */
	public function numRows()
	{
		return count($this->result());
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * get single result object
	 * 
	 * @access public
	 * @param  int $index
	 * @return object
	 */
	public function row($index = 0)
	{
		return ( isset($this[$index]) )
		         ? $this[$index]
		         : NULL;
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * get single result array
	 * 
	 * @access public
	 * @param  int $index
	 * @return array
	 */
	public function rowArray($index = 0)
	{
		return ( isset($this[$index]) )
		         ? get_object_vars($this[$index])
		         : NULL;
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * get SQL affected rows
	 * 
	 * @access public
	 * @return int
	 */
	public function affectedRows()
	{
		return $this->_stmt->rowCount();
	}
}
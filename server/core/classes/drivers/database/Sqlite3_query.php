<?php if ( ! defined('SZ_EXEC') ) exit('access_denied');

/**
 * ====================================================================
 * 
 * Seezoo-Framework
 * 
 * A simple MVC/action Framework on PHP 5.1.0 or newer
 * 
 * 
 * SQLite3 database driver fixes SQL builder
 * 
 * @package  Seezoo-Framework
 * @category drivers
 * @author   Yoshiaki Sugimoto <neo.yoshiaki.sugimoto@gmail.com>
 * @license  MIT Licence
 * 
 * ====================================================================
 */
class SZ_Sqlite3_query extends SZ_Database_driver
{
	
	/**
	 * Returns get table list SQL
	 * 
	 * @access public
	 * @param  string $dbname
	 * @param  string $prefix
	 * @return string
	 */
	public function tableListQuery($dbname, $prefix)
	{
		return "SELECT * FROM sqlite_master WHERE type <> 'index';";
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * Returns get column list SQL
	 * 
	 * @access public
	 * @param  string $table
	 * @return string
	 */
	public function columnListQuery($table)
	{
		return "PRAGMA table_info('" . $table . "');";
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * Returns Field-formatted object
	 * 
	 * @access public
	 * @param  object $field
	 * @return object
	 */
	public function convertField($field)
	{
		$obj = new stdClass;
		$obj->field = $field->name;
		$obj->key = ( $field->pk > 0 ) ? TRUE : FALSE;
		$obj->type = strtoupper($field->type);
		return $obj;
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * Returns Table string
	 * 
	 * @access public
	 * @param  object $tables
	 * @return string
	 */
	public function convertTable($table)
	{
		return $table[2];
	}
}

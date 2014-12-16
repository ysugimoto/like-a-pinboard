<?php if ( ! defined('SZ_EXEC') ) exit('access_denied');

/**
 * ====================================================================
 * 
 * Seezoo-Framework
 * 
 * A simple MVC/action Framework on PHP 5.1.0 or newer
 * 
 * 
 * SQLite2 database driver fixes SQL builder
 * 
 * @package  Seezoo-Framework
 * @category drivers
 * 
 * ====================================================================
 */
 
### Will be implemented soon ###

class SZ_Sqlite2_query extends SZ_Database_driver
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
	
	}
}

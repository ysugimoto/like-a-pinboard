<?php if ( ! defined('SZ_EXEC') ) exit('access_denied');

/**
 * ====================================================================
 * 
 * Seezoo-Framework
 * 
 * A simple MVC/action Framework on PHP 5.1.0 or newer
 * 
 * 
 * MySQL database driver fixes SQL builder
 * 
 * @package  Seezoo-Framework
 * @category drivers
 * @author   Yoshiaki Sugimoto <neo.yoshiaki.sugimoto@gmail.com>
 * @license  MIT Licence
 * 
 * ====================================================================
 */
class SZ_Mysql_query extends SZ_Database_driver
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
		return 'SHOW TABLES';
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
		return 'SHOW COLUMNS FROM ' . $table . ';';
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
		$obj->field = $field->Field;
		$obj->key   = ( $field->Key === 'PRI' ) ? TRUE : FALSE;
		if ( FALSE !== ($point = strpos($field->Type, '(')) )
		{
			$type = strtoupper(substr($field->Type, 0, $point));
		}
		else
		{
			$type = strtoupper($field->Type);
		}
		$obj->type = $type;
		return $obj;
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * Returns Table string
	 * 
	 * @access public
	 * @param  object $table
	 * @return string
	 */
	public function convertTable($table)
	{
		return $table[0];
	}
}

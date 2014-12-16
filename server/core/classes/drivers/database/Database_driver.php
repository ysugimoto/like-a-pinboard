<?php if ( ! defined('SZ_EXEC') ) exit('access_denied');

/**
 * ====================================================================
 * 
 * Seezoo-Framework
 * 
 * A simple MVC/action Framework on PHP 5.1.0 or newer
 * 
 * 
 * Some database driver fixes SQL builder
 * 
 * @package  Seezoo-Framework
 * @category drivers
 * @author   Yoshiaki Sugimoto <neo.yoshiaki.sugimoto@gmail.com>
 * @license  MIT Licence
 * 
 * ====================================================================
 */

abstract class SZ_Database_driver
{
	/**
	 * Abstract
	 * Returns get table list SQL
	 * 
	 * @access public
	 * @param  string $dbname
	 * @param  string $prefix
	 * @return string
	 */
	abstract public function tableListQuery($dbname, $prefix);
	
	
	// --------------------------------------------------
	
	
	/**
	 * Abstract
	 * Returns get column list SQL
	 * 
	 * @access public
	 * @param  string $table
	 * @return string
	 */
	abstract public function columnListQuery($table);
	
	
	// --------------------------------------------------
	
	
	/**
	 * Abstract
	 * Returns Field-formatted object
	 * 
	 * @access public
	 * @param  object $field
	 * @return object
	 */
	abstract public function convertField($field);
	
	
	// --------------------------------------------------
	
	
	/**
	 * Abstract
	 * Returns Table string
	 * 
	 * @access public
	 * @param  object $tables
	 * @return string
	 */
	abstract public function convertTable($tables);
}

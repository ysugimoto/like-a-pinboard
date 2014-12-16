<?php if ( ! defined('SZ_EXEC') ) exit('access_denied');

/**
 * ====================================================================
 * 
 * Seezoo-Framework
 * 
 * A simple MVC/action Framework on PHP 5.1.0 or newer
 * 
 * 
 * Postgres database driver fixes SQL builder
 * 
 * @package  Seezoo-Framework
 * @category drivers
 * @author   Yoshiaki Sugimoto <neo.yoshiaki.sugimoto@gmail.com>
 * @license  MIT Licence
 * 
 * ====================================================================
 */
class SZ_Postgres_query extends SZ_Database_driver
{
	/**
	 * Columns have PRIMARY KEY field
	 * @var array
	 */
	protected $_primaryList;
	
	
	// --------------------------------------------------
	
	
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
		$sql = "SELECT table_name FROM information_schema.tables WHERE table_schema = 'public'";
		if ( $prefix !== '' )
		{
			$sql .= " AND table_name LIKE '" . $prefix . "%' ESCAPE '!';";
		}
		
		return $sql;
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
		$sql = 'SELECT '
				.	'column_name as field, '
				.	'data_type as type '
				.'FROM '
				.	'information_schema.columns '
				.'WHERE '
				.	"table_name = '" . $table . "'";
		
		$db         = Seezoo::$Importer->database();
		
		// Get temporary primary key fields
		$primaries  = array();
		$primarySQL = 
						'SELECT '
						.	'A.attname as field '
						.'FROM '
						.	'pg_attribute as A '
						.'INNER JOIN pg_stat_user_tables as S ON '
						.	'A.attrelid = S.relid '
						.	"AND S.schemaname = 'public' "
						.	"AND S.relname = '" . $table . "' "
						.'INNER JOIN pg_constraint as CONS ON '
						.	'A.attnum = ANY(CONS.conkey) '
						.	"AND CONS.contype = 'p' "
						.'AND CONS.conrelid = S.relid ';
		$query = $db->query($primarySQL)->get();
		foreach ( $query as $row )
		{
			$primaries[] = $row['field'];
		}
		$this->_primaryList = $primaries;
		return $sql;
		//return "SELECT column_name, data_type FROM information_schema.columns WHERE table_name = '" . $table . "'";
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
		$obj        = new stdClass;
		$obj->field = $field->field;
		$obj->type  = strtoupper($field->type);
		$obj->key   = ( in_array($field->field, $this->_primaryList) ) ? TRUE : FALSE;
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
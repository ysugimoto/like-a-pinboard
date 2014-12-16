<?php if ( ! defined('SZ_EXEC') ) exit('access_denied');

/**
 * ====================================================================
 * 
 * Seezoo-Framework
 * 
 * A simple MVC/action Framework on PHP 5.1.0 or newer
 * 
 * 
 * Database class ( PDO driver )
 * 
 * @package  Seezoo-Framework
 * @category Core
 * @author   Yoshiaki Sugimoto <neo.yoshiaki.sugimoto@gmail.com>
 * @license  MIT Licence
 * 
 * ====================================================================
 */

/**
 * Extra simple Access class
 */
class DB implements Growable, Singleton
{
	private static $_dbClass;
	
	public static function connect($dsn = 'default', $dsnConnection = FALSE)
	{
		if ( ! self::$_dbClass )
		{
			$db = self::grow();
			self::$_dbClass = get_class($db);
		}
		
		return new self::$_dbClass($dsn, $dsnConnection);
	}
	
	public static function prefix()
	{
		return self::grow()->prefix();
	}
	
	public static function grow()
	{
		return Seezoo::$Importer->database();
	}
}


Class SZ_Database extends SZ_Driver implements Singleton
{
	/**
	 * PDO connections
	 * @var PDO resource
	 */
	private $_connectID;
	
	
	/**
	 * PDOStatement instace
	 * @var PDOStatement
	 */
	private $_statement;
	
	
	/**
	 * connection group
	 * @var string
	 */
	private $_group;
	
	
	/**
	 * transaction status
	 * @var bool
	 */
	private $_isTrans = FALSE;
	
	
	/**
	 * database connection info
	 * @var array
	 */
	private $_info;
	
	
	/**
	 * database allowed table characters ( white list )
	 * @var string
	 */
	private $_allowedTableCharacter = "0-9a-zA-Z_,\s";
	
	
	/**
	 * Environment class instance
	 * @var Environment
	 */
	protected $env;
	
	
	/**
	 * Benchmark stack
	 * @var array
	 */
	protected $_stackBench;
	
	
	/**
	 * table list cache
	 * @var array
	 */
	protected $_tablesCache;
	
	
	/**
	 * field list cache
	 * @var array
	 */
	protected $_fieldsCache = array();
	
	
	/**
	 * query log stack
	 * @var array
	 */
	protected $_queryLog = array();
	
	
	/**
	 * Query result className
	 * @var string
	 */
	protected $_resultClass;
	
	
	/**
	 * Flag of manual connection
	 * @var bool
	 */
	protected $_manualConnect = FALSE;
	
	
	/**
	 * DSN string format list
	 * @var array
	 */
	protected $_dsn = array(
		'mysql'    => 'mysql:host=%s;dbname=%s;port=%s',
		'postgres' => 'pgsql:host=%s;port=%s;dbname=%s',
		'sqlite2'  => 'sqlite2:%s',
		'sqlite3'  => 'sqlite:%s',
		'odbc'     => 'odbc:Driver=%s;HOSTNAME=%s;PORT=%d;DATABASE=%s;UID=%s;PWD=%s',
		'firebird' => 'firebird:dbname=%s:%s'
	);


	public function __construct($group, $dsnConnection = FALSE)
	{
		parent::__construct();
		
		$this->env            = Seezoo::getENV();
		$this->_manualConnect = is_array($group);
		$this->_group         = $group;
		$this->_resultClass   = $this->loadDriver('Database_result', FALSE);
		
		$this->_initialize($group);
		$this->driver = $this->loadDriver(ucfirst($this->_info['driver']) . '_query');
		
		// Initial connect when auto conection
		if ( ! $this->_manualConnect )
		{
			$settings = $this->_makeConnectSettings();
			$this->_connect(array(
				'dsn'      => $settings['dsn_string'],
				'username' => $this->_info['username'],
				'password' => $this->_info['password'],
				'option'   => $settings['options']
			), $settings['dsn_only']);
		}
		// Either manual connection
		else
		{
			$this->_connect($group, $dsnConnection);
		}
	}
	
	
	
	// --------------------------------------------------
	
	
	/**
	 * database connection start
	 * 
	 * @access public
	 */
	public function _connect($connection, $dsnOnly = FALSE)
	{
		if ( $this->_connectID )
		{
			return;
		}
		
		try
		{
			if ( $dsnOnly === FALSE )
			{
				$this->_connectID = new PDO(
				                             $connection['dsn'],
				                             $connection['username'],
				                             $connection['password'],
				                             $connection['option']
				                           );
			}
			else
			{
				$this->_connectID = new PDO($connection['dsn']);
			}
			$this->_connectID->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			// below code causes PDOStatement::execute() to error and shutting down...why?
			//$this->_connectID->setAttribute(PDO::ATTR_EMULATE_PREPARES, FALSE);
			Event::addListener('shutdown', array($this, 'disconnect'));
		}
		catch ( PDOException $e )
		{
			throw $e;
		}
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * database connection close
	 * 
	 * @access public
	 */
	public function disconnect()
	{
		// PDO simply resource to null
		$this->_connectID = null;
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * Get database table prefix
	 * 
	 * @access public
	 * @return string
	 */
	public function prefix()
	{
		return ( isset($this->_info['table_prefix']) )
		         ? $this->_info['table_prefix']
		         : '';
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * get database group name
	 * 
	 * @access public
	 * @return string
	 */
	public function getGroup()
	{
		return $this->_group;
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * execute query
	 * 
	 * @access public
	 * @param  string $sql
	 * @param  array $bind
	 */
	public function query($sql, $bind = FALSE)
	{
		$this->_stackBench = $this->_bench();
		if ( is_array($bind) )
		{
			if ( strpos($sql, '?') !== FALSE ) {
				// query binding chars and bind paramter is match?
				if ( substr_count($sql, '?') !== count($bind) )
				{
					throw new PDOException('prepared statement count is not match.', SZ_ERROR_CODE_DATABASE);
				}
				
				$this->_statement = $this->_connectID->prepare($sql);
				$index = 0;
				foreach ( $bind as $val )
				{
					$this->_statement->bindValue(++$index, $val, $this->typeof($val));
				}
			}
			else if ( strpos($sql, ':') !== FALSE )
			{
				if ( ($cnt = preg_match_all('|(:[a-zA-Z0-9\-_]+)\b|u', $sql, $matches)) && $cnt !== count($bind) )
				{
					throw new PDOException('prepared statement count is not match.', SZ_ERROR_CODE_DATABASE);
				}
				
				$this->_statement = $this->_connectID->prepare($sql);
				foreach ( $matches[0] as $bindColumn )
				{
					if ( ! array_key_exists($bindColumn, $bind) )
					{
						throw new PDOException('Prepared statement "' . $bindColumn . '" is not supplied.', SZ_ERROR_CODE_DATABASE);
					}
					$this->_statement->bindValue($bindColumn, $bind[$bindColumn], $this->typeof($bind[$bindColumn]));
				}
			}
			
			try
			{
				$this->_statement->execute();
			}
			catch ( PDOException $e )
			{
				$error = '';
				if ( $this->_info['query_debug'] === TRUE )
				{
					$error = $this->_stackQueryLog($sql, $bind);
				}
				throw new PDOException($e->getMessage() . '<br />'
				                       . ' Execute SQL: ' . $error, SZ_ERROR_CODE_DATABASE);
			}
			
			// SQL debugging
			if ( $this->_info['query_debug'] === TRUE )
			{
				$this->_stackQueryLog($sql, $bind);
			}
			
			$bind = NULL;
		}
		else
		{
			if ( FALSE === ($this->_statement = $this->_connectID->query($sql)) )
			{
				$error = '';
				if ( $this->_info['query_debug'] === TRUE )
				{
					$error = $this->_stackQueryLog($sql);
				}
				throw new PDOException('SQL Failed :'
				                       . implode(', ', $this->_connectID->errorInfo()) . ' SQL : ' . $error, SZ_ERROR_CODE_DATABASE);
			}
			
			if ( $this->_info['query_debug'] === TRUE )
			{
				$this->_stackQueryLog($sql);
			}
			
		}
		
		// returns database Result
		return $this->_createResult();
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * Create Result statement
	 * 
	 * @access public
	 * @return DatabaseResult
	 */
	protected function _createResult()
	{
		return new $this->_resultClass($this->_statement);
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * Insert record
	 * 
	 * @access public
	 * @param  string $table
	 * @param  array $data
	 * @param  bool  $returnInsertID
	 * @return mixed
	 */
	public function insert($table, $data = array(), $returnInsertID = FALSE)
	{
		if ( ! $this->isAllowedTableName($table) )
		{
			return FALSE;
		}
		
		if ( count($data) === 0 )
		{
			throw new Exception('Insert values have not be empty!', SZ_ERROR_CODE_DATABASE);
			return FALSE;
		}
		// build query
		$columns    = array();
		$statements = array();
		$bindData   = array();
		foreach ( $data as $column => $value )
		{
			$columns[]    = $this->prepColumn($column);
			$statements[] = '?';
			$bindData[]   = $value;
			
		}
		$sql = sprintf(
					'INSERT INTO %s (%s) VALUES (%s);', 
					$this->prefix() . $table,
					implode(', ', $columns),
					implode(', ', $statements)
				);
		$query = $this->query($sql, $bindData);
		//GC
		$columns = $statements = $bindData = $data = NULL;
		
		return ( $returnInsertID ) ? $this->insertID() : $query;
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * Update record
	 * 
	 * @access public
	 * @param  string $table
	 * @param  array $data
	 * @param  array $where
	 * @throws Exception
	 */
	public function update($table, $data = array(), $where = FALSE)
	{
		if ( ! $this->isAllowedTableName($table) )
		{
			return FALSE;
		}
				
		if ( count($data) === 0 )
		{
			throw new Exception('Update values have not be empty!', SZ_ERROR_CODE_DATABASE);
			return FALSE;
		}
		// build query
		$statements = array();
		$bindData   = array();
		
		foreach ( $data as $column => $value )
		{
			$statements[] = $column . ' = ? ';
			$bindData[]   = $value;
		}
		$sql = sprintf(
					'UPDATE %s SET %s',
					$this->prefix() . $table,
					implode(', ', $statements)
				);
		
		// Is limited update?
		if ( is_array($where) )
		{
			$statements = array();
			$this->_buildConditionStatement($where, $statements, $bindData);
			$sql .= ' WHERE ' . implode(' AND ', $statements);
		}
		else if ( is_string($where) )
		{
			$sql .= ' WHERE ' . $where;
		}
		
		// GC
		$statements = $data = $where = NULL;
		
		return $this->query($sql, $bindData);
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * Delete record
	 * 
	 * @access public
	 * @param  string $table
	 * @param  array $where
	 */
	public function delete($table, $where = array())
	{
		if ( ! $this->isAllowedTableName($table) )
		{
			return FALSE;
		}
		
		// build query
		$bindData = array();
		$sql      = 'DELETE FROM ' . $this->prefix() . $table;

		if ( ! empty($where) )
		{
			$statements = array();
			$this->_buildConditionStatement($where, $statements, $bindData);
			$sql .= ' WHERE ' . implode(' AND ', $statements);
		}
		
		return $this->query($sql, ( count($bindData) > 0 ) ? $bindData : FALSE);
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * get table lists
	 * 
	 * @access public
	 * @param  string $table
	 */
	public function tables()
	{
		if ( ! $this->_tablesCache )
		{
			$sql   = $this->driver->tableListQuery($this->_info['dbname'], $this->prefix());
			$query = $this->_connectID->query($sql);
			$this->_tablesCache = array();
			foreach ( $query->fetchAll(PDO::FETCH_BOTH) as $tables )
			{
				$this->_tablesCache[] = $this->driver->convertTable($tables);
			}
		}
		
		return $this->_tablesCache;
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * check table exists
	 * 
	 * @access public
	 * @param  string $table
	 */
	public function tableExists($table)
	{
		return in_array($this->prefix() . $table, $this->tables());
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * get field list
	 * 
	 * @access public
	 * @param  string $table
	 * @param  bool   $needPrefix
	 */
	public function fields($table, $needPrefix = FALSE)
	{
		if ( ! $this->isAllowedTableName($table) )
		{
			return FALSE;
		}
		
		if ( ! isset($this->_fieldsCache[$table]) )
		{
			$prefix = ( $needPrefix ) ? $this->prefix() : '';
			$sql    = $this->driver->columnListQuery($prefix . $table);
			$query  = $this->_connectID->query($sql);
			$this->_fieldsCache[$table] = array();
			foreach ( $query->fetchAll(PDO::FETCH_OBJ) as $column )
			{
				$column = $this->driver->convertField($column);
				$this->_fieldsCache[$table][$column->field] = $column;
			}
		}
		
		return $this->_fieldsCache[$table];
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * check field exists
	 * 
	 * @access public
	 * @param  string $fieldName
	 * @param  string $table
	 */
	public function fieldExists($fieldName, $table)
	{
		$fields = $this->fields($table);
		return ( isset($fields[$fieldName]) ) ? TRUE : FALSE;
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * start transaction
	 * 
	 * @access public
	 */
	public function transaction()
	{
		$this->_connectID->beginTransaction();
		$this->_isTrans = TRUE;
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * transaction commit
	 * 
	 * @access public
	 */
	public function commit()
	{
		if ( $this->_isTrans === FALSE )
		{
			return FALSE;
		}
		$this->_isTrans = FALSE;
		return $this->_connectID->commit();
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * transaction rollback
	 * 
	 * @access public
	 */
	public function rollback()
	{
		if ( $this->_isTrans === FALSE )
		{
			return FALSE;
		}
		$this->_isTrans = FALSE;
		return $this->_connectID->rollBack();
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * get last inserted ID
	 * 
	 * @access public
	 * @return int
	 */
	public function insertID($name = NULL)
	{
		return (int)$this->_connectID->lastInsertId($name);
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * get last prepered SQL
	 * 
	 * @access public
	 * @return string
	 */
	public function lastQuery()
	{
		return $this->_statement->queryString;
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * get query log
	 * 
	 * @access public
	 * @return array
	 */
	public function getQueryLogs()
	{
		return $this->_queryLog;
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * database initialize
	 * 
	 * @access protected
	 * @param  string $group
	 * @throws Exception
	 */
	protected function _initialize($group)
	{
		if ( ! is_array($group) )
		{
			// Database already connected?
			if ( isset($this->_connectID) && is_resource($this->_connectID) )
			{
				return;
			}
			
			$database = $this->env->getDBSettings();
			if ( ! isset($database) || ! isset($database[$group]) )
			{
				throw new Exception('Undefined database settings.', SZ_ERROR_CODE_DATABASE);
				return;
			}
			$this->_info = $database[$group];
		}
		else
		{
			$this->_info = $group;
			foreach ( array('table_prefix', 'query_debug') as $key )
			{
				if ( ! isset($this->_info[$key]) )
				{
					$this->_info[$key] = FALSE;
				}
			}
		}
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * Build where condintion statements
	 * 
	 * @access protected
	 * @param  array $where
	 * @param  array $statement ( reference )
	 * @param  array $bindData ( reference )
	 */
	protected function _buildConditionStatement($where, &$statement, &$bindData)
	{
		foreach ( $where as $column => $value )
		{
			$statementBind = $this->buildOperatorStatement($column, $value);
			if ( is_array($statementBind) )
			{
				$statement[] = $statementBind[0];
				foreach ( (array)$statementBind[1] as $bind )
				{
					$bindData[] = $bind;
				}
			}
			else
			{
				$statement[] = $statementBind;
			}
		}
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * connection settings create
	 * 
	 * @access protected
	 * @return array
	 */
	protected function _makeConnectSettings()
	{
		if ( ! isset($this->_dsn[$this->_info['driver']]) )
		{
			throw new Exception('Sysytem unsupported Driver: ' . $this->_info['driver'], SZ_ERROR_CODE_DATABASE);
			return FALSE;
		}
		$dsn = $this->_dsn[$this->_info['driver']];
		
		if ( isset($this->_info['host']) && $this->_info['host'] === 'localhost' )
		{
			$this->_info['host'] = '127.0.0.1';
		}
		$options  = array();
		$dsn_only = FALSE;
		switch ( $this->_info['driver'] )
		{
			case 'mysql':
				$dsn = sprintf($dsn, $this->_info['host'], $this->_info['dbname'], $this->_info['port']);
				$options[PDO::MYSQL_ATTR_INIT_COMMAND] = "SET NAMES utf8";
				break;
			case 'postgres':
				$dsn = sprintf($dsn, $this->_info['host'], $this->_info['port'], $this->_info['dbname']);
				break;
			case 'sqlite2':
			case 'sqlite3':
				$dsn = sprintf($dsn, rtrim($this->_info['path'], '/') . '/' . $this->_info['dbname']);
				break;
			case 'odbc':
				$dsn = sprintf($dsn,
				               $this->_info['driver_name'],
				               $this->_info['host'],
				               $this->_info['port'],
				               $this->_info['host'],
				               $this->_info['dbname'],
				               $this->_info['username'],
				               $this->_info['password']);
				$dsn_only = TRUE;
			case 'firebird':
				$dsn = sprintf($dsn, $this->_info['host'], $this->_info['path']);
				break;
			default:
				throw new PDOException('Undefiend or non-support database driver selected.', SZ_ERROR_CODE_DATABASE);
				return FALSE;
			
		}
		
		// Persistent connection enables Not windows OS and not ODBC driver.
		if ( $this->_info['pconnect'] === TRUE
		     && $this->env->isWindows === FALSE
			 && $this->_info['driver'] !== 'odbc')
		{
			$options[PDO::ATTR_PERSISTENT] = TRUE;
		}
		else
		{
			$options[PDO::ATTR_PERSISTENT] = FALSE;
		}
		return array(
			'dsn_string' => $dsn,
			'options'    => $options,
			'dsn_only'   => $dsn_only
		);
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * PDO binding parameter detection
	 * 
	 * @access protected
	 * @param $value
	 * @return PDO::PARAM_*
	 */
	protected function typeof($value)
	{
		$type = PDO::PARAM_STR;
		if ( is_int($value) )
		{
			$type =  PDO::PARAM_INT;
		}
		else if ( is_bool($value) )
		{
			$type =  PDO::PARAM_BOOL;
		}
		else if( is_null($value) )
		{
			$type =  PDO::PARAM_NULL;
		}
		else if ( is_resource($value) )
		{
			$type = PDO::PARAM_LOB;
		}
		
		return $type;
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * check tablename is allowed characters
	 * 
	 * @access public
	 * @param  string $table
	 * @return bool
	 */
	public function isAllowedTableName($table)
	{
		if ( ! preg_match('#\A[' . $this->_allowedTableCharacter . ']+\Z#u', $table) )
		{
			throw new Exception('Invalid Table name: ' . $table, SZ_ERROR_CODE_DATABASE);
			return FALSE;
		}
		return TRUE;
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * beckmarh start
	 * 
	 * @access protected
	 * @return array
	 */
	protected function _bench()
	{
		return explode(' ', microtime());
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * format and add SQL logs
	 * 
	 * @access protected
	 * @param  string $sql
	 * @param  array $bind
	 */
	protected function _stackQueryLog($sql, $bind = FALSE)
	{
		$end  = $this->_bench();
		$time = number_format(($end[0] + $end[1]) - ($this->_stackBench[0] + $this->_stackBench[1]), 4);
		
		if ( $bind !== FALSE )
		{
			if ( strpos($sql, '?') !== FALSE )
			{
				$logSQL    = '';
				$sqlPiece  = explode('?', $sql);
				$lastPiece = array_pop($sqlPiece);
				foreach ( $sqlPiece as $index => $piece )
				{
					$logSQL .= $piece . $this->_connectID->quote($bind[$index]);
				}
				$logSQL .= $lastPiece;
			}
			else if ( strpos($sql, ':') !== FALSE )
			{
				preg_match_all('|(:[a-zA-Z0-9\-_]+)\b|u', $sql, $matches);
				foreach ( $matches[0] as $column )
				{
					$sql = preg_replace('#' . $column . '#', $this->_connectID->quote($bind[$column]), $sql, 1);
				}
				$logSQL = $sql;
			}
		}
		else
		{
			$logSQL = $sql;
		}
		
		$this->_queryLog[] = array('query' => $logSQL, 'exec_time' => $time);
		return $logSQL;
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * Prepare column name
	 * 
	 * @access public
	 * @param  string $column
	 * @return string
	 */
	public function prepColumn($column)
	{
		$column = trim($column);
		if ( $column === '*' )
		{
			return $column;
		}
		if ( strpos($column, ',') !== FALSE )
		{
			$exp = explode(',', $column);
			$exp = array_map(array($this, 'prepColumn'), $exp);
			return implode(',', $ret);
		}
		else
		{
			return preg_replace('/[^0-9a-zA-Z\-_\.\s]/', '', $column);
		}
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * Parse where section and build statement
	 * 
	 * @access public
	 * @param  string $key
	 * @param  mixed  $value
	 * @return mixed
	 */
	public function buildOperatorStatement($key, $value)
	{
		$split = explode(' ', $key, 2);
		if ( count($split) > 1 )
		{
			$column = $this->prepColumn($split[0]);
			switch ( strtoupper($split[1]) )
			{
				case 'BETWEEN':
					$column .= ' BETWEEN ? AND ? ';
					break;
				case 'NOT BETWEEN':
					$column .= ' NOT BETWEEN ? AND ? ';
					break;
				default:
					$column .= ' ' . trim($split[1]) . ' ? ';
			}
			return array($column, $value);
		}
		if ( $value === 'IS NULL' )
		{
			return $this->prepColumn($key) . ' IS NULL';
		}
		else if ( $value === 'IS NOT NULL' )
		{
			return $this->prepColumn($key) . ' IS NOT NULL';
		}
		else if ( is_array($value) )
		{
			$column      = $this->prepColumn($key);
			$placeHolder = array();
			foreach ( $value as $k => $v )
			{
				// If key is string, build SQL syntax contains key
				if ( ! is_int($k) )
				{
					$column .= ' ' . strtoupper($k);
					return ( is_array($v) )
					         ? array($column, $v)
					         : array($column . ' ?', $value);
				}
				$placeHolder[] = '?';
			}
			// If values array is numbered array, build "IN" SQL
			return array(
				$column . ' IN (' . implode(', ', $placeHolder) . ')',
				$value
			);
		}
		
		return array($this->prepColumn($key) . ' = ? ', $value);
	}
}

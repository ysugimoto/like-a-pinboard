<?php

/**
 * PHP-Memcache emurate class
 * 
 * This is to emulate the class of the extension Memcache pecl.
 * However, methods for the setting on the server is not implemented. sorry.
 * In addition, only an object-oriented class.
 * Procedural functions are not implemented.
 * 
 * @author Yoshiaki Sugimoto <neo.yoshiaki.sugimoto@gmail.com>
 * @license MIT-License
 * 
 * @usage
 * you can use to create an instance in the same way as when the extension pecl.
 * 
 * <code>
 * $memcache = new Memcache;
 * $memcache->connect('localhost', 11211);
 * $memcache->set('foo', 'bar');
 * $foo = $memcache->get('foo'); // bar
 * </code>
 */

// Emurate pecl defines constants
// http://www.php.net/manual/en/memcache.constants.php
define('MEMCACHE_COMPRESSED', 2);

// Memcache Exception class
class MemcacheException extends Exception {}

// Main class implements
class Memcache
{
	/**
	 * Socket connection
	 * @var resource
	 */
	private $connection;
	
	/**
	 * CR-LF
	 * @var string
	 */
	private $CRLF         = "\r\n";
	
	/**
	 * Format of set/replace/append/prepend command
	 * @var string
	 */
	private $setterFormat = "%s %s %d %d %d\r\n%s";
	
	/**
	 * Format of get command
	 * @var string
	 */
	private $getterFormat = "%s %s";
	
	/**
	 * Format of increment/decrement command
	 */
	private $incDecFormat = "%s %s %d";
	
	
	/**
	 * Start socket connection
	 * 
	 * @access public
	 * @param  string $host
	 * @param  int $port
	 * @return bool
	 */
	public function connect($host = 'localhost', $port = 11211)
	{
		$this->connection = ( preg_match('#^unix://#', $host) )
		                      ? @fsockopen($host)
		                      : @fsockopen($host, $port, $errno, $errstr);
		
		return ( $this->connection ) ? TRUE : FALSE;
	}
	
	
	// ====================================================
	
	
	/**
	 * Start spersistent ocket connection
	 * 
	 * @access public
	 * @param  string $host
	 * @param  int $port
	 * @return bool
	 */
	public function pconnect($host = 'localhost', $port = 11211)
	{
		$this->connection = ( preg_match('#^unix://#', $host) )
		                      ? @pfsockopen($host)
		                      : @pfsockopen($host, $port, $errno, $errstr);
		
		return ( $this->connection ) ? TRUE : FALSE;
	}
	
	
	// ====================================================
	
	
	/**
	 * Close connection
	 * 
	 * @access public
	 * @return bool
	 * @note If persistent connection,
	 *       this method may be return false.
	 */
	public function close()
	{
		return @fclose($this->connection);
	}
	
	
	// ====================================================
	
	
	/**
	 * Replace value
	 * 
	 * @access public
	 * @param  string $key
	 * @param  mixed $value
	 * @param  int $compress
	 * @param  int $expire
	 * @return bool
	 */
	public function replace($key, $value, $compress = FALSE, $expire = 0)
	{
		return $this->formatCommand('replace', $key, $value, $compress, $expire);	
	}
	
	
	// ====================================================
	
	
	/**
	 * Set key-value
	 * 
	 * @access public
	 * @param  string $key
	 * @param  mixed $value
	 * @param  int $compress
	 * @param  int $expire
	 * @return bool
	 */
	public function set($key, $value, $compress = FALSE, $expire = 0)
	{
		return $this->formatCommand('set', $key, $value, $compress, $expire);
	}
	
	
	// ====================================================
	
	
	/**
	 * Set key-value if key is not exists
	 * 
	 * @access public
	 * @param  string $key
	 * @param  mixed $value
	 * @param  int $compress
	 * @param  int $expire
	 * @return bool
	 */
	public function add($key, $value, $compress = FALSE, $expire = 0)
	{
		return ( $this->get($key) === FALSE )
		         ? $this->formatCommand('set', $key, $value, $compress, $expire)
		         : FALSE;
	}
	
	
	// ====================================================
	
	
	/**
	 * Get value from key
	 * 
	 * @access public
	 * @param  string $key
	 * @param  int $flag
	 * @return mixed
	 */
	public function get($key, $flags = null)
	{
		if ( ! is_array($key) )
		{
			return $this->command(
				sprintf($this->getterFormat, 'get', $key)
			);
		}
		
		$rv = array();
		foreach ( $key as $k )
		{
			$rv[$key] = $this->get($k);
		}
		
		return $rv;
	}
	
	
	// ====================================================
	
	
	/**
	 * Delete key-value
	 * 
	 * @access public
	 * @param  string $key
	 * @param  int $timeout ( deprecated )
	 * @return bool
	 */
	public function delete($key, $timeout = 0)
	{
		return $this->command(
			sprintf($this->getterFormat, 'delete', $key)
		);
	}
	
	
	// ====================================================
	
	
	/**
	 * Increment value
	 * 
	 * @access public
	 * @param  string $key
	 * @param  mixed $value
	 * @return mixed ( int or FALSE )
	 */
	public function increment($key, $value)
	{
		return $this->command(
			sprintf($this->incDecFormat, 'incr', $key, $value)
		);
	}
	
	
	// ====================================================
	
	
	/**
	 * Decrement value
	 * 
	 * @access public
	 * @param  string $key
	 * @param  mixed $value
	 * @return mixed ( int or FALSE )
	 */
	public function decrement($key, $value)
	{
		return $this->command(
			sprintf($this->incDecFormat, 'decr', $key, $value)
		);
	}
	
	
	// ====================================================
	
	
	/**
	 * Flush all value
	 * 
	 * @access public
	 * @return bool
	 */
	public function flush()
	{
		$slab = $this->command('stats items');
		
		// Slab returns like: STAT items:1:number 6
		if ( ! preg_match('/STAT\sitems:([0-9]+):.+/', $slab, $match) )
		{
			return FALSE;
		}
		$cachedumps = $this->command("stats cachedump {$match[1]} 100", TRUE);
		foreach ( $cachedumps as $dump )
		{
			// cachedump returns like: ITEM foo [9 b; 1264464651 s]
			if ( ! preg_match('/^ITEM\s([^\s+])\s\[.+$/', $dump, $match) )
			{
				continue;
			}
			if ( ! $this->delete($match[1]) )
			{
				return FALSE;
			}
		}
		return TRUE;
	}
	
	
	// ====================================================
	
	
	/**
	 * Get server version
	 * 
	 * @access public
	 * @return string
	 */
	public function getVersion()
	{
		return $this->command('version', TRUE);
	}
	
	
	// ====================================================
	
	
	/**
	 * Format command
	 * 
	 * @access private
	 * @param  string $method
	 * @param  string $key
	 * @param  mixed $value
	 * @param  int $compress
	 * @param  int $expire
	 * @return bool
	 */
	private function formatCommand($method, $key, $value, $compress, $expire)
	{
		if (! is_int($value) && ! is_string($value) )
		{
			$value = serialize($value);
		}
		
		// Compress data if MEMCACHE_COMPRESSED flag set
		// and zlib extension enabled
		if ( $compress === MEMCACHE_COMPRESSED
		     && function_exists('gzcompress') )
		{
			$value = gzcompress($value);
		}
		
		$command = sprintf($this->setterFormat,
						$method,
						$key,
						mt_rand(0, 4294967295),
						$expire,
						strlen($value),
						$value
					);
		return $this->command($command);
	}
	
	
	// ====================================================
	
	
	/**
	 * Send command string
	 * 
	 * @access public
	 * @param  string $message
	 * @param  bool $statReturn
	 * @return mixed
	 */
	public function command($message, $statsReturn = FALSE)
	{
		fputs($this->connection, $message . "\r\n");
		
		return $this->parseResponse($statsReturn);
	}
	
	
	// ====================================================
	
	
	/**
	 * Parse server response
	 * 
	 * @access private
	 * @param  bool $statReturn
	 * @return bool
	 */
	private function parseResponse($statsReturn)
	{
		// Get response while end of sign
		do
		{
			$line = trim(fgets($this->connection, 512));
			$responses[] = $line;
		}
		while ( ! preg_match('/^(STORED|END|DELETED|NOT_FOUND|[0-9]+|VERSION\s[0-9\.]+)$/', $line)
		     && ! preg_match('/^.+_ERROR\s/', $line) );
		
		$status = trim(array_pop($responses), $this->CRLF);
		// Switch status
		switch ( $status )
		{
			// success sign
			case 'STORED':
			case 'DELETED':
				return TRUE;
			
			// error sign	
			case 'NOT_FOUND':
				return FALSE;
			
			// success and data sign
			case 'END':
				if ( $statsReturn )
				{
					return $responses;
				}
				$value = @unserialize(end($responses));
				
				if ( $value !== FALSE )
				{
					return $value;
				}
				return ( ! empty($responses) ) ? array_pop($responses) : FALSE;
			
			default:
				// server error
				if ( preg_match('/^.*ERROR$/', $status) )
				{
					return FALSE;
				}
				// version command response
				else if ( preg_match('/^VERSION\s([0-9\.]+)$/', $status, $match) )
				{
					return $match[1];
				}
				// increment/decrement response
				else if ( preg_match('/^[0-9]+$/', $status) )
				{
					return (int)$status;
				}
				return FALSE;
		}
		
	}
}

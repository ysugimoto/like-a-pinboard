<?php

/**
 * PHP-Redis client class
 * 
 * This is a client library that supports the connection with Redis Database.
 * I returned as a response and Redis command is issued.
 * Converting to those dealt with in PHP is not supported.
 * 
 * @author Yoshiaki Sugimoto <neo.yoshiaki.sugimoto@gmail.com>
 * @license MIT-License
 * 
 * @usase
 * Create an instance, connect connection() method, we issue the command.
 * 
 * <code>
 * $redis = new Redis;
 * $redis->connect('localhost', 6379);
 * $redis->set('foo', 'bar');
 * $foo = $redis->get('foo'); // bar
 * </code>
 * 
 */

// Redis Exception class
class RedisException extends Exception {}

// Main class implements
class Redis
{
	/**
	 * Socket connection
	 * @var resource
	 */
	protected $connection;
	
	/**
	 * CR-LF
	 * @var string
	 */
	protected $CRLF = "\r\n";
	
	/**
	 * Supported commands
	 * @var array
	 */
	protected $commands = array(
		'GET', 'EXISTS', 'DEL', 'SET', 'GETSET', 'MGET', 'SETNX', 'TYPE',
		'KEYS', 'RANDOMKEY', 'RENAME', 'RENAMENX', 'DBSIZE', 'EXPIRE',
		'PERSIST', 'TTL', 'SELECT', 'MOVE', 'FLUSHDB', 'FLUSHALL',
		'SETEX', 'MSET','INCR', 'INCRBY', 'DECR', 'DECRBY', 'APPEND',
		'SUBSTR', 'RPUSH', 'LPUSH', 'LLEN', 'LRANGE', 'LTRIM', 'LINDEX',
		'LSET', 'LREM', 'LPOP', 'RPOP', 'BLPOP', 'BRPOP', 'RPOPLPUSH',
		'SADD', 'SREM', 'SPOP', 'SMOVE', 'SCARD', 'SISMEMBER', 'SINTER',
		'SINTERSTORE', 'SUNION', 'SUNIONSTORE', 'SDIFF', 'SDIFFSTORE',
		'SMEMBERS', 'SRANDMEMBER', 'HSET', 'HGET', 'HMGET', 'HMSET',
		'HINCRBY', 'HEXISTS', 'HDEL', 'HKLEN', 'HKEYS', 'HVALS', 'HGETALL'
	);
	
	/**
	 * Connect to redis service
	 * 
	 * @access public
	 * @param  string $host
	 * @param  int $port
	 * @return bool
	 */
	public function connect($host = 'localhost', $port = 6379)
	{
		$this->connection = @fsockopen($host, $port, $errno, $errstr);
		
		return ( $this->connection ) ? TRUE : FALSE;
	}
	
	
	// ====================================================
	
	
	/**
	 * Quit connection
	 * 
	 * @access public
	 * @return void
	 */
	public function quit()
	{
		$this->command('QUIT');
		@fclose($this->connection);
	}
	
	
	// ====================================================
	
	
	/**
	 * Overload method
	 * 
	 * @access public
	 * @param  string $name
	 * @param  array $arguments
	 * @return string
	 * @throws RedisException
	 */
	public function __call($name, $arguments)
	{
		$command = strtoupper($name);
		if ( in_array($command, $this->commands) )
		{
			return $this->command($command, $arguments);
		}
		
		throw new RedisException($name . ' method is not declared or supported.');
	}
	
	
	// ====================================================
	
	
	/**
	 * Send command
	 * 
	 * @access public
	 * @param  string $method
	 * @param  array $params
	 * @return mixed
	 */
	public function command($method, $params = array())
	{
		$message  = '*' . (count($params) + 1) . $this->CRLF;
		$message .= '$' . strlen($method) . $this->CRLF;
		$message .= $method . $this->CRLF;
		foreach ( $params as $param )
		{
			$message .= '$' . strlen($param) . $this->CRLF;
			$message .= $param . $this->CRLF;
		}
		
		fputs($this->connection, $message);
		
		return $this->parseResponse($method);
	}
	
	
	// ====================================================
	
	
	/**
	 * Parse server response
	 * 
	 * @access private
	 * @param  string $method
	 * @return mixed
	 * @throws RedisException
	 */
	private function parseResponse($method)
	{
		$replyData = trim(fgets($this->connection, 512));
		$replyCode = substr($replyData, 0, 1);
		$reply     = substr($replyData, 1);

		switch ( $replyCode )
		{
			case '+':
			case ':':
				return $reply;

			case '-':
				throw new RedisException($reply);

			case '$':
				return $this->bulkReply($reply);

			case '*':
				return $this->multiBulkReply($reply);
		}
		
		throw new RedisException('Unexpected response returned: ' . $replyCode);
	}
	
	
	// ====================================================
	
	
	/**
	 * Parse bulk reply
	 * 
	 * @access protected
	 * @param  string $reply
	 * @return mixed
	 */
	protected function bulkReply($reply)
	{
		return ( $reply === '-1' )
		         ? NULL
		         : trim(fgets($this->connection, (int)$reply + 1));
	}
	
	
	// ====================================================
	
	
	/**
	 * Parse multi bulk reply
	 * 
	 * @access protected
	 * @param  string reply
	 * @return array
	 */
	protected function multiBulkReply($reply)
	{
		$multiBulk = array();
		for ( $i = 0; $i < $reply; ++$i )
		{
			$bulk = trim(fgets($this->connection, 512));
			$multiBulk[] = $this->bulkReply(substr($bulk, 1));
		}
		
		return $multiBulk;
	}
}

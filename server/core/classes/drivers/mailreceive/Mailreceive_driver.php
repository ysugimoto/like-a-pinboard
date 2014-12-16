<?php if ( ! defined('SZ_EXEC') ) exit('access_denied');

/**
 * ====================================================================
 * 
 * Seezoo-Framework
 * 
 * A simple MVC/action Framework on PHP 5.1.0 or newer
 * 
 * 
 * Mailreceiver driver base
 * 
 * @package  Seezoo-Framework
 * @category Drivers
 * @author   Yoshiaki Sugimoto <neo.yoshiaki.sugimoto@gmail.com>
 * @license  MIT Licence
 * 
 * ====================================================================
 */
abstract class SZ_Mailreceive_driver
{
	/**
	 * Driver settings
	 * @var array
	 */
	protected $setting = array();
	
	/**
	 * Connection socket
	 * @var resource
	 */
	protected $socket;
	
	/**
	 * Carrige return
	 * @var string
	 */
	protected $CRLF = "\r\n";
	
	/**
	 * Client-Server message log
	 * @var array
	 */
	protected $_log = array();
	
	/**
	 * Logged in flag
	 * @var bool
	 */
	protected $loggedIn = FALSE;
	
	
	/**
	 * Configure setting
	 * 
	 * @access public
	 * @param  array $conf
	 */
	public function configure($conf = array())
	{
		$this->setting = array_merge($this->setting, $conf);
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Abstruct get mails
	 * 
	 * @access public
	 * @param  int $count
	 */
	abstract public function getMail($count);
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Connect server
	 * 
	 * @access protected
	 * @return bool
	 */
	protected function _connect()
	{
		// Is already connected?
		if ( is_resource($this->socket) )
		{
			return;
		}
		$connectFunction = ( $this->setting['persistent'] === TRUE )
		                     ? 'pfsockopen'
		                     : 'fsockopen';
		$protocol = '';
		if ( $this->setting['ssl'] === TRUE )
		{
			$protocol = 'ssl://';
		}
		// connection start
		$this->socket = @$connectFunction(
			                              $protocol . $this->setting['hostname'],
			                              $this->setting['port'],
			                              $errno,
			                              $errstr,
			                              $this->setting['timeout']
			                             );
		
		if ( ! is_resource($this->socket) )
		{
			trigger_error('IMAP connection failed: ' . $errno . ':' . $errstr);
		}
		
		// set socket timeout
		stream_set_timeout($this->socket, 1);
		
		return TRUE;
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Destructor
	 * clean up connection resource
	 */
	public function __destruct()
	{
		if ( is_resource($this->socket) )
		{
			if ( $this->setting['persistent'] === TRUE )
			{
				@pclose($this->socket);
			}
			else
			{
				@fclose($this->socket);
			}
		}
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Get command-response logs
	 * 
	 * @access public
	 * @return string
	 */
	public function getLog()
	{
		return implode("\n", $this->_log);
	}
}

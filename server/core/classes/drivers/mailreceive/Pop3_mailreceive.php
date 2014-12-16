<?php if ( ! defined('SZ_EXEC') ) exit('access_denied');

/**
 * ====================================================================
 * 
 * Seezoo-Framework
 * 
 * A simple MVC/action Framework on PHP 5.1.0 or newer
 * 
 * 
 * Receive mail from POP3
 * 
 * @package  Seezoo-Framework
 * @category Drivers
 * @author   Yoshiaki Sugimoto <neo.yoshiaki.sugimoto@gmail.com>
 * @license  MIT Licence
 * 
 * ====================================================================
 */
class SZ_Pop3_mailreceive extends SZ_Mailreceive_driver
{
	/**
	 * Get mails
	 * 
	 * @access public
	 * @param  int $count
	 * @return mixed
	 */
	public function getMail($count)
	{
		$this->_connect();
		// POP3 server responses string after connection.
		// So, rewind while last line
		$this->prepare();
		if ( $this->_login() )
		{
			$mails = $this->_getMails($count);
			$this->_logout();
			return $mails;
		}
		return FALSE;
		
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Prepare rewind the response socket
	 * 
	 * @access protected
	 */
	protected function prepare()
	{
		while ( TRUE )
		{
			$line = fgets($this->socket, 512);
			if ( preg_match('/^\+OK\s|^\-ERR\s/u', $line) )
			{
				break;
			}
		}
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Get mail
	 * 
	 * @access protected
	 * @param  int $count
	 * @return mixed
	 */
	protected function _getMails($count = 0)
	{
		$resp  = $this->command('LIST');
		$mails = array();
		
		// Split the mail list
		$listData = explode("\r\n", $resp->response);
		$msg      = array_shift($listData);
		
		// Does response be OK?
		if ( ! preg_match('/^\+OK/u', trim($msg), $match) )
		{
			throw new Exception('Get inbox failed.');
		}
		
		$this->mailCount = count($listData);
		
		// Loop and decode mails
		for ( $i = $this->mailCount; $i > $this->mailCount - $count; $i-- )
		{
			$resp = $this->command('RETR ' . $i);
			list($status, $mail) = explode("\r\n", $resp->response, 2);
			if ( ! preg_match('/^\+OK/u', $status) )
			{
				throw new Exception('Number ' . $i . ' mail not found.');
			}
			$mails[] = new SZ_Mail_decoder($mail);
		}
		
		return $mails;
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Login server
	 * 
	 * @access protected
	 * @return bool
	 */
	protected function _login()
	{
		if ( $this->loggedIn === TRUE )
		{
			return TRUE;
		}
		// Send USER command
		$com = $this->command('USER ' . $this->setting['user']);
		if ( $com->code !== 'OK' )
		{
			return FALSE;
		}
		// Send PASS command
		$com = $this->command('PASS ' . $this->setting['password']);
		if ( $com->code !== 'OK' )
		{
			return FALSE;
		}
		$this->loggedIn = TRUE;
		return TRUE;
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Logout
	 * 
	 * @access protected
	 */
	protected function _logout()
	{
		$this->command('QUIT', FALSE);
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Send command
	 */
	public function command($command, $getResponse = TRUE)
	{
		fputs($this->socket, $command . $this->CRLF);
		$this->_log[] = 'client: ' . $command;
		if ( $getResponse )
		{
			$response = '';
			while ( TRUE )
			{
				$line = fgets($this->socket, 512);
				if ( trim($line, "\r\n") === '.' )
				{
					break;
				}
				else if ( preg_match('/^\+OK\s|^\-ERR\s/u', $line)
				          && preg_match('/^USER|PASS/us', $command) )
				{
					break;
				}
				$response .= $line;
			}
			
			$this->_log[] = 'server: ' . $response . $line;
			$exp  = explode(' ', trim($line, '.+-'), 2);
			
			$data = new stdClass;
			$data->code     = reset($exp);
			$data->message  = end($exp);
			$data->response = trim($response, "\r\n");
			
			return $data;
		}
	}
}

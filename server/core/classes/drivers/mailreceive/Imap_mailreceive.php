<?php if ( ! defined('SZ_EXEC') ) exit('access_denied');

/**
 * ====================================================================
 * 
 * Seezoo-Framework
 * 
 * A simple MVC/action Framework on PHP 5.1.0 or newer
 * 
 * 
 * Receive mail from IMAP
 * 
 * @package  Seezoo-Framework
 * @category Drivers
 * @author   Yoshiaki Sugimoto <neo.yoshiaki.sugimoto@gmail.com>
 * @license  MIT Licence
 * 
 * ====================================================================
 */
class SZ_Imap_mailreceive extends SZ_Mailreceive_driver
{
	/**
	 * Send command count
	 * @var int
	 */
	protected $commandCount = 0;
	
	/**
	 * Command prefix
	 * @var string
	 */
	protected $commandPrefix = 'Dog';
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Get mail
	 * abstruct implement
	 * 
	 * @access public
	 * @param  int $count
	 * @return mixed
	 */
	public function getMail($count)
	{
		$this->_connect();
		
		// try login
		if ( $this->setting['authenticate'] === 'LOGIN' )
		{
			$ret = $this->_login();
		}
		else if ( $this->setting['authenticate'] === 'SASL' )
		{
			$ret = $this->_saslLogin();
		}
		
		if ( ! $ret )
		{
			return FALSE;
		}
		
		// execute get mail command
		$mails = $this->_getMails($count);
		
		// and clean up...
		$this->_logout();
		
		return $mails;
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Get mail command
	 * 
	 * @access protected
	 * @param  int $count
	 * @return mixed
	 */
	protected function _getMails($count = 0)
	{
		$resp = $this->command('SELECT INBOX');
		
		// Response had inbox mail count string?
		if ( ! preg_match('/([0-9]+)\sEXISTS.+([0-9]+)\sRECENT/us', $resp->response, $match) )
		{
			throw new Exception('get inbox data failed.');
		}
		
		$this->mailCount   = $match[1];
		$this->recentMails = $match[2];
		
		$cnt   = (int)$this->mailCount;
		$range = ( $count > 0 )
		           ? sprintf('%d:%d', $cnt - $count + 1, $cnt)
		           : $cnt;
		
		$resp  = $this->command('FETCH ' . $range . ' BODY.PEEK[]');
		$mails = preg_split('/\*\s[0-9]+\sFETCH\s\(BODY\[\]\s\{[0-9]+\}/u', $resp->response);
		$mails = array_filter($mails);
		$ret   = array();
		
		// Loop and decode mail
		foreach ( $mails as $mail )
		{
			$ret[] = new SZ_Mail_decoder($mail, $cnt--);
		}
		
		return $ret;
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Make command prefix
	 * 
	 * @access protected
	 * @return string
	 */
	protected function prefix()
	{
		return $this->commandPrefix . ++$this->commandCount;
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Do login
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
		$com = $this->command('LOGIN ' . $this->setting['user'] . ' ' . $this->setting['password']);
		if ( $com->code !== 'OK' )
		{
			return FALSE;
		}
		$this->loggedIn = TRUE;
		return TRUE;
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * SASL login
	 * 
	 * @access protected
	 * @return bool
	 */
	protected function _saslLogin()
	{
		if ( $this->loggedIn === TRUE )
		{
			return TRUE;
		}
		$com = $this->command('AUTHENTICATE XOAUTH2 ' . $this->setting['saslkey']);
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
		$this->command('LOGOUT', FALSE);
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Send command
	 * 
	 * @access public
	 * @param  string $command
	 * @param  bool $getResponse
	 * @return mixed void or stdClass
	 */
	public function command($command, $getResponse = TRUE)
	{
		$prefix = $this->prefix();
		$this->_log[] = 'client: ' . $prefix . ' ' . $command;
		
		// Send command
		fputs($this->socket, $prefix . ' ' . $command . $this->CRLF);
		
		if ( $getResponse )
		{
			// Get response
			$response = '';
			while ( TRUE )
			{
				$line = fgets($this->socket, 512);
				// Break get response while response has command preifx
				if ( strpos($line, $prefix) === 0 )
				{
					break;
				}
				$response .= $line;
			}
			
			$this->_log[] = 'server: ' . $response . $line;
			$exp = explode(' ', $line, 3);
			array_shift($exp);
			
			$data = new stdClass;
			$data->code     = array_shift($exp);
			$data->message  = end($exp);
			$data->response = $response;
			
			return $data;
		}
	}
}

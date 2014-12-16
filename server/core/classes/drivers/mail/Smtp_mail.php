<?php if ( ! defined('SZ_EXEC') ) exit('access_denied');

/**
 * ====================================================================
 * 
 * Seezoo-Framework
 * 
 * A simple MVC/action Framework on PHP 5.1.0 or newer
 * 
 * 
 * Send Mail with SMTP server connection
 * 
 * @package  Seezoo-Framework
 * @category Drivers
 * @author   Yoshiaki Sugimoto <neo.yoshiaki.sugimoto@gmail.com>
 * @license  MIT Licence
 * 
 * ====================================================================
 */

class SZ_Smtp_mail extends SZ_Mail_driver
{
	/**
	 * Socket handle
	 * @var recource
	 */
	protected $handle;
	
	
	/**
	 * SMTP server information from config
	 * @var sring / int / bool
	 */
	protected $_host;      // hostname
	protected $_port;      // port number
	protected $_authtype;  // authenticate protocol
	protected $_username;  // username
	protected $_password;  // password
	
	
	/**
	 * Connection error
	 * @var string
	 */
	protected $_error;
	
	
	/**
	 * To strings
	 * @var array
	 */
	protected $_tos = array();
	
	public function __construct()
	{
		parent::__construct();
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Init parameter 
	 * 
	 * @access protected
	 */
	protected function initParams()
	{
		$smtp = $this->setting['smtp'];
		// set SMTP server settings from config
		$this->_host      = $smtp['hostname'];
		$this->_port      = $smtp['port'];
		$this->_authtype  = strtoupper($smtp['authtype']);
		$this->_username  = $smtp['username'];
		$this->_password  = $smtp['password'];
		$this->_keepAlive = $smtp['keepalive'];
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Abstract implements
	 * Do send mail
	 * @see seezoo/core/classes/drivers/mail/SZ_Mail_driver::_sendmail()
	 */
	protected function _sendmail()
	{
		// socket connection
		$this->_connect();
		// initialize
		$this->_tos = array();
		
		$this->cmd('MAIL FROM:<' . $this->_from . '>');
		
		// set To
		foreach ( $this->_to as $email )
		{
			$this->tos[] = 'To:' . $this->_addressFormat($email);
			$this->cmd('RCPT TO:<' . $email[0] . '>');
		}
		// set Cc
		foreach ( $this->_cc as $email )
		{
			$this->tos[] = 'To:' . $this->_addressFormat($email);
			$this->cmd('RCPT TO:<' . $email[0] . '>');
		}
		// If SMTP sending, BCC also use RCPT TO command.
		// But, don't add header parameter 
		foreach ( $this->_bcc as $email )
		{
			// Bcc is not add header.
			$this->cmd('RCPT TO:<' . $email[0] . '>');
		}
		
		$this->cmd('DATA');
		// mail data
		$data = array(
			$this->_createHeader(),
			$this->CRLF,
			$this->_createBody(),
			'.' . $this->CRLF
		);
		
		// send!
		$exec = $this->cmd(implode('', $data));
		
		// close socket if not keep-alive
		if ( $this->_keepAlive !== TRUE )
		{
			fclose($this->handle);
		}
		
		return ( $exec[0] == 250 ) ? TRUE : FALSE;
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Send command and check response
	 * 
	 * @access protected
	 * @param  string $command
	 * @return bool
	 */
	protected function cmd($command)
	{
		// send command
		fputs($this->handle, $command . $this->CRLF);
		
		// and get response
		$response = fgets($this->handle, 512);
		$this->_log[] = $command . ' : ' . $response;
		
		// response code 2XX is sucess code.
		//if ( ! preg_match('/\A2[0-9]{2}/', $response) )
		//{
			//throw new Exception('SMTP response returns Failure Code: ' . $response);
			//return FALSE;
		//}
		
		return explode(' ', $response, 2);
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Connect to SMTP server
	 * 
	 * @access protected
	 */
	protected function _connect()
	{
		$this->initParams();
		$this->handle = @fsockopen($this->_host, $this->_port);
		$this->cmd('EHLO ' . $this->_host);
		
		// Does Server need Authenticate?
		switch ( $this->_authtype )
		{
			case 'PLAIN':
				$this->authPlain();
				break;
			
			case 'LOGIN':
				$this->authLogin();
				break;
			
			case 'CRAM-MD5':
				$this->authCramMd5();
				break;
				
		}
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Authenticate "PLAIN"
	 * 
	 * @access protected
	 * @return void
	 * @throws SeezooException
	 */
	protected function authPlain()
	{
		$crypto = $this->_username. "\0". $this->_username. "\0" . $this->_password;
		$auth   = $this->_cmd('AUTH PLAIN ' . base64_encode($crypto));
		
		if ( $auth[0] != 235 )
		{
			throw new SeezooException('SMTP PLAIN Auth failed at Server: ' . $this->_host);
		}
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Authenticate "LOGIN"
	 * 
	 * @access protected
	 * @return void
	 * @throws SeezooException
	 */
	protected function authLogin()
	{
		$this->cmd('AUTH LOGIN');
		$this->cmd(base64_encode($this->_username));
		$auth = $this->cmd(base64_encode($this->_password));
		
		if ( $auth[0] != 235 )
		{
			throw new SeezooException('SMTP LOGIN Auth failed at Server: ' . $this->_host);
		}
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Authenticate "CRAM-MD5"
	 * 
	 * @access protected
	 * @return void
	 * @throws SeezooException
	 */
	protected function authCramMd5()
	{
		$challenge = $this->cmd('AUTH CRAM-MD5');
		$cipher    = base64_decode($challenge[1]);
		$crypto    = $this->_username . ' ' . hash_hmac('md5', $cipher, $this->_password);
		$auth      = $this->cmd(base64_encode($crypto));
		
		if ( $auth[0] != 235 )
		{
			throw new SeezooException('SMTP CRAM-MD5 Auth failed at Server: ' . $this->_host);
		}
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Create header string
	 * 
	 * @access protected
	 * @return string
	 */
	protected function _createHeader()
	{
		$header = array();
		$uniq   = sha1(uniqid(mt_rand(), TRUE));
		$date   = date('D, j M Y H:i:s');
		
		$this->_boundary = 'szboundary' . $uniq;
		
		// Date
		$header[] = 'Date: ' . $date;
		$replyTo  = ( $this->_replyTo ) ? $this->_replyTo : $this->_from;
		// Return-Path
		$header[] = 'Return-Path: ' . $replyTo;
		// Reply-to
		$header[] = 'Reply-To: ' . $replyTo;
		// From
		$header[] = 'From: ' . $this->_addressFormat(array($this->_from, $this->_fromName));
		
		if ( count($this->_cc) > 0 )
		{
			$header[] = 'Cc: ' . implode(', ', array_map(array($this, '_addressFormat'), $this->_cc));
		}
		
		// SMTP need to contain Subject on header string
		$header[] = 'Subject:' . $this->_encodeHeader($this->_subject);
		
		if ( ! $this->_messageID )
		{
			$req = Seezoo::getRequest();
			$header[] = sprintf('Message-ID: <%s@%s>', $uniq, $req->server('SERVER_NAME')); 
		}
		else
		{
			$header[] = sprintf('Message-ID: <%s>', $this->_messageID);
		}
		$header[] = 'X-Mailer: SZFW MailClass.SMTPSender';
		
		if ( count($this->_attachFiles) > 0 )
		{
			$header[] = 'Content-Type: multipart/mixed; boundary=' . $this->_boundary;
		}
		else
		{
			$header[] = 'Content-Transfer-Encoding: base64';
			$header[] = 'Content-Type: text/plain; charset=UTF-8';
		}
		
		return implode($this->CRLF, $header);
		
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Create mail body
	 * 
	 * @access protected
	 * @return string
	 */
	protected function _createBody()
	{
		if ( count($this->_attachFiles) > 0 )
		{
			return $this->_attachFileToMail();
		}
		else
		{
			return chunk_split(base64_encode($this->_body), 70, $this->CRLF);
		}
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Create file-attached mail body
	 * 
	 * @access protected
	 * @return string
	 */
	protected function _attachFileToMail()
	{
		$ret = array();
		
		// initial boundary
		$ret[] = '--' . $this->_boundary;
		$ret[] = 'Content-Type: text/plain; charset="UTF-8"';
		$ret[] = 'Content-Transfer-Encoding: base64';
		$ret[] = $this->CRLF;
		$ret[] = chunk_split(base64_encode($this->_body), 70, $this->CRLF);
		
		foreach ( $this->_attachFiles as $attach )
		{
			// get file content
			$dat = file_get_contents($attach->filePath);
			// data is Binary?
			if ( $attach->encoding === 'binary' || ! ctype_print($dat) )
			{
				$encode = 'base64';
				$body   = chunk_split(base64_encode($dat), 76, $this->CRLF);
				//$body   = $dat;
			}
			else
			{
				$encode = $attach->encoding;
				$body   = chunk_split(base64_encode($dat), 76, $this->CRLF);
			}
			
			$ret[] = '--' . $this->_boundary;
			$ret[] = sprintf('Content-Type: %s; name="%s"', $attach->mimeType, $this->_encodeHeader(trim($attach->attachName)));
			$ret[] = 'Content-Transfer-Encoding: ' . $encode;
			$ret[] = 'Content-Disposition: attachment; filename="' . $this->_encodeHeader($attach->attachName) . '"';
			$ret[] = $this->CRLF;
			$ret[] = $body;
			$ret[] = $this->CRLF;
		}
		
		$ret[] = '--' . $this->_boundary . '--' . $this->CRLF;
		
		return implode($this->CRLF, $ret);
	}
}
<?php if ( ! defined('SZ_EXEC') ) exit('access_denied');

/**
 * ====================================================================
 * 
 * Seezoo-Framework
 * 
 * A simple MVC/action Framework on PHP 5.1.0 or newer
 * 
 * 
 * Send Mail with PHP mail() function
 * 
 * @package  Seezoo-Framework
 * @category Drivers
 * @author   Yoshiaki Sugimoto <neo.yoshiaki.sugimoto@gmail.com>
 * @license  MIT Licence
 * 
 * ====================================================================
 */

class SZ_Php_mail extends SZ_Mail_driver
{
	/**
	 * Error data
	 * @var array
	 */
	protected $_errorSend = array();
	
	public function __construct()
	{
		parent::__construct();
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Abstract implements
	 * Send mail
	 * @see seezoo/core/classes/drivers/mail/SZ_Mail_driver::_sendmail()
	 */
	protected function _sendmail()
	{
		// initialize errors;
		$this->_errorSend = array();
		
		// create header, body string
		$header = $this->_createHeader();
		$body   = $this->_createBody();
		
		foreach ( $this->_to as $to )
		{
			$to = $this->_addressFormat($to);
			
			if ( ! $this->env->isSafeMode )
			{
				$ret = @mail(
							$to,
							$this->_encodeHeader($this->_subject),
							$body,
							$header,
							sprintf('-oi -f %s', $this->_from)
						);
			}
			else 
			{
				// If PHP works with safe-mode,
				// mail() function can't use 5th parameter ( additional parameter ) 
				$ret = @mail(
							$to,
							$this->_encodeHeader($this->_subject),
							$body,
							$header
						);
			}
			if ( ! $ret )
			{
				// send erro...
				$this->_errorSend[] = $this->_addressFormat($to);
				$this->_log[] = "Mail send miss to: {$to} address.";
			}
			else
			{
				$this->_log[] = "Sended my mail() function: {$to}";
			}
		}
		
		return ( count($this->_errorSend) === 0 ) ? TRUE : FALSE;
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Create Header string
	 * 
	 * @access protected
	 * @return string
	 */
	protected function _createHeader()
	{
		$header = array();
		$uniq   = sha1(uniqid(mt_rand(), TRUE));
		$date   = date('D, j M Y H:i:s');
		
		// create boundary string ( but perhaps no use )
		$this->_boundary = 'szboundary' . $uniq;
		
		// Send date
		$header[] = 'Date: ' . $date;
		
		$replyTo  = ( $this->_replyTo ) ? $this->_replyTo : $this->_from;
		// Return-Path
		$header[] = 'Return-Path: ' . $replyTo;
		// Reply-to
		$header[] = 'Reply-To: ' . $replyTo;
		// From
		$header[] = 'From: ' . $this->_addressFormat(array($this->_from, $this->_fromName));
		
		// Does need Cc?
		if ( count($this->_cc) > 0 )
		{
			$header[] = 'Cc: ' . implode(', ', array_map(array($this, '_addressFormat'), $this->_cc));
		}
		
		// Does need Bcc?
		if ( count($this->_bcc) > 0 )
		{
			$header[] = 'Bcc: ' . implode(', ', array_map(array($this, '_addressFormat'), $this->_bcc));
		}
		
		// Message-ID
		if ( ! $this->_messageID )
		{
			$req = Seezoo::getRequest();
			$header[] = sprintf('Message-ID: <%s@%s>', $uniq, $req->server('SERVER_NAME')); 
		}
		else
		{
			$header[] = sprintf('Message-ID: <%s>', $this->_messageID);
		}
		
		// Set our Mail system name
		$header[] = 'X-Mailer: SZFW MailClass.MailFunctionSender';
		
		// Does attachment file exists?
		if ( count($this->_attachFiles) > 0 )
		{
			$header[] = 'Content-Type: multipart/mixed; boundary=' . $this->_boundary;
		}
		// Or simple mail
		else
		{
			$header[] = 'Content-Transfer-Encoding: base64';
			$header[] = 'Content-Type: text/plain; charset=UTF-8';
		}
		
		return implode($this->LF, $header);
		
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
		// Does attachment file exists?
		if ( count($this->_attachFiles) > 0 )
		{
			return $this->_attachFileToMail();
		}
		// Or simple text mail
		else
		{
			return chunk_split(base64_encode($this->_body), 70, $this->LF);
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
		// returns
		$ret = array();
		
		// First, text section
		$ret[] = '--' . $this->_boundary;
		$ret[] = 'Content-Type: text/plain; charset="UTF-8"';
		$ret[] = 'Content-Transfer-Encoding: base64';
		$ret[] = $this->LF;
		$ret[] = chunk_split(base64_encode($this->_body), 70, $this->LF);
		
		// Second, attach file section
		foreach ( $this->_attachFiles as $attach )
		{
			// get file content
			$dat = file_get_contents($attach->filePath);
			
			// Binary data sometimes does not send...?
			// So, we use base64-encoded data
			
			// data is Binary?
			if ( $attach->encoding === 'binary' || ! ctype_print($dat) )
			{
				$encode = 'base64';
				$body   = chunk_split(base64_encode($dat), 76, $this->LF);
			}
			else
			{
				$encode = $attach->encoding;
				$body   = chunk_split(base64_encode($dat), 76, $this->LF);
			}
			
			$ret[] = '--' . $this->_boundary;
			$ret[] = sprintf('Content-Type: %s; name="%s"', $attach->mimeType, $this->_encodeHeader(trim($attach->attachName)));
			$ret[] = 'Content-Transfer-Encoding: ' . $encode;
			$ret[] = 'Content-Disposition: attachment; filename="' . $this->_encodeHeader($attach->attachName) . '"';
			$ret[] = $this->LF;
			$ret[] = $body;
			$ret[] = $this->LF;
		}
		
		// End boundary
		$ret[] = '--' . $this->_boundary . '--' . $this->LF;
		
		return implode($this->LF, $ret);
	}
}
<?php if ( ! defined('SZ_EXEC') ) exit('access_denied');

/**
 * ====================================================================
 * 
 * Seezoo-Framework
 * 
 * A simple MVC/action Framework on PHP 5.1.0 or newer
 * 
 * 
 * Simple Mail sender Library
 * 
 * @package  Seezoo-Framework
 * @category Libraries
 * @author   Yoshiaki Sugimoto <neo.yoshiaki.sugimoto@gmail.com>
 * @license  MIT Licence
 * 
 * ====================================================================
 */

class SZ_Mail extends SZ_Driver implements Growable
{
	/**
	 * Mail settings
	 * @var array
	 */
	protected $setting;
	
	
	/**
	 * Use Dirver type
	 * @var string
	 */
	protected $dirverType;
	
	
	public function __construct()
	{
		parent::__construct();
		
		$env              = Seezoo::getENV();
		$this->setting    = $env->getMailSettings();
		$driverType       = $this->setting['type'];
		// load the driver
		$this->driver = $this->loadDriver(ucfirst($driverType) . '_mail');
		$this->driver->setup($this->setting);
	}
	
	
	/**
	 * Growable interface implementation
	 * 
	 * @access public static
	 * @return View ( extended )
	 */
	public static function grow()
	{
		return Seezoo::$Importer->library('Mail');
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * set "To"
	 * 
	 * @access public
	 * @param  mixed  $email ( string or array )
	 * @param  string $toName
	 */
	public function to($email = '', $toName = '')
	{
		$this->driver->to($email, $toName);
		return $this;
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Set "reply-to"
	 * This library use both reply-to and return-path
	 * 
	 * @access public
	 * @param  string $file
	 */
	public function replyTo($replyTo)
	{
		$this->driver->replyTo($replyTo);
		return $this;
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * set "Cc"
	 * 
	 * @access public
	 * @param  mixed  $email ( string or array )
	 * @param  string $toName
	 */
	public function cc($email = '', $toName = '')
	{
		$this->driver->cc($email, $toName);
		return $this;
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * set "Bcc"
	 * 
	 * @access public
	 * @param  mixed  $email ( string or array (
	 * @param  string $toName
	 */
	public function bcc($email = '', $toName = '')
	{
		$this->driver->bcc($email, $toName);
		return $this;
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Attach file to mail
	 * 
	 * @access public
	 * @param  string $file
	 * @param  string $attachName
	 * @param  string $encoding
	 * @param  string $mimetype
	 */
	public function attach($file, $attachName = '', $encoding = 'base64', $mimetype = 'application/octet-stream')
	{
		$this->driver->attach($file, $attachName, $encoding, $mimetype);
		return $this;
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * get Send ( or command on SMTP ) log
	 * 
	 * @access public
	 * @return string
	 */
	public function log()
	{
		return $this->driver->getLog();
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * set "Subject"
	 * 
	 * @access public
	 * @param  string $subject
	 */
	public function subject($subject = '')
	{
		$this->driver->subject($subject);
		return $this;
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * set mailbody
	 * 
	 * @access public
	 * @param  string $body
	 */
	public function body($body = '')
	{
		$this->driver->body($body);
		return $this;
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * set "From" email
	 * @param string $from
	 */
	public function from($from)
	{
		$this->driver->from($from);
		return $this;
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * set "From" name
	 * @param string $fromName
	 */
	public function fromName($fromName)
	{
		$this->driver->fromName($fromName);
		return $this;
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * set "Message-ID" section
	 * @param string $msgID
	 */
	public function messageID($msgID)
	{
		$this->driver->messageID($msgID);
		return $this;
	}
	
}
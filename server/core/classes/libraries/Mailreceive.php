<?php if ( ! defined('SZ_EXEC') ) exit('access_denied');

/**
 * ====================================================================
 * 
 * Seezoo-Framework
 * 
 * A simple MVC/action Framework on PHP 5.1.0 or newer
 * 
 * 
 * Mail receiver
 * 
 * @package  Seezoo-Framework
 * @category Libraries
 * @author   Yoshiaki Sugimoto <neo.yoshiaki.sugimoto@gmail.com>
 * @license  MIT Licence
 * 
 * ====================================================================
 */

class SZ_Mailreceive extends SZ_Driver implements Growable
{
	/**
	 * Mail settings
	 * @var array
	 */
	protected $settings;
	
	/**
	 * Mail decoder
	 * @var string
	 */
	protected $decoder;
	
	/**
	 * Mail receiver name
	 * @var sring
	 */
	protected $receiver;
	
	
	public function __construct()
	{
		parent::__construct();
		
		$env = Seezoo::getENV();
		$this->settings = $env->getMailSettings();
		$this->receiver = $this->settings['receiver'];
		$this->decoder  = $this->loadDriver('Mail_decoder', FALSE);
		$this->driver   = $this->loadDriver(ucfirst($this->receiver) . '_mailreceive');
		if ( isset($this->settings[$this->receiver]) )
		{
			$this->driver->configure($this->settings[$this->receiver]);
		}
	}
	
	
	/**
	 * Growable interface implementation
	 * 
	 * @access public static
	 * @return View ( extended )
	 */
	public static function grow()
	{
		return Seezoo::$Importer->library('Mailreceive');
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Get mails
	 * 
	 * @access public
	 * @param int $count
	 * @return mixed
	 */
	public function getMail($count = 0)
	{
		return $this->driver->getMail($count);
	}
	
}

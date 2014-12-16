<?php if ( ! defined('SZ_EXEC') ) exit('access_denied');

/**
 * ====================================================================
 * 
 * Seezoo-Framework
 * 
 * A simple MVC/action Framework on PHP 5.1.0 or newer
 * 
 * 
 * Oauth library
 * 
 * @package  Seezoo-Framework
 * @category Libraries
 * @author   Yoshiaki Sugimoto <neo.yoshiaki.sugimoto@gmail.com>
 * @license  MIT Licence
 * 
 * ====================================================================
 */
class SZ_Oauth extends SZ_Driver implements Growable
{
	/**
	 * Support drivers
	 */
	protected $drivers = array(
		'facebook' => FALSE,
		'google'   => FALSE,
		'mixi'     => FALSE,
		'github'   => FALSE,
		'twitter'  => FALSE,
		'dropbox'  => FALSE
	);
	
	
	public function __construct($serviceName = null)
	{
		parent::__construct();
		
		if ( $serviceName )
		{
			$this->service($serviceName);
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
		return Seezoo::$Importer->library('Oauth');
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Select use service
	 * 
	 * @access public
	 * @param  string $serviceName
	 * @param  array  $conf
	 */
	public function service($serviceName, $conf = array())
	{
		$serviceName = strtolower($serviceName);
		if ( ! isset($this->drivers[$serviceName]) )
		{
			throw new Exception('Service ' . $serviceName . ' does not support!');
		}
		
		if ( ! $this->drivers[$serviceName] )
		{
			$this->drivers[$serviceName] = $this->loadDriver(ucfirst($serviceName) . '_oauth');
		}
		
		$this->driver = $this->drivers[$serviceName];
		$this->driver->configure($conf);
	}
}

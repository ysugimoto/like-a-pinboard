<?php if ( ! defined('SZ_EXEC') ) exit('access_denied');

/**
 * ====================================================================
 * 
 * Seezoo-Framework
 * 
 * A simple MVC/action Framework on PHP 5.1.0 or newer
 * 
 * 
 * Session Library ( use Driver )
 * 
 * @package  Seezoo-Framework
 * @category Libraries
 * @author   Yoshiaki Sugimoto <neo.yoshiaki.sugimoto@gmail.com>
 * @license  MIT Licence
 * 
 * ====================================================================
 */

class SZ_Session extends SZ_Driver implements Growable, Singleton
{
	/**
	 * Enviroment ckass instance
	 * @var Enviroment
	 */
	protected $env;
	
	public function __construct()
	{
		parent::__construct();
		
		$this->env = Seezoo::getENV();
		
		$driverName = $this->env->getConfig('session_store_type');
		if ( ! $driverName )
		{
			$driverName = 'php';
		}
		$this->driver = $this->loadDriver(ucfirst($driverName) . '_session');
	}
	
	
	/**
	 * Growable interface implementation
	 * 
	 * @access public static
	 * @return View ( extended )
	 */
	public static function grow()
	{
		return Seezoo::$Importer->library('Session');
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * Generate session token
	 * 
	 * @access public
	 * @param  string $tokenName
	 * @param  bool   $setFlash
	 * @return string
	 */
	public function generateToken($tokenName = '', $setFlash = FALSE)
	{
		if ( empty($tokenName) )
		{
			throw new InvalidArgumentException('Token name must not be empty!');
		}
		
		$token = sha1(uniqid(mt_rand(), TRUE));
		if ( $setFlash )
		{
			$this->setFlash($tokenName, $token);
		}
		else
		{
			$this->set($tokenName, $token);
		}
		return $token;
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * Check token data
	 * 
	 * @access public
	 * @param  string $tokenName
	 * @param  string $token
	 * @param  bool   $fromFlash
	 * @return bool
	 */
	public function checkToken($tokenName = '', $token = '', $fromFlash = FALSE)
	{
		$sess = ( $fromFlash )
		          ? $this->driver->getFlash($tokenName)
		          : $this->driver->get($tokenName);
		
		if ( ! $token || $token !== $sess )
		{
			return FALSE;
		}
		return TRUE;
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * set session data
	 * 
	 * @access public
	 * @param  string $key
	 * @param  string $value
	 */
	public function set($key, $value = '')
	{
		$this->driver->set($key, $value);
		return $this;
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * remove session data
	 * 
	 * @access public
	 * @param  string $key
	 */
	public function remove($key)
	{
		foreach ( (array)$key as $index )
		{
			$this->driver->remove($index);
		}
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * set flash session data
	 * 
	 * @access public
	 * @param  string $key
	 * @param  string $value
	 */
	public function setFlash($key, $value = '')
	{
		$this->driver->setFlash($key, $value);
		return $this;
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * keep flash session data
	 * 
	 * @access public
	 * @param  string $key
	 */
	public function keepFlash($key = null)
	{
		$this->driver->keepFlash($key);
		return $this;
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * Save sessions
	 * 
	 * @access public
	 * @return $this
	 */
	public function save()
	{
		$this->driver->save();
		return $this;
	}

}
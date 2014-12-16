<?php if ( ! defined('SZ_EXEC') ) exit('access_denied');

/**
 * ====================================================================
 * 
 * Seezoo-Framework
 * 
 * A simple MVC/action Framework on PHP 5.1.0 or newer
 * 
 * 
 * Cookie helper
 * 
 * @package  Seezoo-Framework
 * @category helpers
 * @author   Yoshiaki Sugimoto <neo.yoshiaki.sugimoto@gmail.com>
 * @license  MIT Licence
 * 
 * ====================================================================
 */

class SZ_CookieHelper implements Growable
{
	/**
	 * Request class instance
	 * @var Request
	 */
	protected $req;
	
	
	/**
	 * Environment class instance
	 * @var Environment
	 */
	protected $env;
	
	
	/**
	 * Default cookie settings
	 * @var array
	 */
	protected $_defaultCookie = array(
		'name'     => '',
		'value'    => '',
		'expire'   => 0,
		'domain'   => '',
		'path'     => '/',
		'httponly' => FALSE
	);
	
	public function __construct()
	{
		$this->req = Seezoo::getRequest();
		$this->env = Seezoo::getENV();
		
		$this->_defaultCookie['domain'] = $this->env->getConfig('cookie_domain');
		$this->_defaultCookie['path']   = $this->env->getConfig('cookie_path');
	}
	
	
	/**
	 * Growable interface implementation
	 * 
	 * @access public static
	 * @return View ( extended )
	 */
	public static function grow()
	{
		return Seezoo::$Importer->helper('Cookie');
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Get a Cookie
	 * 
	 * @access public
	 * @param  string $key
	 * @return mixed
	 */
	public function get($key)
	{
		return $this->req->cookie($key);
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Set a Cookie
	 * 
	 * @access public
	 * @param  mixed  $name
	 * @param  string $value
	 * @param  int    $expire
	 * @param  string $domain
	 * @param  string $path
	 */
	public function set($name = '', $value = '', $expire = 0, $domain = '', $path = '/')
	{
		if ( ! is_array($name) )
		{
			$cookie = array(
				'name'   => $name,
				'value'  => $value,
				'expire' => $expire,
				'domain' => $domain,
				'path'   => $path
			);
		}
		else
		{
			$cookie = $name;
		}
		
		$cookie = array_merge($this->_defaultCookie, $cookie);
		
		setcookie($cookie['name'], $cookie['value'], time() + $cookie['expire'], $cookie['path'], $cookie['domain'], 0);
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Delete a Cookie
	 * 
	 * @access public
	 * @param  string $name
	 * @param  string $domain
	 * @param  string $path
	 */
	public function delete($name, $domain = '', $path = '/')
	{
		if ( $this->get($name) !== FALSE )
		{
			$this->set($name, '', time() - 3600, $domain, $path);
			return TRUE;
		}
		return FALSE;
	}
}

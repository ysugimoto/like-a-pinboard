<?php if ( ! defined('SZ_EXEC') ) exit('access_denied');

/**
 * ====================================================================
 * 
 * Seezoo-Framework
 * 
 * A simple MVC/action Framework on PHP 5.1.0 or newer
 * 
 * 
 * Http driver
 * 
 * @package  Seezoo-Framework
 * @category drivers
 * @author   Yoshiaki Sugimoto <neo.yoshiaki.sugimoto@gmail.com>
 * @license  MIT Licence
 * 
 * ====================================================================
 */
 
abstract class SZ_Http_driver
{
	/**
	 * Send request
	 *
	 * @abstract
	 * @param  string $method
	 * @param  string $uri
	 * @param  array $header
	 * @param  string $postBody
	 * @return object
	 */
	abstract function sendRequest($method, $uri, $header, $postBody);

	/**
	 * Request instance
	 *
	 * @access protected
	 * @param Request $req
	 */
	protected $req;

	/**
	 * Connection timeout
	 *
	 * @access protected
	 * @param int $connectTimeout
	 */
	protected $connectTimeout;

	/**
	 * Socket timeout
	 *
	 * @access protected
	 * @param int $timeout
	 */
	protected $timeout;

	/**
	 * Configure
	 * 
	 * @access public
	 * @param  array $conf
	 */
	public function configure($conf = array())
	{
		foreach ( $conf as $key => $val )
		{
			$this->{$key} = $val;
		}
	}
}

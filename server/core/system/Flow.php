<?php if ( ! defined('SZ_EXEC') ) exit('access_denied');

/**
 * ====================================================================
 * 
 * Seezoo-Framework
 * 
 * A simple MVC/action Framework on PHP 5.1.0 or newer
 * 
 * 
 * Simple Page Flow management
 * 
 * @package  Seezoo-Framework
 * @category Classes
 * @author   Yoshiaki Sugimoto <neo.yoshiaki.sugimoto@gmail.com>
 * @license  MIT Licence
 * 
 * ====================================================================
 */

class Flow
{
	/**
	 * Access flow data
	 * @var array
	 */
	protected static $_flowOrder = array();
	
	
	/**
	 * Current flow
	 * @var int
	 */
	protected static $_current = 0;
	
	
	public function __construct()
	{
		self::$_flowOrder = array();
		self::$_current   = 0;
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * Set and initialize flow order
	 * @param array $flow
	 */
	public static function set($flow)
	{
		self::$_flowOrder = (array)$flow;
		
		$SZ     = Seezoo::getInstance();
		$method = $SZ->router->getInfo('method');
		
		foreach ( self::$_flowOrder as $key => $val )
		{
			if ( $method == trim($val) )
			{
				self::$_current = $key;
				break;
			}
		}
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * Get all links flow
	 * 
	 * @access public
	 * @return array
	 */
	public static function all()
	{
		$links = array();
		foreach ( self::$_flowOrder as $key => $flow )
		{
			$prefix  = ( ! preg_match('/\Ahttp/u', $flow) ) ? page_link() : '';
			$links[] = $prefix . ltrim($flow, '/');
		}
		return $links;
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * Check strict process
	 * 
	 * @access public
	 * @param  string $from
	 * @return bool
	 */
	public static function checkFrom($from = '')
	{
		if ( ! $from )
		{
			$prev = rtrim(self::prev(), '/');
		}
		else
		{
			$prefix = ( ! preg_match('/\Ahttp/u', $flow) ) ? page_link() : '';
			$prev   = trim($prefix . $from, '/'); 
		}
		$req = Seezoo::getRequest();
		$ref = (string)$req->server('HTTP_REFERER');
		
		return ( ! empty($ref) && strpos(trim($ref, '/'), $prev) !== FALSE ) ? TRUE : FALSE;
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * get stepped flow link
	 * 
	 * @access public
	 * @param  int $num
	 * @return mixed 
	 */
	public static function step($num)
	{
		if ( isset(self::$_flowOrder[self::$_current + $num]) )
		{
			$step   = self::$_flowOrder[self::$_current + $num];
			$prefix = '';
			if ( ! preg_match('/\Ahttp/u', $step) )
			{
				$SZ = Seezoo::getInstance();
				$prefix = page_link() . $SZ->router->getInfo('directory') . $SZ->router->getInfo('class') . '/';
			}
			return $prefix . ltrim($step, '/');
		}
		return FALSE;
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * next flow
	 * 
	 * @access public
	 * @return string
	 */
	public static function next($withPointer = FALSE)
	{
		$ret = self::step(1);
		if ( $withPointer === TRUE )
		{
			self::$_current++;
		}
		return $ret;
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * next flow with anchor tag
	 * 
	 * @access public
	 * @return string
	 */
	public static function nextLink($linkText = '', $targetBlank = FALSE)
	{
		$uri  = self::step(1);
		$text = ( empty($linkText) ) ? $uri : $linkText;
		return self::_makeLinkTag($uri, $text, $targetBlank);
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * current flow
	 * 
	 * @access piblic
	 * @return mixed
	 */
	public static function current()
	{
		return self::step(0);
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * previous flow
	 * 
	 * @access public
	 * @return mixed
	 */
	public static function prev($withPointer = FALSE)
	{
		$ret = self::step(-1);
		if ( $withPointer === TRUE )
		{
			self::$_current--;
		}
		return $ret;
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * previous flow with anchor tag
	 * 
	 * @access public
	 * @return string
	 */
	public static function prevLink($linkText = '', $targetBlank = FALSE)
	{
		$uri  = self::step(-1);
		$text = ( empty($linkText) ) ? $uri : $linkText;
		return self::_makeLinkTag($uri, $text, $targetBlank);
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * check last flow
	 * 
	 * @access public
	 * @return bool
	 */
	public static function isLast()
	{
		return ( self::$_current === count(self::$_flowOrder) - 1 ) ? TRUE : FALSE;
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * check first flow
	 * 
	 * @access public
	 * @return bool
	 */
	public static function isFirst()
	{
		return ( self::$_current === 0 ) ? TRUE : FALSE;
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * Add flow
	 *
	 * @access public static
	 */
	public static function add($link)
	{
		self::$_flowOrder[] = $link;
	}
	
	
	// --------------------------------------------------
	
	/**
	 * Build link tag
	 *
	 * @access protected
	 * @param string $uri
	 * @param string $text
	 * @param bool   $targetBlank
	 * @return string
	 */
	protected function _makeLinkTag($uri, $text, $targetBlank)
	{
		$blank = ( $targetBlank ) ? ' target="_blank"' : '';
		return sprintf('<a href="%s"%s>%s</a>',$uri, $blank, prep_str($text));
	}
}

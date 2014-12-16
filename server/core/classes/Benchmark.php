<?php if ( ! defined('SZ_EXEC') ) exit('access_denied');

/**
 * ====================================================================
 * 
 * Seezoo-Framework
 * 
 * A simple MVC/action Framework on PHP 5.1.0 or newer
 * 
 * 
 * calc benchmark
 * 
 * @package  Seezoo-Framework
 * @category Classes
 * @author   Yoshiaki Sugimoto <neo.yoshiaki.sugimoto@gmail.com>
 * @license  MIT Licence
 * 
 * ====================================================================
 */

class SZ_Benchmark implements Growable, Singleton
{
	/**
	 * marking points
	 * @var array
	 */
	protected $_points = array();
	
	
	/**
	 * calculated points
	 * @var array
	 */
	protected $_marked = array();
	
	
	/**
	 * Growable interface implementation
	 * 
	 * @access public static
	 * @return View ( extended )
	 */
	public static function grow()
	{
		return Seezoo::$Importer->clases('Benchmark');
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * benchmark start
	 * 
	 * @access public
	 * @param  string $name
	 */
	public function start($name = 'default')
	{
		$this->_points[$name] = microtime();
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * End the benchmark
	 * 
	 * @access public
	 * @param  string $name
	 * @param  string $since
	 * @return float $bm
	 */
	public function end($name = 'default', $since = '')
	{
		if ( ! $since )
		{
			$since = $name;
		}
		if ( ! isset($this->_points[$since]) )
		{
			return FALSE;
		}
		
		$start = $this->_points[$since];
		$end   = microtime();
		
		list($stm, $sts) = explode(' ', $start);
		list($edm, $eds) = explode(' ', $end);
		
		$bm = number_format(($edm + $eds) - ($stm + $sts), 4);
		$this->_marked[$name] = $bm;
		return $bm;
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * get all marked benckmark ( for debug )
	 * 
	 * @access public 
	 * @return array
	 */
	public function getAllMarks()
	{
		return $this->_marked;
	}
}
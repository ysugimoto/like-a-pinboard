<?php if ( ! defined('SZ_EXEC') ) exit('access_denied');

/**
 * ====================================================================
 * 
 * Seezoo-Framework
 * 
 * A simple MVC/action Framework on PHP 5.1.0 or newer
 * 
 * 
 * Generate pagination
 * 
 * @package  Seezoo-Framework
 * @category libraries
 * @author   Yoshiaki Sugimoto <neo.yoshiaki.sugimoto@gmail.com>
 * @license  MIT Licence
 * 
 * ====================================================================
 */

class SZ_Paginate implements Growable
{
	/**
	 * Process settings
	 * @var array
	 */
	protected $setting = array();
	
	
	/**
	 * Genetated pagination stack string
	 * @var string
	 */
	protected $_generatedStack;
	
	protected $_total   = 0;
	protected $_start   = 0;
	protected $_end     = 0;
	
	
	/**
	 * Default settings
	 * @var array
	 */
	protected $_defaultSetting = array(
		'total'               => 0,
		'base_link'           => '',
		'per_page'            => 20,
		'num_links'           => 5, 
		'query_string'        => FALSE,
		'segment'             => 3,
		'first'               => '&laquo;最初',
		'last'                => '最後&raquo;',
		'next'                => '次へ&gt;',
		'prev'                => '&lt;前へ',
		'current_tag'         => 'strong',
		'current_class'       => 'current_page',
		'link_separator'      => '|',
		'links_wrapper_start' => '',
		'links_wrapper_end'   => '',
		'auto_assign'         => FALSE
	);
	
	public function __construct($conf = array())
	{
		$this->_defaultSetting['base_link'] = page_link();
		$this->configure($conf);
	}
	
	
	/**
	 * Growable interface implementation
	 * 
	 * @access public static
	 * @return SZ_Paginate ( extended )
	 */
	public static function grow()
	{
		return Seezoo::$Importer->library('Paginate');
	}
	
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Configuration
	 * 
	 * @access public
	 * @param  array $conf
	 */
	public function configure($conf = array())
	{
		$this->setting = array_merge($this->_defaultSetting, $conf);
		$this->_generatedStack = null;
		
		if ( $this->setting['auto_assign'] === TRUE )
		{
			$this->_autoAssign();
		}
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Start range getter
	 * 
	 * @access public
	 * @return int
	 */
	public function getStart()
	{
		return $this->_start;
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * End range getter
	 * 
	 * @access public
	 * @return int
	 */
	public function getEnd()
	{
		return $this->_end;
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Total rows getter
	 * 
	 * @access public
	 * @return int;
	 */
	public function getTotal()
	{
		return $this->_total;
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Auto assign this class to current process view
	 * 
	 * @access protected
	 */
	protected function _autoAssign()
	{
		$SZ = Seezoo::getInstance();
		$SZ->view->assign(array('Paginate' => $this));
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Generate pagination links
	 * 
	 * @access public
	 * @return string
	 */
	public function generate()
	{
		if ( $this->_generatedStack )
		{
			return $this->_generatedStack;
		}
		
		// extract setting parameters
		foreach ( $this->setting as $key => $val )
		{
			$$key = $val;
		}
		
		if ( $total === 0 || $per_page === 0 )
		{
			return '';
		}
		
		// set total pages and current page number
		$req     = Seezoo::getRequest();
		$pages   = ceil((int)$total / (int)$per_page);
		$current = ( $query_string !== FALSE ) ? (int)$req->get($query_string) : (int)$req->segment($segment);
		
		// If current page less than 1 ( minus ), set current page to 0;
		if ( $current < 1 )
		{
			$current = 0;
		}
		// If current page greater than total, set curretn page to last
		if ( $current > $total )
		{
			$current = ( $pages - 1 ) * $per_page;
		}
		
		// set start / end parameter
		$page_num  = $current;
		$current   = (int)floor(($current / $per_page) + 1);
		$start     = ( $current > $num_links ) ? $current - ($num_links - 1) : 1;
		$end       = ( ($current + $num_links) <  $pages ) ? $current + $num_links : $pages;
		$base_link = prep_str($base_link);
		
		// format base link
		// parameter pass with querystring or segment
		if ( $query_string !== FALSE )
		{
			$prefix     = ( strpos($base_link, '?') !== FALSE ) ? '&amp;' : '?';
			$base_link .=  $prefix . $query_string . '=';
		}
		else
		{
			$base_link  = trail_slash($base_link);
		}
		
		// start links generate
		$out = array();
		
		// Do we need to generate first link?
		if ( $current > ($pages + 1) )
		{
			$out[] = '<a href="' . $base_link . '">' . $first . '</a>';
		}
		
		// Do we need to generate previous link?
		if ( $current != 1 )
		{
			$tmp = $page_num - $per_page;
			if ( $tmp === 0 )
			{
				$tmp = 1;
			}
			$out[] = '<a href="' . $base_link . $tmp . '">' . $prev . '</a>';
		}
		
		// page links generate
		$loop = $start - 1;
		while ( ++$loop <= $end )
		{
			$i = ($loop * $per_page) - $per_page;
			if ( $i >= 0 )
			{
				// Is current page?
				if ( $current === $loop )
				{
					$out[] = '<' . prep_str($current_tag) . ' class="' . prep_str($current_class) . '">'
					         . $loop
					         . '</' . $current_tag . '>';
				}
				else
				{
					$n = ( $i === 0 ) ? '' : $i; 
					$out[] = '<a href="' . $base_link . $n . '">' . $loop . '</a>';
				}
			}
		}
		
		// Do we need to generate next link?
		if ( $current < $pages )
		{
			$tmp = $current * $per_page;
			$out[] = '<a href="' . $base_link . $tmp . '">' . $next . '</a>';
		}
		
		// Do we need to generate last link?
		if ( ($current * $per_page) < $pages )
		{
			$i = ($pages * $per_page) - $per_page;
			$out[] = '<a href="' . $base_link . $i . '">' . $last . '</a>';
		}
		
		// set stack to parameter
		$this->_start = $start;
		$this->_end   = $end;
		$this->_total = $total;
		
		// wrap and return links!
		$this->_generatedStack =  $links_wrapper_start . "\n" . implode($link_separator . "\n", $out) . "\n" . $links_wrapper_end;
		return $this->_generatedStack;
	}
}
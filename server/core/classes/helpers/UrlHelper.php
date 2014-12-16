<?php if ( ! defined('SZ_EXEC') ) exit('access_denied');

/**
 * ====================================================================
 * 
 * Seezoo-Framework
 * 
 * A simple MVC/action Framework on PHP 5.1.0 or newer
 * 
 * 
 * Url helper
 * 
 * @package  Seezoo-Framework
 * @category helpers
 * @author   Yoshiaki Sugimoto <neo.yoshiaki.sugimoto@gmail.com>
 * @license  MIT Licence
 * 
 * ====================================================================
 */

class SZ_UrlHelper implements Growable
{
	/**
	 * Growable interface implementation
	 * 
	 * @access public static
	 * @return View ( extended )
	 */
	public static function grow()
	{
		return Seezoo::$Importer->helper('Url');
	}
	
	
	/**
	 * Generate popup-anchor
	 * 
	 * @access public
	 * @param  string $uri
	 * @param  string $title
	 * @param  array  $attributes
	 * @return string
	 */
	public function anchorPopup($uri = '', $title = '', $attributes = array())
	{
		$out = array('<a href="javascript:void(0);"');
		if ( empty($title) )
		{
			$title = page_link($uri);
		}
		$out[] = ' title="' . $title . '"';
		$out[] = ' onclick="window.open(\'' . page_link($uri) . '\', \'popup\');"';
		$attr  = '';
		foreach ( (array)$attributes as $key => $attribute )
		{
			if ( is_int($key) )
			{
				$attr .= ' ' . $attribute;
			}
			else
			{
				$attr .= ' ' . $key . '="' . $attribute . '"';
			}
		}
		if ( $attr !== '' )
		{
			$out[] = $attr;
		}
		$out[] = '>' . prep_str($title) . '</a>';
		return implode('', $out);
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Generate anchor tag
	 * 
	 * @access public
	 * @param  string $uri
	 * @param  string $title
	 * @param  array  $attributes
	 * @return string
	 */
	public function anchor($uri = '', $title = '', $attributes = array() )
	{
		$out = array('<a href="' . page_link($uri) . '"');
		if ( empty($title) )
		{
			$title = page_link($uri);
		}
		$out[] = ' title="' . $title . '"';
		$attr  = '';
		foreach ( (array)$attributes as $key => $attribute )
		{
			if ( is_int($key) )
			{
				$attr .= ' ' . $attribute;
			}
			else
			{
				$attr .= ' ' . $key . '="' . $attribute . '"';
			}
		}
		if ( $attr !== '' )
		{
			$out[] = $attr;
		}
		$out[] = '>' . prep_str($title) . '</a>';
		return implode('', $out);
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * URL-formatted string convert to anchor
	 * 
	 * @access public
	 * @param  string $str
	 * @param  bool $popup
	 * @return string
	 */
	public function autoLink($str, $popup = FALSE)
	{
		return preg_replace(
					'/(https?:\/\/[\w\.\/]+[\/|\?]?[\-_.!~\*a-zA-Z0-9\/\?:;@&=+$,%#]+)/u',
					'<a href="\\1"' . (( $popup === TRUE ) ? ' target="_blank"' : '')  . '>\\1</a>',
					$str
				);
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Generate <img> tag
	 * 
	 * @access public
	 * @param  string $src
	 * @param  string $alt
	 * @param  array $attributes
	 * @return string
	 */
	public function img($src, $alt = '', $attributes = array())
	{
		$out = array('<img src="' . basse_link($src) . '"');
		if ( empty($alt) )
		{
			$alt = base_link($src);
		}
		$out[] = ' alt="' . $alt . '"';
		$attr  = '';
		foreach ( (array)$attributes as $key => $attribute )
		{
			if ( is_int($key) )
			{
				$attr .= ' ' . $attribute;
			}
			else
			{
				$attr .= ' ' . $key . '="' . $attribute . '"';
			}
		}
		if ( $attr !== '' )
		{
			$out[] = $attr;
		}
		$out[] = ' />';
		return implode('', $out);
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Generate <script> tag
	 * 
	 * @access public
	 * @param  string $src
	 * @return string
	 */
	public function js($src)
	{
		return '<script type="text/javascript" src="'
		       . base_link($src)
		       . '"></script>';
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Generate <link> stylesheet tag
	 * 
	 * @access public
	 * @param  string $href
	 * @param  string $media
	 * @return string
	 */
	public function css($href, $media = 'all')
	{
		return '<link rel="stylsheet" type="text/css" href="'
		       . base_link($href)
		       . '" media="' . prep_str($media) . '" />';
	}
}

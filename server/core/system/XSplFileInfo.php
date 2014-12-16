<?php if ( ! defined('SZ_EXEC') ) exit('access_denied');

/**
 * ====================================================================
 * 
 * Seezoo-Framework
 * 
 * A simple MVC/action Framework on PHP 5.1.0 or newer
 * 
 * 
 * Extensible SplFileInfo class ( for PHP5.2.x or lower)
 * 
 * @package  Seezoo-Framework
 * @category System
 * @author   Yoshiaki Sugimoto <neo.yoshiaki.sugimoto@gmail.com>
 * @license  MIT Licence
 * 
 * ====================================================================
 */

class XSplFileInfo extends SplFileInfo
{
	
	public function __construct($file)
	{
		parent::__construct($file);
		parent::setInfoClass(__CLASS__);
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Compatible exists()
	 * 
	 * @access public
	 * @return bool
	 */
	public function exists()
	{
		return file_exists($this);
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Compatible getBasename()
	 * 
	 * @access public
	 * @param  string $suffix
	 * @return string
	 */
	public function getBasename($suffix = '')
	{
		if ( method_exists(parent, __METHOD__) )
		{
			return parent::getBasename($suffix);
		}
		return basename($this);
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Compatible getExtension()
	 * 
	 * @access public
	 * @return string
	 */
	public function getExtension()
	{
		if ( method_exists(parent, __METHOD__) )
		{
			return parent::getExtension();
		}
		
		if ( FALSE !== ($pos = strrpos($this, '.')) )
		{
			return substr($this, ++$pos);
		}
		return (string)$this;
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Compatible getLinkTarget()
	 * 
	 * @access public
	 * @return string
	 */
	public function getLinkTarget()
	{
		if ( method_exists(parent, __METHOD__) )
		{
			return parent::getLinkTarget();
		}
		
		if ( FALSE !== is_link($this) )
		{
			return readlink($this);
		}
		return '';
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Compatible getRealPath()
	 * 
	 * @access public
	 * @return string
	 */
	public function getRealPath()
	{
		if ( method_exists(parent, __METHOD__) )
		{
			return parent::getRealPath();
		}
		return realpath($this);
	}
}

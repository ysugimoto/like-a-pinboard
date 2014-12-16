<?php if ( ! defined('SZ_EXEC') ) exit('access_denied');

/**
 * ====================================================================
 * 
 * Seezoo-Framework
 * 
 * A simple MVC/action Framework on PHP 5.1.0 or newer
 * 
 * 
 * XML-RPC special typed encode driver
 * 
 * @package  Seezoo-Framework
 * @category Drivers
 * @author   Yoshiaki Sugimoto <neo.yoshiaki.sugimoto@gmail.com>
 * @license  MIT Licence
 * 
 * ====================================================================
 */
class SZ_Xmlrpc_encoder
{
	/**
	 * return value stack
	 * @var string
	 */
	protected $_value;
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Detect paramters type
	 * 
	 * @access public static
	 * @param  mixed $param
	 * @return string
	 * @throws LogicException
	 */
	public static function detectType($param)
	{
		switch ( gettype($param) )
		{
			case 'boolean':
				return 'Boolean';
			case 'integer':
				return 'Integer';
			case 'double':
				return 'Double';
			case 'string':
				return 'String';
			case 'array':
				return ( self::isOrderedArray($param) ) ? 'Array' : 'Struct';
			case 'object':
				return 'base64';
			case 'NULL':
				return 'Nil';
			default:
				throw new LogicException('Invalid parameter passed! at SZ_Xmlrpc_value::detectType.');
		}
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Check Number-ordered array
	 * 
	 * @access pubic static
	 * @param  array $param
	 * @return bool
	 */
	public static function isOrderedArray($param)
	{
		foreach ( $param as $key => $val )
		{
			if ( intval($key) !== $key )
			{
				return FALSE;
			}
		}
		return TRUE;
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * constructor
	 */
	public function __construct($value, $type)
	{
		$type = str_replace('.', '_', $type);
		if ( ! method_exists($this, '_encode' . ucfirst($type)) )
		{
			throw new LogicException('Invalid typed value! called at ' . get_class($this) . '.');
		}
		
		$this->{'_encode' . ucfirst($type)}($value);
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Get the value ( XML-formatted-string )
	 * 
	 * @access public
	 * @return string
	 */
	public function getValue()
	{
		return '<value>' . $this->_value . '</value>';
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Escape character
	 * 
	 * @access protected
	 * @param  string $str
	 * @return string
	 */
	protected function _escape($str)
	{
		$grep = array('&', '<', '>');
		$sed  = array('&amp;', '&lt;', '&gt;');
		
		return str_replace($grep, $sed, $str);
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Encode integer parameter
	 * 
	 * @access protected
	 * @param  int $value
	 */
	protected function _encodeInteger($value)
	{
		$tag = ( $value > 2147483647 ) ? 'i8' : 'int';
		$this->_value = "<{$tag}>{$value}</{$tag}>"; 
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Encode 64bit integer parameter
	 * 
	 * @access protected
	 * @param  int $value
	 */
	protected function _encodeInt64($value)
	{
		$this->_encodeInteger($value);
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Encode boolean parameter
	 * 
	 * @access protected
	 * @param  bool $value
	 */
	protected function _encodeBoolean($value)
	{
		$this->_value = '<boolean>';
		$this->_value .= ( $value ) ? 1 : 0;
		$this->_value .= '</boolean>';
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Encode string parameter
	 * 
	 * @access protected
	 * @param  string $value
	 */
	protected function _encodeString($value)
	{
		$this->_value = '<string>';
		$this->_value .= $this->_escape($value);
		$this->_value .= '</string>';
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Encode double parameter
	 * 
	 * @access protected
	 * @param  float $value
	 */
	protected function _encodeDouble($value)
	{
		$this->_value = '<double>' . $value . '</double>';
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Encode array parameter
	 * 
	 * @access protected
	 * @param  array $value
	 */
	protected function _encodeArray($value)
	{
		$Class = get_class($this);
		$this->_value = '<array><data>';
		foreach ( $value as $element )
		{
			if ( $element instanceof $Class )
			{
				$this->_value .= $element->getValue();
			}
			else
			{
				// recursive
				$type = self::detectType($element);
				$v    = new $Class($element, $type);
				$this->_value .= $v->getValue();
			}
		}
		$this->_value .= '</data></array>';
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Encode base64-encoded parameter
	 * 
	 * @access protected
	 * @param  object $value
	 */
	protected function _encodeBase64($value)
	{
		$this->_value = '<base64>';
		$this->_value .= base64_encode(serialize($value));
		$this->_value .= '</base64>';
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Encode struct parameter
	 * 
	 * @access protected
	 * @param  array $value
	 */
	protected function _encodeStruct($value)
	{
		$Class = get_class($this);
		$this->_value = '<struct>';
		foreach ( $value as $member => $element )
		{
			$this->_value .= '<member>';
			$this->_value .= '<name>';
			$this->_value .= $this->_escape($member);
			$this->_value .= '</name>';
			if ( $element instanceof $Class )
			{
				$this->_value .= $element->getValue();
			}
			else
			{
				// recursive
				$type = self::detectType($element);
				$v    = new $Class($element, $type);
				$this->_value .= $v->getValue();
			}
			$this->_value .= '</member>';
		}
		$this->_value .= '</struct>';
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Encode Null parameter
	 * 
	 * @access protected
	 * @param  null $value
	 */
	protected function _encodeNil($value)
	{
		$this->_value .= '<nil>';
		$this->_value .= NULL;
		$this->_value .= '</nil>';
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Encode datetime-formatted parameter
	 * 
	 * @access protected
	 * @param  mixed $value
	 */
	protected function _encodeDatetime($value)
	{
		if ( is_int($value) || ctype_digit($value) )
		{
			$value =  ( function_exists('gmstrftime') )
			            ? gmstrftime("%Y%m%dT%H:%i:%s", $value)
			            : strftime("%Y%m%dT%H:%i:%s", $value - date('Z'));
		}
		$this->_value = '<dateTime.iso8601>' . $value . '</dateTime.iso8601>';
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Encode dateime.iso8601 parameter
	 * 
	 * @access protected
	 * @param  mixed $value
	 */
	protected function _encodeDatetime_iso8601($value)
	{
		$this->_encodeDatetime($value);
	}
}

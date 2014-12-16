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
class SZ_Xmlrpc_decoder
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
		switch ( strtolower($param->firstChild->tagName) )
		{
			case 'int':
			case 'i4':
			case 'i8':
				$type = 'Integer';
				break;
			case 'boolean':
				$type = 'Boolean';
				break;
			case 'string':
				$type = 'String';
				break;
			case 'double':
				$type = 'Double';
				break;
			case 'dateTime':
			case 'dateTime.iso8601':
				$type = 'Datetime';
				break;
			case 'base64':
				$type = 'Base64';
				break;
			case 'struct':
				$type = 'Struct';
				break;
			case 'array':
				$type = 'Array';
				break;
			case 'nil':
				$type = 'Nil';
				break;
			default:
				$type = FALSE;
				break;
		}
		
		return $type;
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * constructor
	 */
	public function  __construct($value, $type)
	{
		$this->{'_decode' . ucfirst($type)}($value);
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Get the value
	 * 
	 * @access public
	 * @return string
	 */
	public function getValue()
	{
		return $this->_value;
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * unescape character
	 * 
	 * @access protected
	 * @param  string $str
	 * @return string
	 */
	protected function _unEscape($str)
	{
		$grep  = array('&amp;', '&lt;', '&gt;');
		$sed   = array('&', '<', '>');
		
		return str_replace($grep, $sed, $str);
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Decode integer parameter
	 * 
	 * @access protected
	 * @param  int $value
	 */
	protected function _decodeInteger($value)
	{
		$this->_value = (int)$value->firstChild->nodeValue;
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Decode boolean parameter
	 * 
	 * @access protected
	 * @param  bool $value
	 */
	protected function _decodeBoolean($value)
	{
		$this->_value = (bool)$value->firstChild->nodeValue;
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Decode string parameter
	 * 
	 * @access protected
	 * @param  string $value
	 */
	protected function _decodeString($value)
	{
		$this->_value = $this->_unEscape((string)$value->firstChild->nodeValue);
	}
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Decode double parameter
	 * 
	 * @access protected
	 * @param  float $value
	 */
	protected function _decodeDouble($value)
	{
		$this->_value = (float)$value->firstChild->nodeValue;
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Decode array parameter
	 * 
	 * @access protected
	 * @param  array $value
	 */
	protected function _decodeArray($value)
	{
		$Class        = get_class($this);
		$this->_value = array();
		$dataElement  = $value->firstChild->firstChild; // <data> element
		
		foreach ( $dataElement->childNodes as $aryValue )
		{
			$type = self::detectType($aryValue);
			$v    = new $Class($element, $type);
			
			$this->_value[] = $v->getValue();
		}
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Decode base64-encoded parameter
	 * 
	 * @access protected
	 * @param  object $value
	 */
	protected function _decodeBase64($value)
	{
		$serialized   = base64_decode($value->firstChild->nodeValue);
		$this->_value = @unserialize($serialized);
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Decode struct parameter
	 * 
	 * @access protected
	 * @param  array $value
	 */
	protected function _decodeStruct($value)
	{
		$Class = get_class($this);
		$this->_value = new stdClass;
		
		foreach ( $value->firstChild->childNodes as $memberElement )
		{
			$key = '';
			$val = '';
			foreach ( $memberElement->childNodes as $node )
			{
				if ( $node->tagName === 'member' )
				{
					$key = $this->_unEscape((string)$node->nodeValue);
				}
				else if ( $node->tagName === 'value' )
				{
					$type = self::detectType($node);
					$v    = new $Class($node, $type);
					$val  = $v->getValue();
				}
			}
			
			if ( ! empty($key) && ! empty($val) )
			{
				$this->_value->{$key} = $val;
			}
		}
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Decode Null parameter
	 * 
	 * @access protected
	 * @param  null $value
	 */
	protected function _decodeNil($value)
	{
		$this->_value = NULL;
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Decode datetime-formatted parameter
	 * 
	 * @access protected
	 * @param  mixed $value
	 */
	protected function _decodeDatetime($value)
	{
		$this->_value = (string)$value->firstChild->nodeValue;
	}
}

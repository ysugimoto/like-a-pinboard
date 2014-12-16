<?php if ( ! defined('SZ_EXEC') ) exit('access_denied');

/**
 * ====================================================================
 * 
 * Seezoo-Framework
 * 
 * A simple MVC/action Framework on PHP 5.1.0 or newer
 * 
 * 
 * Compatible json_encode/json_decode function supply
 * 
 * @package  Seezoo-Framework
 * @category System
 * @author   Yoshiaki Sugimoto <neo.yoshiaki.sugimoto@gmail.com>
 * @license  MIT Licence
 * 
 * algorithm base:
 * @see http://www.json.org/
 * 
 * Rewrited json2.js for PHP!
 * 
 * ====================================================================
 */


class JSON
{
	/**
	 * meta characters
	 * @var array
	 */
	private $_escChars = array(
		'"' => '"',    // quotation mark
		'\\'=> '\\',   // reverse solidus
		'/' => '/',    // solidus
		'b' => "\b",    // backspace
		'f' => "\f",    // formfeed
		'n' => "\n",    // newline
		'r' => "\r",    // carriage return
		't' => "\t"     // horizontal tab
	);
	
	// encode parameters
	private $gap;
	private $index;
	
	// decode parameters
	private $point;
	private $currentChar;
	private $_textValue;
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * decode from json string
	 * 
	 * @access public static
	 * @param  string $str
	 * @param  bool $assoc
	 * @return mixed
	 */
	public static function decode($str, $assoc = FALSE)
	{
		$json = new self();
		return $json->_decode($str, $assoc);
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * encode json string fomat
	 * 
	 * @access public static
	 * @param  mixed $value
	 * @return string
	 */
	public static function encode($value)
	{
		$json = new self();
		return $json->_encode($value);
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * class constructror
	 * 
	 * @access private
	 */
	private function __construct()
	{
		$this->gap   = '';
		$this->index = '';
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * decode dispatcher method
	 * 
	 * @access private
	 * @param  string $str
	 * @param  bool $assoc
	 * @return mixed
	 */
	private function _decode($str, $assoc)
	{
		$decoded = $this->_parse($str);
		if ( $assoc === TRUE )
		{
			return ( ! is_object($decoded) )
			         ? get_object_vars($decoded)
			         : $decoded;
		}
		return $decoded;
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * encode dispatcher method
	 * 
	 * @access private
	 * @param  string $str
	 */
	private function _encode($str)
	{
		return $this->_strstr('', array('' => $str));
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * build json string format process
	 * 
	 * @access private
	 * @param  mixed $key
	 * @param  array $data
	 */
	private function _strstr($key, $data)
	{
		$m   = $this->gap;
		$val = $data[$key];
		$pat = array();
		
		if ( $val === FALSE || $val === TRUE )
		{
			return ( $val ) ? 'true' : 'false';
		}
		else if ( is_string($val) )
		{
			return $this->_quote($val);
		}
		else if ( is_float($val) )
		{
			return ( ! is_infinite($val) ) ? (float)$val : 'null';
		}
		else if ( is_int($val) )
		{
			return ( ! is_infinite($val) ) ? (int)$val : 'null';
		}
		else if ( is_null($val) )
		{
			return 'null';
		}
		else if ( $this->_isNumberingArray($val) )
		{
			if ( empty($val) )
			{
				return 'null';
			}
			$this->gap .= $this->index;
			foreach ( $val as $k => $v )
			{
				$tr = $this->_strstr($k, $val);
				$pat[$k] = ( $tr ) ? $tr : 'null';
			}
			
			if ( count($pat) === 0 )
			{
				$ret = '[]';
			}
			else
			{
				$ret = ( ! empty($this->gap) )
				         ? "[\n" . $this->gap . implode(",\n" . $this->gap, $pat) . "\n" . $m . ']'
				         : '[' . implode(',', $pat) . ']';
			}
			$this->gap = $m;
			return $ret;
		}
		else
		{
			if ( empty($val) )
			{
				return 'null';
			}
			$this->gap .= $this->index;
			$val = ( is_object($val) ) ? get_object_vars($val) : $val;
			foreach ( $val as $k => $v )
			{
				$tmp = $this->_strstr($k, $val);
				$pat[] = $this->_quote($k) . (( $this->gap ) ? ': ' : ':') . $tmp;
			}
			
			if ( count($pat) === 0 )
			{
				$ret = '{}';
			}
			else
			{
				$ret = ( $this->gap )
				         ? "{\n" . $this->gap . implode(",\n" . $this->gap, $pat) . "\n" . $m . '}'
				         : '{' . implode(',', $pat) . '}';
				$this->gap = $m;
			}
			return $ret;
		}
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * parse json string process
	 * 
	 * @access private
	 * @param  string $str
	 */
	private function _parse($str)
	{
		$this->_textValue = $str;
		$this->currentChar = ' ';
		
		$result = $this->_value();
		$this->_white();
		if ( $this->currentChar )
		{
			throw new Exception('JSON String Syntax Error.');
		}
		return $result;
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * step next character
	 * 
	 * @access private
	 * @param  string $char
	 * @throws Exception
	 */
	private function _next($char = null)
	{
		if ( $char && $char !== $this->currentChar )
		{
			throw new Exception("Expected '" . $char . "' instead of '" . $this->currentChar .  "'");
		}
		
		$this->currentChar = ( isset($this->_textValue[$this->point]) )
		                       ? $this->_textValue[$this->point++]
		                       : null;

		return $this->currentChar;
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * parse number
	 * 
	 * @access private
	 */
	private function _number()
	{
		$str = '';
		if ( $this->currentChar === '-' )
		{
			$str = '-';
			$this->_next('-');
		}
		while ( ctype_digit($this->currentChar) && $this->currentChar >= 0 && $this->currentChar <= 9 )
		{
			$str .= $this->currentChar;
			$this->_next();
		}
		if ( $this->currentChar === '.' )
		{
			$str .= '.';
			while ( $this->_next() && $this->currentChar >= 0 && $this->currentChar <= 9 )
			{
				$str .= $this->currentChar;
			}
		}
		if ( $this->currentChar === 'e' || $this->currentChar === 'E' )
		{
			$str .= $this->currentChar;
			$this->_next();
			if( $this->currentChar === '-' || $this->currentChar === '+')
			{
				$str .= $this->currentChar;
				$this->_next();
			}
			while( $this->currentChar >= 0 && $this->currentChar <= 9 )
			{
				$str .= $this->currentChar;
				$this->_next();
			}
		}
		
		if ( is_infinite($str) )
		{
			throw new Exception('illegal number exists.');
		}
		return $str;
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * parse string
	 * 
	 * @access private
	 */
	private function _string()
	{
		$str = '';
		if ( $this->currentChar === '"' )
		{
			while ( $this->_next() )
			{
				if ( $this->currentChar === '"' )
				{
					$this->_next();
					return $str;
				}
				else if ( $this->currentChar === '\\' )
				{
					$this->_next();
					if ( $this->currentChar === 'u' )
					{
						$uffff = '';
						for ( $i = 0; $i < 4; $i++ )
						{
							$uffff .= $this->_next();
						}
						$str .= mb_convert_encoding(pack('H*', $uffff), 'UTF-8', 'UTF-16');
						//$str .= $this->_unicodeUnEscape($uffff);
					}
					else if ( isset($this->_escChars[$this->currentChar]) )
					{
						$str .= $this->_escChars[$this->currentChar];
					}
					else
					{
						break;
					}
				}
				else
				{
					$str .= $this->currentChar;
				}
			}
		}
		throw new Exception('Illegal string exists.');
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * parse white space
	 * 
	 * @access private
	 */
	private function _white()
	{
		while ( $this->currentChar && $this->currentChar <= ' ' )
		{
			$this->_next();
		}
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * parse true/false/null string
	 * 
	 * @access private
	 * @return mixed
	 */
	private function _word()
	{
		$nexts = FALSE;
		$ret   = TRUE;
		switch ( $this->currentChar )
		{
			case 't':
				$nexts = array('t', 'r', 'u', 'e');
				break;
			case 'T':
				$nexts = array('T', 'R', 'U', 'E');
				break;
			case 'f':
				$nexts = array('f', 'a', 'l', 's', 'e');
				break;
			case 'F':
				$nexts = array('F', 'A', 'L', 'S', 'E');
				break;
			case 'n':
				$nexts = array('n', 'u', 'l', 'l');
				$ret   = NULL;
				break;
			case 'N':
				$nexts = array('N', 'U', 'L', 'L');
				$ret   = NULL;
				break;
			default:
				throw new Exception('Unexpected "' . $this->currentChar . '"');
				break;
		}
		foreach ( $nexts as $char )
		{
			$this->_next($char);
		}
		return $ret;
		
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * parse array
	 * 
	 * @access private
	 * @return mixed
	 */
	private function _array()
	{
		$arr = array();
		if ( $this->currentChar === '[' )
		{
			$this->_next('[');
			$this->_white();
			if ( $this->currentChar === ']' )
			{
				$this->_next(']');
				return $arr;
			}
			while ( $this->currentChar )
			{
				$arr[] = $this->_value();
				$this->_white();
				if ( $this->currentChar === ']' )
				{
					$this->_next(']');
					return $arr;
				}
				$this->_next(',');
				$this->_white();
			}
		}
		throw new Exception('Illegal array format exsits.');
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * parse object
	 * 
	 * @access private
	 */
	private function _object()
	{
		$obj = new stdClass;
		if ( $this->currentChar === '{' )
		{
			$this->_next('{');
			$this->_white();
			if ( $this->currentChar === '}' )
			{
				$this->_next('}');
				return $obj;
			}
			while ( $this->currentChar )
			{
				$key = $this->_string();
				$this->_white();
				$this->_next(':');
				$obj->{$key} = $this->_value();
				$this->_white();
				if ( $this->currentChar === '}' )
				{
					$this->_next('}');
					return $obj;
				}
				$this->_next(',');
				$this->_white();
			}
		}
		throw new Exception('Illegal object format exsits.');
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * detect get value
	 * 
	 * @access private
	 * @return mixed
	 */
	private function _value()
	{
		$this->_white();
		switch ( $this->currentChar )
		{
			case '{':
				return $this->_object();
			case '[':
				return $this->_array();
			case '"':
				return $this->_string();
			case '-':
				return $this->_number();
			default:
				return ( ctype_digit($this->currentChar) && $this->currentChar >= 0 && $this->currentChar <= 9 )
				         ? $this->_number()
				         : $this->_word();
		}
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * check array is strict numbering array
	 * 
	 * @access private
	 * @param  array $arr
	 * @return bool
	 */
	private function _isNumberingArray($arr)
	{
		if ( ! is_array($arr) )
		{
			return FALSE;
		}
		
		$i = -1;
		foreach ( $arr as $key => $val )
		{
			if ( $key != ++$i )
			{
				return FALSE;
			}
		}
		return TRUE;
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * convert object to array
	 * 
	 * @access private
	 * @param  object $obj
	 * @return array
	 */
	private function _objectToArray($obj)
	{
		if ( ! is_object($obj) )
		{
			return $obj;
		}
		else
		{
			return get_object_vars($obj);
		}
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * create JSON quoted format
	 * 
	 * @access private
	 * @param  string $str
	 */
	private function _quote($str)
	{
		if ( ! preg_match('/((?:[^\x09\x0A\x0D\x20-\x7E]{3})+)/', $str) )
		{
			return '"' . $str . '"';
		}
		
		$utf16 = mb_convert_encoding($str, 'UTF-16', 'UTF-8');
		$ret   = '';
		$len   = strlen($utf16);
		$table = array();
		for ( $i = 0; $i < $len; $i += 2 )
		{
			$hex = $utf16[$i] . $utf16[$i + 1];
			if ( ! isset($table[$hex]) )
			{
				$table[$hex] = "\u" . sprintf("%02x%02x", ord($utf16[$i]), ord($utf16[$i + 1]));
			}
			$ret .= $table[$hex];
		}
		$table = null;
			
		return $ret;
	}
}
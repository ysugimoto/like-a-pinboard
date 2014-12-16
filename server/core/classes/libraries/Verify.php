<?php if ( ! defined('SZ_EXEC') ) exit('access_denied');

/**
 * ====================================================================
 * 
 * Seezoo-Framework
 * 
 * A simple MVC/action Framework on PHP 5.1.0 or newer
 * 
 * 
 * Variable verification method
 * 
 * @package  Seezoo-Framework
 * @category Library
 * @author   Yoshiaki Sugimoto <neo.yoshiaki.sugimoto@gmail.com>
 * @license  MIT Licence
 * 
 * ====================================================================
 */

class SZ_Verify implements Growable
{
	/**
	 * Error messages
	 * @var array
	 */
	public $messages = array(
		'alnum'       => '%sは半角英数で入力してください。',
		'alunm_dash'  => '%sは半角英数で入力してください。',
		'alpha'       => '%sは半角英字で入力してください。',
		'alpha_dash'  => '%sは半角英字で入力してください。',
		'alpha_lower' => '%sは半角英小文字で入力してください。',
		'alpha_upper' => '%sは半角英大文字で入力してください。',
		'dateformat'  => '%sの日付の形式が正しくありません。',
		'exact_date'  => '%sには実在する日付を入力してください。',
		'future_date' => '%sに過去の日付は指定できません。',
		'hiragana'    => '%sはひらがなで入力してください。',
		'kana'        => '%sはカタカナで入力してください。',
		'max_length'  => '%sは%s文字以内で入力してください。',
		'min_length'  => '%sは%s文字以上で入力してください。',
		'numeric'     => '%sは数値で入力してください。',
		'past_date'   => '%sに未来の日付は指定できません。。',
		'range'       => '%sは%sから%sの間で指定してください。',
		'telnumber'   => '%sの形式が正しくありません',
		'unsigned'    => '%sに正の数値を入力してください。',
		'zipcode'     => '%sの郵便番号の形式が正しくありません。',
		'required'    => '%sは必須入力です。',
		'blank'       => '%sは空欄にしてください。',
		'ctype'       => '%sは半角数字で入力してください。',
		'valid_email' => '%sのメールアドレス形式が正しくありません。',
		'valid_url'   => '%sのURL形式が正しくありません。',
		'regex'       => '%sの形式が正しくありません。',
		'matches'     => '%sの値が%sと一致しません。'
	);
	
	
	/**
	 * Growable interface implementation
	 * 
	 * @access public static
	 * @return SZ_Verify ( extended )
	 */
	public static function grow()
	{
		return Seezoo::$Importer->library('Verify');
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * aet or override message
	 * 
	 * @access public
	 * @param  string $str
	 */
	public function setMessage($key, $msg)
	{
		$this->messages[$key] = $msg;
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * Value is required
	 * 
	 * @access public
	 * @param  string $str
	 * @return bool
	 */
	public function required($str)
	{
		return ( $str !== '' ) ? TRUE : FALSE; 
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * Value is blank only
	 * 
	 * @access public
	 * @param  string $str
	 * @return bool
	 */
	public function blank($str)
	{
		return ( $str === '' ) ? TRUE : FALSE;
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * Value is valid email format and dns exists
	 * 
	 * @access public
	 * @param  string $str
	 * @return bool
	 */
	public function valid_email($str)
	{
		if ( function_exists('filter_var') )
		{
			if ( ! filter_var($str, FILTER_VALIDATE_EMAIL) )
			{
				return FALSE;
			}
		}
		else if ( ! preg_match("/^([a-z0-9\+_\-]+)(\.[a-z0-9\+_\-]+)*@([a-z0-9\-]+\.)+[a-z]{2,6}$/iD", $str) )
		{
			return FALSE;
		}
		
		if ( function_exists('checkdnsrr') )
		{
			list(, $host) = explode('@', $str);
			if ( ! checkdnsrr($host, 'MX') && ! checkdnsrr($host, 'A') )
			{
				return FALSE;
			}
		}
		return TRUE;
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * Value is valid URI format
	 * 
	 * @access public
	 * @param  string $str
	 * @return bool
	 */
	
	public function valid_url($str)
	{
		if ( function_exists('filter_var') )
		{
			return (bool)filter_var($str, FILTER_VALIDATE_URL);
		}
		return ( preg_match('/\A(https?|ftp)(:\/\/[-_.!~*\'()a-zA-Z0-9;\/?:\@&=+\$,%#]+)\Z/u', $str) ) ? TRUE : FALSE;
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * Value is strcit integer format ( 0-9 digits )
	 * 
	 * @access public
	 * @param  string $str
	 * @return bool
	 */
	public function ctype($str)
	{
		return ctype_digit($str);
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * return trimmed value
	 * 
	 * @access public
	 * @param  string $str
	 * @return bool
	 */
	public function trim($str)
	{
		return trim($str);
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * Value length less than condition length
	 * 
	 * @access public
	 * @param  string $str
	 * @param  string $length
	 * @return bool
	 */
	public function max_length($str, $length)
	{
		if ( function_exists('mb_strlen') )
		{
			$len = mb_strlen($str, 'UTF-8');
		}
		else
		{
			$len = strlen($str);
		}
		
		return ( $len <= (int)$length ) ? TRUE : FALSE; 
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * Value length greater than condition length
	 * 
	 * @access public
	 * @param  string $str
	 * @param  string $length
	 * @return bool
	 */
	public function min_length($str, $length)
	{
		if ( function_exists('mb_strlen') )
		{
			$len = mb_strlen($str, 'UTF-8');
		}
		else
		{
			$len = strlen($str);
		}
		
		return ( $len >= (int)$length ) ? TRUE : FALSE; 
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * Value is match in regex
	 * 
	 * @access public
	 * @param  string $str
	 * @param  string $regex
	 * @return bool
	 */
	public function regex($str, $regex)
	{
		return ( preg_match('#' . str_replace('#', '\#', $regex) . '#u', $str) ) ? TRUE : FALSE;
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * Value in range digit
	 * 
	 * @access public
	 * @param  string $str
	 * @param  string $range
	 * @return bool
	 */
	public function range($str, $range)
	{
		list($min, $max) = explode(':', $range);
		return ( (int)$min <= (int)$str && (int)$max >= (int)$str ) ? TRUE : FALSE;
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * Value is Alpha chars only
	 * 
	 * @access public
	 * @param  string $str
	 * @return bool
	 */
	public function alpha($str)
	{
		return ( preg_match('/\A[a-zA-Z]+\Z/u', $str) ) ? TRUE : FALSE; 
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * Value is Alpha-numeric chars only
	 * 
	 * @access public
	 * @param  string $str
	 * @return bool
	 */
	public function alnum($str)
	{
		return ( preg_match('/\A[a-zA-Z0-9]+\Z/u', $str) ) ? TRUE : FALSE;
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * Value is Alpha-numeric and dash chars only
	 * 
	 * @access public
	 * @param  string $str
	 * @return bool
	 */
	public function alnum_dash($str)
	{
		return ( preg_match('/\A[a-zA-Z0-9\-_]+\Z/u', $str) ) ? TRUE : FALSE;
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * Value is Alpha and dash chars only
	 * 
	 * @access public
	 * @param  string $str
	 * @return bool
	 */
	public function alpha_dash($str)
	{
		return ( preg_match('/\A[a-zA-Z\-_]+\Z/u', $str) ) ? TRUE : FALSE;
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * Value is Lowercase-Alpha chars only
	 * 
	 * @access public
	 * @param  string $str
	 * @return bool
	 */
	public function alpha_lower($str)
	{
		return ( preg_match('/\A[a-z]+\Z/u', $str) ) ? TRUE : FALSE;
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * Value is Upercase-Alpha chars only
	 * 
	 * @access public
	 * @param  string $str
	 * @return bool
	 */
	public function alpha_upper($str)
	{
		return ( preg_match('/\A[A-Z]+\Z/u', $str) ) ? TRUE : FALSE;
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * Value is numeric chars only
	 * 
	 * @access public
	 * @param  string $str
	 * @return bool
	 */
	public function numeric($str)
	{
		return is_numeric($str);
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * Value is unsigned numeric chars only
	 * 
	 * @access public
	 * @param  string $str
	 * @return bool
	 */
	public function unsigned($str)
	{
		return ( is_numeric($str) && (int)$str > 0 ) ? TRUE : FALSE;
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * Value is telnumber format only
	 * 
	 * @access public
	 * @param  string $str
	 * @return bool
	 */
	public function telnumber($str)
	{
		return ( preg_match('/\A[0-9]{2,4}\-[0-9]{3,4}\-[0-9]{4}\Z/u', $str) )? TRUE : FALSE;
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * Value is "kana" chars only
	 * 
	 * @access public
	 * @param  string $str
	 * @return bool
	 */
	public function kana($str)
	{
		$str = str_replace('　', ' ', $str);
		return ( preg_match("/\A[ァ-ヴー\s]+$/u", $str) ) ? TRUE : FALSE;
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * Value is "hiragana" chars only
	 * 
	 * @access public
	 * @param  string $str
	 * @return bool
	 */
	public function hiragana($str)
	{
		$str = str_replace('　', ' ', $str);
		return ( preg_match("/^[ぁ-ゞ]+$/u", $str) ) ? TRUE : FALSE;
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * convert kana
	 * 
	 * @access public
	 * @param  string $str
	 * @return string
	 */
	public function conv_kana($str, $cond)
	{
		return mb_convert_kana($str, $cond);
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * convert number
	 * 
	 * @access public
	 * @param  string $str
	 * @return string
	 */
	public function conv_num($str)
	{
		return str_repalce(
		                 array('０', '１', '２', '３', '４', '５', '６', '７', '８', '９'),
		                 array('0', '1', '2', '3', '4', '5', '6', '7', '8', '9'),
		                 $str
		                 );
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * Value is Zipcode-format only
	 * 
	 * @access public
	 * @param  string $str
	 * @return bool
	 */
	public function zipcode($str)
	{
		return ( preg_match('/\A[0-9]{3}\-[0-9]{4}\Z/u', $str) ) ? TRUE : FALSE;
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * Value is Date-format only
	 * 
	 * @access public
	 * @param  string $str
	 * @param  string $cond
	 * @return bool
	 */
	public function dateformat($str, $cond)
	{
		if ( ! $cond )
		{
			$cond = '-';
		}
		$sep = preg_quote($cond, '/');
		return ( preg_match('/\A[0-9]{4}' . $sep . '[0-9]{2}' . $sep . '[0-9]{2}\Z/u', $str) ) ? TRUE : FALSE;
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * Value is past-date only
	 * 
	 * @access public
	 * @param  string $str
	 * @return bool
	 */
	public function past_date($str)
	{
		return ( strtotime($str) < time() ) ? TRUE : FALSE;
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * Value is future date only
	 * 
	 * @access public
	 * @param  string $str
	 * @return bool
	 */
	public function future_date($str)
	{
		return ( strtotime($str) >= time() ) ? TRUE : FALSE;
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * Value is exact date only
	 * 
	 * @access public
	 * @param  string $str
	 * @param  string $cond
	 * @return bool
	 */
	public function exact_date($str, $cond)
	{
		if ( ! $cond )
		{
			$cond = '-';
		}
		$exp = explode($cond, $str);
		return checkdate($exp[1], $exp[2], $exp[0]);
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * Value is matched other field value
	 * 
	 * @access public
	 * @param  string $str
	 * @param  string $cond
	 * @return bool
	 */
	public function matches($str, $cond)
	{
		return ( $str === $cond ) ? TRUE : FALSE;
	}
}
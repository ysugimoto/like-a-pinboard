<?php if ( ! defined('SZ_EXEC') ) exit('access_denied');
/**
* ========================================================
*
* Yaml parser
*
* This library tuned oroginal spyc http://code.google.com/p/spyc/
*
* @author Yoshiaki Sugimoto <neo.yoshiaki.sugimoto@gmail.com>
* @license http://www.opensource.org/licenses/mit-license.php MIT License
*
* ========================================================
*/

# Original algorithm
#
#    S P Y C
#      a simple php yaml class
#
# authors: [vlad andersen (vlad.andersen@gmail.com), chris wanstrath (chris@ozmm.org)]
# websites: [http://www.yaml.org, http://spyc.sourceforge.net/]
# license: [MIT License, http://www.opensource.org/licenses/mit-license.php]
# copyright: (c) 2005-2006 Chris Wanstrath, 2006-2011 Vlad Andersen
#
# spyc.yml - A file containing the YAML that Spyc understands.

class Yaml
{
	private static $path        = array();
	private static $delayedPath = array();
	private static $result      = array();
	private static $savedGroups = array();
	
	// Dymnamic group static status
	private static $groupAnchor = FALSE;
	private static $groupAlias  = FALSE;
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Parse Yaml string from file
	 * 
	 * @access public static
	 * @param  string $file
	 * @return array
	 */
	public static function loadFile($file)
	{
		if ( ! file_exists($file) )
		{
			throw new InvalidArgumentException('Yaml file is not exists.');
		}
		
		return self::_parseString(file($file));
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Parse Yaml string
	 * 
	 * @access public static
	 * @param  string $string
	 * @return array
	 */
	public static function loadString($string)
	{
		return self::_parseString(explode("\n", $string));
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Parse Yaml-formatted string
	 * 
	 * @access private static
	 * @param  array $line
	 * @return array
	 */
	private static function _parseString($lines)
	{
		self::$path        =
		self::$result      = 
		self::$delayedPath =
		self::$savedGroups = array();
		self::$groupAlias  =
		self::$groupAnchor = FALSE;

		$count = count($lines);
		for ( $i = 0; $i < $count; ++$i )
		{
			$line   = $lines[$i];
			$indent = $idt = strlen($line) - strlen(ltrim($line));
			
			// getParentPathByIndent extracts inline
			if ( $indent === 0 )
			{
				$tmpPath = array();
			}
			else
			{
				$tmpPath = self::$path;
				do
				{
					end($tmpPath);
					if ( $indent <= ($lastIndentPath = key($tmpPath)) )
					{
						unset($tmpPath[$lastIndentPath]);
					}
				}
				while ( $indent <= $lastIndentPath );
			}
			
			if ( $indent === -1 )
			{
				$idt = strlen($line) - strlen(ltrim($line));
			}
			$line    = substr($line, $idt);
			$tmpLine = trim($line);
			// line string is commented or empty section?
			if ( $tmpLine === ''
			     || $line[0] === '#'
			     || trim($line, " \r\n\t") === '---' )
			{
				continue;
			}
			self::$path        = $tmpPath;
			$lastChar          = substr($tmpLine, -1);
			$literalBlockStyle = ( ($lastChar !== '>' && $lastChar !== '|') || preg_match('#<.*?>$#', $line) ) ? FALSE : $lastChar;
			if ( $literalBlockStyle )
			{
				$literalBlock       = '';
				$line               = rtrim($line, $literalBlockStyle . " \n") . '__SPICYYAML__';
				$literalBlockIndent = strlen($lines[++$i]) - strlen(ltrim($lines[$i--]));
				while ( ++$i < $count
				       && ( ! trim($lines[$i]) || (strlen($lines[$i]) - strlen(ltrim($lines[$i]))) > $indent ) )
				{
					$tmpLine = $lines[$i];
					$tmpLineTrimedDistance = strlen($tmpLine) - strlen(ltrim($tmpLine));
					$tmpLine = substr($tmpLine, ( $literalBlockIndent === -1 ) ? $tmpLineTrimedDistance : $literalBlockIndent);
					
					if ( $literalBlockStyle !== '|' )
					{
						$tmpLine = substr($tmpLine, $tmpLineTrimedDistance);
					}
					$tmpLine = rtrim($tmpLine, "\r\n\t ") . "\n";
					if ( $literalBlockStyle === '|' )
					{
						$literalBlock .= $tmpLine;
					}
					else if ( $tmpLine == "\n" && $literalBlockStyle === '>' )
					{
						$literalBlock = rtrim($literalBlock, " \t") . "\n";
					}
					else if ( strlen($tmpLine) === 0 )
					{
						$literalBlock = rtrim($literalBlock, ' ') . "\n";
					}
					else
					{
						if ( $tmpLine !== "\n" )
						{
							$tmpLine = trim($tmpLine, "\r\n ") . " ";
						}
						$literalBlock .= $tmpLine;
					}
				}
				--$i;
			}
			
			while ( ++$i < $count )
			{
				$tmpLine = trim($line);
				if ( ! strlen($tmpLine) || substr($tmpLine, -1, 1) === ']' )
				{
					break;
				}
				if ( $tmpLine[0] === '[' || preg_match('#^[^:]+?:\s*\[#', $tmpLine))
				{
					$line = rtrim($line, " \n\t\r") . ' ' . ltrim($lines[$i], " \t");
					continue;
				}
				break;
				
			}
			--$i;
			
			if ( strpos($line, '#')
			     && strpos($line, '"') === FALSE
			     && strpos($line, '\'') === FALSE )
			{
				$line = preg_replace('/\s+#(.+)$/', '', $line);
			}
			
			$lineArray = ( ! $line || ! ($line = trim($line))) ? array() : self::_parseLine($line, $indent);
			if ( $literalBlockStyle )
			{
				$lineArray = self::_revertLiteralPlaceHolder($lineArray, $literalBlock);
			}
			
			if ( count($lineArray) > 1 )
			{
				// addArrayInline inline
				$groupPath = self::$path;
				foreach ( $lineArray as $k => $v )
				{
					self::_addArray(array($k => $v), $indent);
					self::$path = $groupPath;
				}
			}
			else
			{
				self::_addArray($lineArray, $indent);
			}
			
			foreach ( self::$delayedPath as $idt => $delayedPath )
			{
				self::$path[$idt] = $delayedPath;
			}
			
			self::$delayedPath = array();
		}
		return self::$result;
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Parse single line
	 * 
	 * @access private static
	 * @param  string $line
	 * @param  int    $indent
	 * @return mixed
	 */
	private static function _parseLine($line, $indent)
	{
		$ret    = array();
		if ( (($line[0] === '&' || $line[0] === '*') && preg_match('/^(&[A-z0-9_\-]+|\*[A-z0-9_\-]+)/', $line, $match))
		     || preg_match('/(&[A-z0-9_\-]+|\*[A-z0-9_\-]+)$/', $line, $match)
		     || preg_match ('#^\s*<<\s*:\s*(\*[^\s]+).*$#', $line, $match) )
		{
			// Add group
			if ( $match[1][0] === '&' )
			{
				self::$groupAnchor = substr($match[1], 1);
			}
			else if ( $match[1][0] === '*' )
			{
				self::$groupAlias = substr($match[1], 1);
			}
			$line = trim(str_replace($match[1], '', $line));
		}
		
		$last = substr($line, -1, 1);
		
		// Mapped sequence
		if ( $line[0] === '-' && $last === ':' )
		{
			if ( ($key = trim(substr($line, 1, -1))) )
			{
				if ( $key[0] === '\'' )
				{
					$key = trim($key, '\'');
				}
				if ( $key[0] === '"' )
				{
					$key =  trim($key, '"');
				}
			}
			$ret[$key]         = array();
			self::$delayedPath = array((strpos($line, $key) + $indent) => $key);
			return array($ret);
		}
		// Mapped value
		if ( $last === ':' )
		{
			if ( ($key = trim(substr($line, 0, -1))) )
			{
				if ( $key[0] === '\'' )
				{
					$key = trim($key, '\'' );
				}
				if ( $key[0] === '"' )
				{
					$key =  trim($key, '"');
				}
			}
			$ret[$key] = '';
			return $ret;
		}
		// Array element
		if ( $line
		     && $line[0] === '-'
		     && ! (($tmpLen = strlen($line)) > 3 && substr($line, 0, 3) === '---') )
		{
			if ( $tmpLen <= 1 )
			{
				$ret = array(array());
			}
			else
			{
				$ret[]  = self::_toType(trim(substr($line, 1)));
			}
			return $ret;
		}
		// Plain array
		if ( $line[0] === '[' && $last === ']' )
		{
			return self::_toType($line);
		}
		
		// getKeyValuePair inline
		if ( strpos($line, ':') )
		{
			if ( ($line[0] === '"' || $line[0] === '\'')
			     && preg_match('#^(["\'](.*)["\'](\s)*:)#', $line, $match) )
			{
				$val = trim(str_replace($match[1], '', $line));
				$key = $match[2];
			}
			else
			{
				$point = strpos($line, ':');
				$key   = trim(substr($line, 0, $point));
				$val   = trim(substr($line, ++$point));
			}
			
			if ( $key === '0' ) 
			{
				$key = '__SPICYZERO__';
			}
			$ret[$key] = self::_toType($val);
		}
		else
		{
			$ret = array($line);
		}
		return $ret;
		
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Detect variable type
	 * 
	 * @access private static
	 * @param  string $str
	 * @return mixed
	 */
	private static function _toType($str)
	{
		if ( $str === '' )
		{
			return NULL;
		}
		$first    = $str[0];
		$last     = substr($str, -1, 1);
		$isQuoted = FALSE;

		do
		{
			if ( ! $str
			    || ($first !== '"' && $first !== '\'')
				|| ($last  !== '"' && $last  !== '\'') )
			{
				break;
			}
			$isQuoted = TRUE;
		}
		while ( 0 );
		
		if ( $isQuoted === TRUE )
		{
			return strtr(
				substr($str, 1, -1),
				array('\\"' => '"', '\'\'' => '\'', '\\\'' => '\'')
			);
		}
		
		if ( strpos($str, ' #') !== FALSE && $isQuoted === FALSE )
		{
			$str = preg_replace('/\s+#(.+)$/', '', $str);
		}
		
		if ( $isQuoted === FALSE )
		{
			$str = str_replace('\n', "\n", $str);
		}
		
		if ( $first === '[' && $last === ']' )
		{
			if ( ($inner = trim(substr($str, 1, -1))) === '' )
			{
				return array();
			}
			$ret = array();
			foreach ( self::_inlineEscape($inner) as $v )
			{
				$ret[] = self::_toType($v);
			}
			return $ret;
		}
		
		if ( ($point = strpos($str, ': ')) !== FALSE && $first !== '{' )
		{
			return array(
				trim(substr($str, 0, $point)) => self::_toType(trim(substr($str, ++$point)))
			);
		}
		
		if ( $first === '{' && $last === '}' )
		{
			if ( ($inner = trim(substr($str, 1, -1))) === '' )
			{
				return array();
			}
			$ret = array();
			foreach ( self::_inlineEscape($inner) as $v )
			{
				$sub = self::_toType($v);
				if ( empty($sub) )
				{
					continue;
				}
				if ( is_array($sub) )
				{
					$k = key($sub);
					$ret[$k] = $sub[$k];
					continue;
				}
				$ret[] = $sub;
			}
			return $ret;
		}
		
		if ( $str === 'null' || $str === 'NULL' || $str === 'Null' || $str === '' || $str === '~' )
		{
			return NULL;
		}
		
		if ( is_numeric($str) )
		{
			if ( $str === '0' )
			{
				$str = 0;
			}
			else if ( preg_match('/^(-|)[1-9]+[0-9]*$/', $str) )
			{
				if ( ($int = (int)$str) != PHP_INT_MAX )
				{
					$str = $int;
				}
			}
			else if ( rtrim($str, 0) === $str )
			{
				$str = (float)$str;
			}
			return $str;
		}
		
		$lower = strtolower($str);
		if ( in_array($lower, array('true', 'on', '+', 'yes', 'y')) )
		{
			return TRUE;
		}
		
		if ( in_array($lower, array('false', 'off', '-', 'no', 'n')) )
		{
			return FALSE;
		}
		
		return $str;
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Inloine escape string
	 * 
	 * @access private static
	 * @param  string $val
	 * @return array
	 */
	private static function _inlineEscape($val)
	{
		$sequences = array();
		$maps      = array();
		$saved     = array();

		$strRegex      = '/(?:(")|(?:\'))((?(1)[^"]+|[^\']+))(?(1)"|\')/';
		$sequenceRegex = '/\[([^{}\[\]]+)\]/U';
		$mapRegex      = '/{([^\[\]{}]+)}/U';
		
		if ( preg_match_all($strRegex, $val, $strings) )
		{
			$saved = $strings[0];
			$val   = preg_replace($strRegex, '__SPICYSTRING__', $val);
		}

		$i = 0;
		do
		{
			while ( preg_match($sequenceRegex, $val, $matchseq) )
			{
				$sequences[] = $matchseq[0];
				$val         = preg_replace($sequenceRegex, ('__SPICYSEQUENCE__' . (count($sequences) - 1) . 's'), $val, 1);
			}
			
			while ( preg_match($mapRegex, $val, $matchmap) )
			{
				$maps[] = $matchmap[0];
				$val    = preg_replace($mapRegex, ('__SPICYMAP__' . (count($maps) - 1) . 's'), $val, 1);
			}
			
			if ( $i++ >= 10 )
			{
				break;
			}
		}
		while ( strpos($val, '[') !== FALSE || strpos($val, '{') !== FALSE );
		
		$exp  = explode(', ', $val);
		$stri = 0;
		$i    = 0;
		
		while ( TRUE )
		{
			if ( ! empty($sequences) )
			{
				foreach ( $exp as $k => $v )
				{
					if ( strpos($v, '__SPICYSEQUENCE__') !== FALSE )
					{
						foreach ( $sequences as $kk => $vv )
						{
							$v = $exp[$k] = str_replace(('__SPICYSEQUENCE__' . $kk . 's'), $vv, $v);
						}
					}
				}
			}
			
			if ( ! empty($maps) )
			{
				foreach ( $exp as $k => $v )
				{
					if ( strpos($v, '__SPICYMAP__') !== FALSE )
					{
						foreach ( $maps as $kk => $vv )
						{
							$v = $exp[$k] = str_replace(('__SPICYMAP__' . $kk . 's'), $vv, $v);
						}
					}
				}
			}
			
			if ( ! empty($saved) )
			{
				foreach ( $exp as $k => $v )
				{
					while ( strpos($v, '__SPICYSTRING__') !== FALSE )
					{
						$v = $exp[$k] = preg_replace('/__SPICYSTRING__/', $saved[$stri], $v, 1);
						unset($saved[$stri++]);
					}
				}
			}
			
			$finished = TRUE;
			foreach ( $exp as $k => $v )
			{
				if ( strpos($v, '__SPICYSEQUENCE__') !== FALSE
				     || strpos($v, '__SPICYMAP__') !== FALSE
				     || strpos($v, '__SPICYSTRING__') !== FALSE )
				{
					$finished = FALSE;
					break;
				}
			}
			if ( $finished || ++$i > 10 )
			{
				break;
			}
		}
		
		return $exp;
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Add to stack array
	 * 
	 * @access private static
	 * @param  array $lineArray
	 * @param  int   $indent
	 * @return void
	 */
	private static function _addArray($lineArray, $indent)
	{
		$key = key($lineArray);
		$val = ( isset($lineArray[$key]) ) ? $lineArray[$key] : NULL;
		if ( $key === '__SPICYZERO__' )
		{
			$key = '0';
		}
		
		if ( $indent === 0 && ! self::$groupAlias && ! self::$groupAnchor )
		{
			if ( $key || $key === '' || $key === '0' )
			{
				self::$result[$key] = $val;
			}
			else
			{
				self::$result[] = $val;
				end(self::$result);
				$key = key(self::$result);
			}
			self::$path[$indent] = $key;
			return;
		}
		
		$history[] = $_arr = self::$result;
		foreach ( self::$path as $path )
		{
			$history[] = $_arr = $_arr[$path];
		}
		
		if ( self::$groupAlias )
		{
			if ( ! isset(self::$savedGroups[self::$groupAlias]) )
			{
				throw new LogicException('Bad group name:' . self::$groupAlias . '.');
			}
			$val = self::$result;
			foreach ( self::$savedGroups[self::$groupAlias] as $g )
			{
				$val = $val[$g];
			}

			self::$groupAlias = FALSE;
		}
		
		if ( (string)$key === $key && $key === '<<' )
		{
			$_arr = ( is_array($_arr) ) ? array_merge($_arr, $val) : $val;
		}
		else if ( $key || $key === '' || $key === '0' )
		{
			if ( ! is_array($_arr) )
			{
				$_arr = array($key => $val);
			}
			else
			{
				$_arr[$key] = $val;
			}
		}
		else
		{
			if ( ! is_array($_arr) )
			{
				$_arr = array($val);
				$key  = 0;
			}
			else
			{
				$_arr[] = $val;
				end($_arr);
				$key = key($_arr);
			}
		}
		$reversePath       = array_reverse(self::$path);
		$reverseHistory    = array_reverse($history);
		$reverseHistory[0] = $_arr;
		$count             = count($reverseHistory) - 1;
		for ( $i = 0; $i < $count; ++$i )
		{
			$reverseHistory[$i + 1][$reversePath[$i]] = $reverseHistory[$i];
		}
		self::$result        = $reverseHistory[$count];
		self::$path[$indent] = $key;
		
		if ( self::$groupAnchor )
		{
			self::$savedGroups[self::$groupAnchor] = self::$path;
			if ( is_array($val) )
			{
				$k = key($val);
				if ( (int)$k !== $k )
				{
					self::$savedGroups[self::$groupAnchor][$indent + 2] = $k;
				}
			}
			self::$groupAnchor = FALSE;
		}
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Revert literal block
	 * 
	 * @access private static
	 * @param  array $lineArray
	 * @param  string $literalBlock
	 * @return array
	 */
	private static function _revertLiteralPlaceHolder($lineArray, $literalBlock)
	{
		foreach ( $lineArray as $key => $val )
		{
			if ( is_array($val) )
			{
				// recursively
				$lineArray[$key] = self::_revertLiteralPlaceHolder($val, $literalBlock);
			}
			else if ( substr($val, -13) === '__SPICYYAML__' )
			{
				$lineArray[$key] = rtrim($literalBlock, " \r\n");
			}
		}
		return $lineArray;
	}
	
}

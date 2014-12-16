<?php if ( ! defined('SZ_EXEC') ) exit('access_denied');

/**
 * ====================================================================
 * 
 * Seezoo-Framework
 * 
 * A simple MVC/action Framework on PHP 5.1.0 or newer
 * 
 * 
 * CSV helper
 * 
 * @package  Seezoo-Framework
 * @category Helpers
 * @author   Yoshiaki Sugimoto <neo.yoshiaki.sugimoto@gmail.com>
 * @license  MIT Licence
 * 
 * ====================================================================
 */

class SZ_CsvHelper implements Growable
{
	/**
	 * Growable interface implementation
	 * 
	 * @access public static
	 * @return View ( extended )
	 */
	public static function grow()
	{
		return Seezoo::$Importer->helper('Csv');
	}
	
	
	/**
	 * Make csv string ( store file or download )
	 * 
	 * @access public
	 * @param  mixed $data
	 * @param  string $path
	 * @param  string $delimiter
	 * @param  string $enc
	 * @param  bool $download
	 * @return mixed
	 */
	public function make($data, $path = '', $delimiter = ',', $enc = '"', $download = FALSE)
	{
		if ( is_object($data) )
		{
			$data = get_object_vars($data);
		}
		
		$csv = array();
		$enc = preg_quote($enc);
		foreach ( $data as $val )
		{
			if ( is_object($val) )
			{
				$val = get_object_vars($val);
			}
			$row = array();
			foreach ( $val as $v )
			{
				$row[] = $enc . preg_replace('/(' . $enc . ')/u' , '\\$1', $v) . $enc;
			}
			$csv[] = implode($delimiter, $row);
		}
		
		$output = implode("\n", $csv);
		$output = mb_convert_encoding($output, 'cp932', 'UTF-8');
		
		if ( $download === TRUE )
		{
			Seezoo::$Response->download($output, basename($path), TRUE);
			// Response::download cause exit.
		}
		
		try
		{
			$file = new SplFileObject($path, 'wb');
			$file->flock(LOCK_EX);
			$file->fwrite($output);
			$file->flock(LOCK_UN);
			return TRUE;
		}
		catch ( RuntimeException $e )
		{
			return FALSE;
		}
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Parse CSV
	 * @see http://yossy.iimp.jp/wp/?p=56 thanks!
	 * 
	 * @access public
	 * @param  resource $fp
	 * @param  string $delimiter
	 * @param  string $enc
	 * @return mixed
	 */
	public function parse($fp, $delimiter = ',', $enc = '"')
	{
		if ( ! is_resource($fp) )
		{
			throw new RuntimeException('First argument must be file pointer!');
		}
		
		$delimiter = preg_quote($delimiter);
		$enc       = preg_quote($enc);
		$line      = '';
		$eof       = FALSE;
		
		$regex = new stdClass;
		$regex->item = '/' . $enc . '/';
		$regex->line = '/(?:\\r\\n|[\\r\\n])?$/';
		$regex->grep = '/(' . $enc . '[^'. $enc . ']*(?:' . $enc . $enc . '[^'. $enc . ']*)*' . $enc . '|[^' . $delimiter . ']*)' . $delimiter . '/';
		$regex->sed  = '/\A' . $enc . '(.*)' . $enc . '\Z/s';
		
		while ( $eof !== TRUE && ! feof($file) )
		{
			$line .= fgets($file);
			$cnt   = preg_match_all($regex->item, $line, $tmp);
			if ( $cnt % 2 === 0) 
			{
				$eof = TRUE;
			}
		}
		
		fclose($file);
		
		$row = preg_replace($regex->line, $delimiter, trim($line));
		preg_match_all($regex->grep, $row, $matches);
		$csv = $matches[1];
		$cnt = count($csv);
		
		for ( $i = 0; $i < $cnt; $i++ )
		{
			$csv[$i] = preg_replace($regex->sed, '$1', $csv[$i]);
			$csv[$i] = str_replace($enc . $enc, $enc, $csv[$i]);
		}
		
		return ( ! empty($line) ) ? $csv : FALSE; 
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Parse CSV from file
	 * 
	 * @access public
	 * @param  string $file
	 * @param  string $delimiter
	 * @param  string $enc
	 * @return mixed array or FALSE
	 */
	public function parseFile($file, $delimiter = ',', $enc = '"')
	{
		if ( ! file_exists($file) )
		{
			return FALSE;
		}
		
		// open file
		$fp = @fopen($file, 'rb');
		if ( ! $fp )
		{
			return FALSE;
		}
		$csv = array();
		while ( FALSE !== ($line = $this->parse($fp, $delimiter, $enc)) )
		{
			$csv[] = $line;
		}
		return $csv;
	}
}
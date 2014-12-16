<?php if ( ! defined('SZ_EXEC') ) exit('access_denied');

/**
 * ====================================================================
 * 
 * Seezoo-Framework
 * 
 * A simple MVC/action Framework on PHP 5.1.0 or newer
 * 
 * 
 * Mail decoder
 * 
 * @package  Seezoo-Framework
 * @category Drivers
 * @author   Yoshiaki Sugimoto <neo.yoshiaki.sugimoto@gmail.com>
 * @license  MIT Licence
 * 
 * ====================================================================
 */
class SZ_Mail_decoder
{
	/**
	 * Non-decoed plain mail string
	 * @var string
	 */
	protected $plainMail = '';
	
	
	/**
	 * Decoded headers
	 * @var array
	 */
	protected $headers = array();
	
	
	/**
	 * Non-decoed plain mail header string
	 * @var string
	 */
	protected $plainHeader = '';
	
	
	/**
	 * Mail UID
	 * @var int
	 */
	protected $UID = 0;
	
	
	/**
	 * Decoded body
	 * @var string
	 */
	protected $body = '';
	
	
	/**
	 * Parsed mail attached data
	 * @var array
	 */
	protected $attachFiles = array();
	
	
	public function __construct($mail, $UID = 0)
	{
		$this->UID       = $UID;
		$this->plainMail = $mail;
		
		// Execute parse
		$this->_parseMail($mail);
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Get the UID
	 * 
	 * @access public
	 * @return int
	 */
	public function getUID()
	{
		return (int)$this->UID;
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Get header index safety
	 * 
	 * @access public
	 * @param  string key ( Original upper cased)
	 * @param  mixed $default
	 * @return mixed
	 */
	public function getHeader($key, $default = FALSE)
	{
		return ( isset($this->headers[$key]) )
		         ? $this->headers[$key]
		         : $default;
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Get plain header
	 * 
	 * @access public
	 * @return string
	 */
	public function getPlainHeader()
	{
		return $this->plainHeader;
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Get mail body
	 * 
	 * @access public
	 * @return string
	 */
	public function getBody()
	{
		return $this->body;
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Get attached files
	 * 
	 * @access public
	 * @return array
	 */
	public function getAttachFiles()
	{
		return $this->attachFiles;
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Parse Mail data
	 * 
	 * @access public
	 * @param  string $stdin
	 * @return stdClass $mail
	 */
	protected function _parseMail($mailString)
	{	
		// Split header/body
		list($this->plainHeader, $body) = $this->_splitHeadBody($mailString);
		
		// Parse Headers
		$this->headers           = $this->_parseMailHeader($this->plainHeader);
		$contentType             = $this->getHeader('Content-Type', 'text/plain');
		$contentTransferEncoding = $this->getHeader('Content-Transfer-Encoding', '7bit');
		
		// Mail has attachment?
		if ( preg_match('/^multipart\/([^;]+).*boundary="?([^"]+)"?/u', $contentType, $matches) )
		{
			$this->_parseMultipart($body, $matches[2], $matches[1]);
		}
		// Mail attachment section
		else if ( strpos($this->getHeader('Content-Disposition', ''), 'attachment') !== FALSE )
		{
			$this->_parseAttachFile($body);
		}
		// Simple text mail
		else
		{
			$charset = ( preg_match('/charset="?([^"]+)"?/', $contentType, $matches) )
			             ? $matches[1]
			             : 'JIS';
			
			// Decode string
			switch ( $contentTransferEncoding )
			{
				case 'base64':
					$body = base64_decode($body);
					break;
				case 'quoted-printable':
					$body = preg_replace('/=¥r?¥n/', '', $body);
					$body = preg_replace('/=([A-F0-9]{2})/e', "chr(hexdec ('$1'))" , $body);
					break;
				default:
					break;
			}
			
			$this->body = ( ! preg_match('/utf\-?8/ui', $charset) )
			                ? mb_convert_encoding(trim($body), 'UTF-8', strtoupper($charset))
			                : trim($body);
		}
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Parse and decode attach files
	 * 
	 * @access protected
	 * @param  string $fileData
	 */
	protected function _parseAttachFile($fileData)
	{
		// Get Header(sub) information
		$contentType             = $this->getHeader('Content-Type');
		$contentDisposition      = $this->getHeader('Content-Disposition');
		$contentTransferEncoding = $this->getHeader('Content-Transfer-Encoding');

		// mimetype
		$exp      = explode(';', trim($contentType));
		$mimeType = reset($exp);
		// filename
		$fileName = preg_replace('/.+filename="?(.+)"?.*/u', '$1', $contentDisposition);
		switch ( $contentTransferEncoding )
		{
			case 'base64':
				$data = base64_decode($fileData);
				break;
			default:
				$data = $fileData;
				break;
		}

		$this->attachFiles[] = array(
		    'filename' => $fileName,
		    'mimetype' => $mimeType,
		    'data'     => $data
		);
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Parse multipart section
	 * 
	 * @access protected
	 * @param  string $body
	 * @param  string $boundary
	 * @param  string $multiType
	 */
	protected function _parseMultipart($body, $boundary, $multiType)
	{
		// Split by boudary
		$multi = preg_split('/\-\-' . preg_quote($boundary) . '(?:\-\-)?\\r?\\n?/u', $body);
		
		// Strip first and last data
		array_shift($multi);
		array_pop($multi);
		
		// If multipart/alternative type, get first decoded data only.
		if ( $multiType === 'alternative' )
		{
			// Decode recursive
			$dec = new SZ_Mail_decoder(reset($multi));
			$this->body       .= $dec->getBody();
			$this->attachFiles = array_merge($this->attachFiles, $dec->getAttachFiles());
		}
		else
		{
			// Decode recursive
			foreach ( $multi as $part ) {
				$dec = new SZ_Mail_decoder($part);
				$this->body       .= $dec->getBody();
				$this->attachFiles = array_merge($this->attachFiles, $dec->getAttachFiles());
			}
		}
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Split header body
	 * 
	 * @access protected
	 * @param  string $input
	 * @return array
	 */
	protected function _splitHeadBody($input)
	{
		return preg_split('/\r?\n\r?\n/s', $input, 2);
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Parse mail header
	 * 
	 * @access protected
	 * @param  string $header
	 * @return array
	 */
	protected function _parseMailHeader($header)
	{
		// To strict linefeed
		$header  = trim(preg_replace('/\r?\n/', "\r\n", $header), "\t");
		$headers = array();
		$lines   = explode("\r\n", $header);
		
		foreach ( $this->_mergeLines($lines) as $line )
		{
			// Decode
			list($key, $value) = explode(':', $line, 2);
			$headers[trim($key)] = $this->_decodeMailHeader(trim($value));
		}
		return $headers;
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Mix vertical header line
	 * 
	 * @access protected
	 * @param  array $lines
	 * @return array
	 */
	protected function _mergeLines($lines)
	{
		$ret     = array('');
		$pointer = 0;
		
		foreach ( $lines as $line )
		{
			$line = trim(preg_replace('/\t/', '', $line));
			if ( $line === '' )
			{
				continue;
			}
			if ( preg_match('/^.+:\s/', $line) )
			{
				$ret[++$pointer] = $line;
			}
			else
			{
				$ret[$pointer] .= ' ' . $line;
			}
		}
		array_shift($ret);
		return $ret;
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Decode mail header
	 * 
	 * @access protected
	 * @param  string $value
	 * @return string
	 */
	protected function _decodeMailHeader($value)
	{
		$value = preg_replace('/(=\?[^?]+\?(q|b)\?[^?]*\?=)(\s)+=\?/i', '\1=?', $value);
		$charset = 'ISO-2022-JP';
		
		while ( preg_match('/(=\?([^?]+)\?(q|b)\?([^?]*)\?=)/i', $value, $matches) )
		{
			list(, $encoded, $charset, $encoding, $text) = $matches;

			switch ( strtolower($encoding) ) {
			case 'b': // BASE64
				$text = base64_decode($text);
				break;
			case 'q': // Quoted-Printable
				$text = str_replace('_', ' ', $text);
				preg_match_all('/=([a-f0-9]{2})/i', $text, $m);
				foreach( $m[1] as $v )
				{
					$text = str_replace('='. $v, chr(hexdec($v)), $text);
				}
				break;
			}
			$value = str_replace($encoded, $text, $value);
		}
		
		return mb_convert_encoding($value, 'UTF-8', $charset);
	}
}

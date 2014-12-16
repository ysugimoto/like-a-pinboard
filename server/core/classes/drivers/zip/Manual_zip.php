<?php if ( ! defined('SZ_EXEC') ) exit('access_denied');

/**
 * ====================================================================
 * 
 * Seezoo-Framework
 * 
 * A simple MVC/action Framework on PHP 5.1.0 or newer
 * 
 * 
 * PHP Zip driver ( Manualy archive/extract )
 * 
 * @package  Seezoo-Framework
 * @category Drivers
 * @author   Yoshiaki Sugimoto <neo.yoshiaki.sugimoto@gmail.com>
 * @license  MIT Licence
 * 
 * ====================================================================
 */

/*
 * ====================================================================================
 * 
 * Zip format:
 * @see http://www.onicos.com/staff/iz/formats/zip.html
 * 
 * [Local file header + Compressed data [+ Extended local header]]
 * [Central directory]*
 * [End of central directory record]
 * 
 * 
 * Local file header
 * -----------------------------------------------------------------------------------
 *  Offset  |  Length   |  Contents                                        |
 * -----------------------------------------------------------------------------------
 *    0     |  4 bytes  |  Local file header signature (0x04034b50) 
 *    4     |  2 bytes  |  Version needed to extract
 *    6     |  2 bytes  |  General purpose bit flag
 *    8     |  2 bytes  |  Compression method
 *   10     |  2 bytes  |  Last mod file time
 *   12     |  2 bytes  |  Last mod file date
 *   14     |  4 bytes  |  CRC-32
 *   18     |  4 bytes  |  Compressed size (n)
 *   22     |  4 bytes  |  Uncompressed size
 *   26     |  2 bytes  |  Filename length (f)
 *   28     |  2 bytes  |  Extra field length (e)
 *          | (f)bytes  |  Filename
 *          | (e)bytes  |  Extra field
 *          | (n)bytes  |  Compressed data
 * -----------------------------------------------------------------------------------
 * 
 * 
 * Extended local header: ( this library no use )
 * -----------------------------------------------------------------------------------
 *  Offset  |  Length   |  Contents                                        |
 * -----------------------------------------------------------------------------------
 *    0     |  4 bytes  |  Extended Local file header signature (0x08074b50)
 *    4     |  4 bytes  |  CRC-32
 *    8     |  4 bytes  |  Compressed size
 *   12     |  4 bytes  |  Uncompressed size
 * -----------------------------------------------------------------------------------
 * 
 * 
 * Central directory:
 * -----------------------------------------------------------------------------------
 *  Offset  |  Length   |  Contents                                        |
 * -----------------------------------------------------------------------------------
 *    0     |  4 bytes  |  Central file header signature (0x02014b50)
 *    4     |  2 bytes  |  Version made by
 *    6     |  2 bytes  |  Version needed to extract
 *    8     |  2 bytes  |  General purpose bit flag
 *   10     |  2 bytes  |  Compression method
 *   12     |  2 bytes  |  Last mod file time
 *   14     |  2 bytes  |  Last mod file date
 *   16     |  4 bytes  |  CRC-32
 *   20     |  4 bytes  |  Compressed size
 *   24     |  4 bytes  |  Uncompressed size
 *   28     |  2 bytes  |  Filename length (f)
 *   30     |  2 bytes  |  Extra field length (e)
 *   32     |  2 bytes  |  File comment length (c)
 *   34     |  2 bytes  |  Disk number start
 *   36     |  2 bytes  |  Internal file attributes
 *   38     |  4 bytes  |  External file attributes
 *   42     |  4 bytes  |  Relative offset of local header
 *   46     | (f)bytes  |  Filename
 *          | (e)bytes  |  Extra field
 *          | (c)bytes  |  File comment
 * -----------------------------------------------------------------------------------
 * 
 * 
 * End of central directory record:
 * -----------------------------------------------------------------------------------
 *  Offset  |  Length   |  Contents                                        |
 * -----------------------------------------------------------------------------------
 *    0     |  4 bytes  |  End of central dir signature (0x06054b50)
 *    4     |  2 bytes  |  Number of this disk
 *    6     |  2 bytes  |  Number of the disk with the start of the central directory
 *    8     |  2 bytes  |  Total number of entries in the central dir on this disk
 *   10     |  2 bytes  |  Total number of entries in the central dir
 *   12     |  4 bytes  |  Size of the central directory
 *   16     |  4 bytes  |  Offset of start of central directory with respect to the starting disk number
 *   20     |  2 bytes  |  zipfile comment length (c)
 *   22     | (c)bytes  |  zipfile comment
 * -----------------------------------------------------------------------------------
 * 
 * 
 * compression method: (2 bytes)
 * -----------------------------------------------------------------------------------
 *  Number  |  Method                                                      |
 * -----------------------------------------------------------------------------------
 *    0     |  The file is stored (no compression)
 *    1     |  (not support) The file is Shrunk
 *    2     |  (not support) The file is Reduced with compression factor 1
 *    3     |  (not support) The file is Reduced with compression factor 2
 *    4     |  (not support) The file is Reduced with compression factor 3
 *    5     |  (not support) The file is Reduced with compression factor 4
 *    6     |  (not support) The file is Imploded
 *    7     |  (not support)Reserved for Tokenizing compression algorithm
 *    8     |  The file is Deflated
 * -----------------------------------------------------------------------------------
 * 
 * ===================================================================================
 */

class SZ_Manual_zip extends SZ_Zip_driver
{
	/**
	 * zip file handle
	 * @var resource
	 */
	protected $handle;
	
	/**
	 * zip/central-directory/central-directory-end signatures
	 * @var string ( binary )
	 */
	protected $_signatureZip           = "\x50\x4b\x03\x04";
	protected $_signatureCentralDir    = "\x50\x4b\x01\x02";
	protected $_signatureCentralDirEnd = "\x50\x4b\x05\x06";
	
	
	/**
	 * CentralDirectory add queue
	 * @var array
	 */
	protected $_stackCentrals = array();
	
	
	/**
	 * Parsed compressed data stacks
	 * @var array/object
	 */
	protected $_compressedList       = array();
	protected $_comprssedCentralList = array();
	protected $_compressedCentralEnd;
	
	
	/**
	 * current parse mode
	 * @var string
	 */
	protected $_current;
	
	
	/**
	 * extracted data stacks
	 * @var array/object
	 */
	protected $_extractedData     = array();
	protected $_extractedCentrals = array();
	protected $_extractedCentralEnd;
	
	
	// --------------------------------------------------
	
	
	/**
	 * abstract implement
	 * create Zip archive
	 * 
	 * @see seezoo/core/classes/drivers/zip/SZ_Zip_driver::_archive()
	 */
	protected function _archive()
	{
		// create mode
		$mode = ( $this->_isOverWrite === TRUE ) ? 'wb' : 'a+b';
		$this->handle = @fopen($this->_archiveName, $mode);
		if ( ! $this->handle )
		{
			throw new Exception('Zip file open failed!');
			return FALSE;
		}
		
		// First, add Directory
		foreach ( $this->_addDirectories as $addDir )
		{
			$this->_addDirToZip($addDir, basename($addDir));
		}
		
		// Second, add File
		foreach ( $this->_addFiles as $addFile )
		{
			$this->_addFileToZip($addFile[0], $addFile[1]);
		}
		
		// Finally, make Central Directory
		$this->_addCentralDirectory();
		
		fclose($this->handle);
		
		// All green!
		return TRUE;
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * Add directory data to Zip archive
	 * 
	 * @access protected
	 * @param string $dir
	 * @param string $dirName
	 */
	protected function _addDirToZip($dir, $dirName)
	{
		// trim double slash
		$dirName = str_replace('//', '/', $dirName);
		// trail last slash
		$dir     = rtrim($dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
		$dirName = rtrim($dirName, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
		// lastmodified
		$lastMod = filemtime($dir);
		// make entry data
		$entry   = array(
						10,                             // 0:  version need
						0,                              // 1:  general bit flag
						0,                              // 2:  compress method
						$this->_calcModTime($lastMod),  // 3:  lastmod-time
						$this->_calcModDate($lastMod),  // 4:  lastmod-date
						crc32(''),                      // 5:  crc-32
						0,                              // 6:  compress size
						0,                              // 7:  uncompress size
						strlen($dirName),               // 8:  directory name length
						0,                              // 9:  extra-field length
						$dirName,                       // 10: directory name,
						16,                             // 11: external attributes Directory
						ftell($this->handle)            // 12: buffer offset
					);
		
		$dirFileEntry = $this->_signatureZip
		                . pack('s', $entry[0])
		                . pack('s', $entry[1])
		                . pack('s', $entry[2])
		                . pack('s', $entry[3])
		                . pack('s', $entry[4])
		                . pack('V', $entry[5])
		                . pack('I', $entry[6])
		                . pack('I', $entry[7])
		                . pack('s', $entry[8])
		                . pack('s', $entry[9])
		                . $entry[10]
		                . ''; // empty string
		fwrite($this->handle, $dirFileEntry);
		// add central queue
		$this->_stackCentrals[] = $entry;
		
		foreach ( $this->_getFileList($dir) as $key => $file )
		{
			// If key is string and value is array, recursive directory
			if ( is_string($key) )
			{
				$this->_addDirToZip($file, $dirName . basename($file));
			}
			// Else, add file add queue
			else
			{
				$this->_addFiles[] = array($file, $dirName . basename($file));
			}
		}
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * Add file data to Zip archive
	 * 
	 * @access protected
	 * @param  string $file
	 * @param  string $filename
	 */
	protected function _addFileToZip($file, $filename)
	{
		// check file really exists?
		if ( ! file_exists($file) )
		{
			return;
		}
		// trim double slash
		$filename = ltrim(str_replace('//', '/', $filename), '/');
		// get filesize, filedata, filemtime
		$size    = filesize($file);
		$data    = file_get_contents($file);
		$lastMod = filemtime($file);
		// and gz compress
		$gzdata  = gzcompress($data);
		$gzdata  = substr(substr($gzdata, 0, strlen($gzdata) - 4), 2);
		// make entry data
		$entry  = array(
				10,                             //  0: version need
				0,                              //  1: general bit flag
				8,                              //  2: compress method 8:Deflated
				$this->_calcModTime($lastMod),  //  3: lastmod-time
				$this->_calcModDate($lastMod),  //  4: l5astmod-date
				crc32($data),                   //  5: crc-32
				strlen($gzdata),                //  6: compress size
				$data,                          //  7: uncompress size
				strlen($filename),              //  8: file name length
				0,                              //  9: extra-field length
				$filename,                      // 10: file name
				32,                             // 11: external attributes File
				ftell($this->handle)            // 12: buffer offset
			);
		
		$fileEntry = $this->_signatureZip
		             . pack('s', $entry[0])
		             . pack('s', $entry[1])
		             . pack('s', $entry[2])
		             . pack('s', $entry[3])
		             . pack('s', $entry[4])
		             . pack('V', $entry[5])
		             . pack('I', $entry[6])
		             . pack('I', $entry[7])
		             . pack('s', $entry[8])
		             . pack('s', $entry[9])
		             . $entry[10]
		             . $gzdata;
		fwrite($this->handle, $fileEntry);
		$this->_stackCentrals[] = $entry;
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * Create Central Directroy signature
	 * 
	 * @access protected
	 */
	protected function _addCentralDirectory()
	{
		$cdBuffer = '';
		
		// write central dir
		foreach ( $this->_stackCentrals as $entry )
		{
			$cdBuffer .= $this->_signatureCentralDir
			              . "\x0\x0"              // version made
			              . pack('v', $entry[0])  // version need
			              . "\x0\x0"              // general bit flag
			              . pack('v', $entry[2])  // compress method
			              . pack('v', $entry[3])  // lastmod-time
			              . pack('v', $entry[4])  // lastmod-date
			              . pack('V', $entry[5])  // crc32
			              . pack('V', $entry[6])  // compress filesize
			              . pack('V', $entry[7])  // uncompress filesize
			              . pack('v', $entry[8])  // filename length
			              . pack('v', 0)          // extra field length
			              . pack('v', 0)          // file comment length
			              . pack('v', 0)          // disk number start
			              . pack('v', 0)          // internal file attribute
			              . pack('V', $entry[11]) // external file attribute
			              . pack('V', $entry[12]) // relative offset
			              . $entry[10]            // filename
			              .'';                    // comment
		}
		
		$point = ftell($this->handle);
		fwrite($this->handle, $cdBuffer);
		
		// write end of central dir
		$edBuffer = $this->_signatureCentralDirEnd
		              . pack('v', 0)
		              . pack('v', 0)
		              . pack('v', count($this->_stackCentrals))
		              . pack('v', count($this->_stackCentrals))
		              . pack('V', strlen($cdBuffer))
		              . pack('V', $point)
		              . pack('v', 0)
		              . ''; // empty comment
		fwrite($this->handle, $edBuffer);
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * calculate decimal lastmodified time 
	 * 
	 * @access protected
	 * @return int 
	 */
	protected function _calcModTime($mtime = FALSE)
	{
		$lastMod = ( $mtime ) ? $mtime : time();
		$sec     = date('s', $lastMod);
		if ( $sec >= 32 )
		{
			$sec -= 32;
		}
		return bindec(
			  str_pad(decbin(date('H', $lastMod)), 5, '0', STR_PAD_LEFT)
			. str_pad(decbin(date('i', $lastMod)), 6, '0', STR_PAD_LEFT)
			. str_pad(decbin($sec), 5, '0', STR_PAD_LEFT)
		);
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * calculate decimal lastmodified date
	 * 
	 * @access protected
	 * @return int
	 */
	protected function _calcModDate($mtime = FALSE)
	{
		$lastMod = ( $mtime ) ? $mtime : time();
		return bindec(
			  str_pad(decbin(date('Y', $lastMod) - 1980), 7, '0', STR_PAD_LEFT)
			. str_pad(decbin(date('m', $lastMod)), 4, '0', STR_PAD_LEFT)
			. str_pad(decbin(date('d', $lastMod)), 5, '0', STR_PAD_LEFT)
		);
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * deflate lastmodified timestamp
	 * 
	 * @access protected
	 * @param  int $time
	 * @param  int $date
	 * @return int timestamp
	 */
	protected function _deflateLastMod($time, $date)
	{
		$time = str_pad(decbin($time), 16, '0', STR_PAD_LEFT);
		$date = str_pad(decbin($date), 16, '0', STR_PAD_LEFT);
		
		return mktime(
			bindec(substr($time, 0,  5)),       // hour
			bindec(substr($time, 5,  6)),       // minute
			bindec(substr($time, 11, 5)),       // second
			bindec(substr($date, 7,  4)),       // month
			bindec(substr($date, 11, 5)),       // day
			bindec(substr($date, 0,  7)) + 1980 // year
		);
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * abstract implement
	 * extract Zip archive
	 * 
	 * @see seezoo/core/classes/drivers/zip/SZ_Zip_driver::_extract()
	 */
	protected function _extract()
	{
		$this->handle = @fopen($this->_archiveName, 'rb');
		if ( ! $this->handle )
		{
			throw new Exception('Zip archive open Failed!');
			return FALSE;
		}
		
		// guard: lock and rewind
		flock($this->handle, LOCK_SH);
		rewind($this->handle);
		
		// parse Compressed archive data
		$this->_parseCompressFile();
		
		flock($this->handle, LOCK_UN);
		fclose($this->handle);
		
		// uncompress file/directory data
		foreach ( $this->_compressedList as $data )
		{
			$this->_extractData($data);
		}
		
		// uncompress central-directoryy data
		foreach ( $this->_compressedCentralList as $central )
		{
			$this->_extractCentralDirectory($central);
		}
		
		$this->_extractCentralDirectoryEnd($this->_compressedCentralEnd);
		
		// sort by path length
		// guard: make directories before nested file create
		usort($this->_extractedData, array($this, '_sortByPathLength'));
		
		// create extracted file/directory
		foreach ( $this->_extractedData as $file )
		{
			$this->_createFile($file);
		}
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * Parse Zip-compressed data stream
	 * 
	 * @access protected
	 * @param  string $dat
	 */
	protected function _parseCompressFile($dat = '')
	{
		// read 1 byte
		$byte = fread($this->handle, 1);
		// Does byte is zip/central/centralend signatures first byte?
		if ( $byte !== "\x50" )
		{
			// cotinute read
			$dat .= $byte;
			$this->_parseCompressFile($dat);
		}
		else
		{
			// read more 3 byte stream
			$sigByte = $byte . fread($this->handle, 3);
			
			// 4 byte is Zip-signature?
			if ( $sigByte === $this->_signatureZip )
			{
				if ( $this->_current === 'zip' )
				{
					$this->_compressedList[] = $dat;
				}
				else if ( $this->_current === 'central' )
				{
					$this->_compressedCentralList[] = $dat;
				}
				$this->_current = 'zip';
				$this->_parseCompressFile();
			}
			// 4 byte is Central-Directory-signature?
			else if ( $sigByte === $this->_signatureCentralDir )
			{
				if ( $this->_current === 'zip' )
				{
					$this->_compressedList[] = $dat;
				}
				else if ( $this->_current === 'central' )
				{
					$this->_compressedCentralList[] = $dat;
				}
				$this->_current = 'central';
				$this->_parseCompressFile();
			}
			// 4 byte is Central-Directory-End-signature?
			else if ( $sigByte === $this->_signatureCentralDirEnd )
			{
				if ( $this->_current === 'zip' )
				{
					$this->_compressedList[] = $dat;
				}
				else if ( $this->_current === 'central' )
				{
					$this->_compressedCentralList[] = $dat;
				}
				$this->_compressedCentralEnd = fread($this->handle, 4096);
			}
			// else, conitnue read
			else
			{
				$dat .= $sigByte;
				$this->_parseCompressFile($dat);
			}
		}
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * extract compressed file/directory data
	 * 
	 * @access protected
	 * @param  string $dat
	 */
	protected function _extractData($dat)
	{
		$data = new stdClass;
		// unpack bit offset stream
		list(, $data->versionNeed)      = unpack('v', substr($dat, 0,  2));  // 2byte Version need
		list(, $data->generalBitFlag)   = unpack('v', substr($dat, 2,  2));  // 2byte General bit flag
		list(, $data->compressMethod )  = unpack('v', substr($dat, 4,  2));  // 2byte Compress Method
		list(, $data->lastModTime)      = unpack('v', substr($dat, 6,  2));  // 2byte last-modified-time
		list(, $data->lastModDate)      = unpack('v', substr($dat, 8,  2));  // 2byte last-modified-date
		list(, $data->crc32)            = unpack('V', substr($dat, 10, 4));  // 4byte CRC-32
		list(, $data->compressSize)     = unpack('V', substr($dat, 14, 4));  // 4byte Compressed size
		list(, $data->uncompressSize)   = unpack('V', substr($dat, 18, 4));  // 4byte Uncompressed size
		list(, $data->fileNameLength)   = unpack('v', substr($dat, 22, 2));  // 2byte Filename length
		list(, $data->extraFieldLength) = unpack('v', substr($dat, 24, 2));  // 2byte Extra field length
		
		// mark split point
		$extraFieldPoint   = 26 + (int)$data->fileNameLength;
		$compressdataPoint = $extraFieldPoint + (int)$data->extraFieldLength;
		
		// split filename, extra, compressed data
		$data->fileName       = substr($dat, 26,               (int)$data->fileNameLength);    // Filename
		$data->extraField     = substr($dat, $extraFieldPoint, (int)$data->extraFieldLength);  // Extra filed data
		$data->compressedData = ( $data->compressMethod == 8 ) 
		                         ? gzinflate(substr($dat, $compressdataPoint), (int)$data->compressSize)
		                         : substr($dat, $compressdataPoint);
		// consider archived filename traversal insertion
		$data->fileName = $this->_prepfileName($data->fileName);
		// delfate timestemp lastmodified
		$data->lastMod  = $this->_deflateLastMod($data->lastModTime, $data->lastModDate);
		$this->_extractedData[] = $data;
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * extract compressed central-directory data
	 * 
	 * @access protected
	 * @param  string $dat
	 */
	protected function _extractCentralDirectory($dat)
	{
		$data = new stdClass;
		list(, $data->versionMadeBy)          = unpack('v', substr($dat, 0,  2));
		list(, $data->versionNeedExtract)     = unpack('v', substr($dat, 2,  2));
		list(, $data->generalBitFlag)         = unpack('v', substr($dat, 4,  2));
		list(, $data->compressMethod)         = unpack('v', substr($dat, 6,  2));
		list(, $data->lastModTime)            = unpack('v', substr($dat, 8,  2));
		list(, $data->lastModDate)            = unpack('v', substr($dat, 10, 2));
		list(, $data->crc32)                  = unpack('V', substr($dat, 12, 4));
		list(, $data->compressSize)           = unpack('V', substr($dat, 16, 4));
		list(, $data->uncompressSize)         = unpack('V', substr($dat, 20, 4));
		list(, $data->fileNameLength)         = unpack('v', substr($dat, 24, 2));
		list(, $data->extraFieldLength)       = unpack('v', substr($dat, 26, 2));
		list(, $data->fileCommentLength)      = unpack('v', substr($dat, 28, 2));
		list(, $data->diskNumberStart)        = unpack('v', substr($dat, 30, 2));
		list(, $data->internalFileAttributes) = unpack('v', substr($dat, 32, 2));
		list(, $data->externalFileAttributes) = unpack('V', substr($dat, 34, 4));
		list(, $data->relativeOffsetHeader)   = unpack('V', substr($dat, 38, 4));
		
		// mark split point
		$fileNamePoint    = 42;
		$extraFieldPoint  = $fileNamePoint + (int)$data->fileNameLength;
		$fileCommentPoint = $extraFieldPoint + (int)$data->extraFieldLength;
		
		$data->fileName    = substr($dat, 42,                (int)$data->fileNameLength);
		$data->extraField  = substr($dat, $extraFieldPoint,  (int)$data->extraFieldLength);
		$data->fileComment = substr($dat, $fileCommentPoint, (int)$data->fileCommentLength);
		
		// consider archived filename traversal insertion
		$data->fileName = $this->_prepfileName($data->fileName);
		// delfate timestemp lastmodified
		$data->lastMod  = $this->_deflateLastMod($data->lastModTime, $data->lastModDate);
		$this->_extractedCentrals[$data->fileName] = $data;
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * extract compressed central-directory-end data
	 * 
	 * @access protected
	 * @param  string $dat
	 */
	protected function _extractCentralDirectoryEnd($dat)
	{
		$data = new stdClass;
		list(, $data->diskNumber)       = unpack('v', substr($dat, 0,  2));
		list(, $data->startDiskNumber)  = unpack('v', substr($dat, 2,  2));
		list(, $data->totalDiskEntries) = unpack('v', substr($dat, 4,  2));
		list(, $data->totalEntries)     = unpack('v', substr($dat, 6,  2));
		list(, $data->size)             = unpack('V', substr($dat, 8,  4));
		list(, $data->offset)           = unpack('V', substr($dat, 12, 4));
		list(, $data->zipCommentLength) = unpack('v', substr($dat, 16, 2));
		$data->zipComment               = substr($dat, 18, $data->zipCommentLength);
		
		$this->_extractedCentralEnd = $data;
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * pref file/directory name from travaersal attack
	 * 
	 * @access protected
	 * @param  string $fileName
	 * @return string
	 */
	protected function _prepFileName($fileName)
	{
		// kill first-slash on top directory on *BSD/Linux;
		$fileName = ltrim($fileName, '/');
		
		// kill \x0 space
		$fileName = str_replace('\x0', '', $fileName);
		
		// resolve relative path
		// ../../../../usr/bin resolve to usr/bin 
		$name = basename($fileName);
		$dirs = dirname($fileName);
		$path = array();
		foreach ( explode('/', $dirs) as $dir )
		{
			if ( $dir === '.' )
			{
				continue;
			}
			else if ( $dir === '..' )
			{
				array_pop($path);
				continue;
			}
			$path[] = $dir;
		}
		return ( count($path) > 0 )
		         ? implode('/', $path) . '/' . $name
		         : $name;
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * create extracted data
	 * 
	 * @param  stdClass $file
	 * @throws Exception
	 */
	protected function _createFile($file)
	{
		$name = $file->fileName;
		
		// Does file defined in centraldirectory?
		if ( ! isset($this->_extractedCentrals[$name]) )
		{
			throw new Exception('Extracted data is not registed central directory!');
			return;
		}
		$central = $this->_extractedCentrals[$name];
		if ( $central->externalFileAttributes == 16 ) // Directory
		{
			// create directory with path recursive, write permission if not exists
			if ( ! file_exists($this->_extractDir . $name) )
			{
				@mkdir($this->_extractDir . $name, 0755, TRUE);
			}
		}
		else // File
		{
			file_put_contents($this->_extractDir . $name, $file->compressedData);
		}
		// set lastmodified timestamp
		touch($this->_extractDir . $name, $file->lastMod);
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * usort callback, sort by slash count
	 * 
	 * @param stdClass $a
	 * @param stdClass $b
	 */
	protected function _sortByPathLength($a, $b)
	{
		$exp1 = explode('/', $a->fileName);
		$exp2 = explode('/', $b->fileName);
		
		return ( count($exp1) > count($exp2) ) ? 1 : -1;
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * abstract implement
	 * cleanup handle, queue, stacks
	 * @see seezoo/core/classes/drivers/zip/SZ_Zip_driver::_cleanup()
	 */
	protected function _cleanup()
	{
		if ( is_resource($this->handle) )
		{
			@fclose($this->handle);
		}
		$this->_archiveName      = '';
		$this->_addFiles         = array();
		$this->_addDirectories   = array();
		$this->_extractDir       = '';
		$this->_compressedList   = array();
		$this->_compressdCentral = null;
		$this->_extractedData    = array();
	}
}
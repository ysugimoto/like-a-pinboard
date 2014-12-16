<?php if ( ! defined('SZ_EXEC') ) exit('access_denied');

/**
 * ====================================================================
 * 
 * Seezoo-Framework
 * 
 * A simple MVC/action Framework on PHP 5.1.0 or newer
 * 
 * 
 * RSS Builder / Parser
 * 
 * @package  Seezoo-Framework
 * @category Libraries
 * @author   Yoshiaki Sugimoto <neo.yoshiaki.sugimoto@gmail.com>
 * @license  MIT Licence
 * 
 * ====================================================================
 */

class SZ_Rss implements Growable
{
	/**
	 * Current build/parse setting
	 * @var array
	 */
	protected $setting = array();
	
	
	/**
	 * Default settings
	 * @var array
	 */
	protected $defaultSetting = array(
		'type'              => '1.0',
		'feed_url'          => '',
		'list_url'          => '',
		'site_title'        => '',
		'description'       => '',
		'parse_date_format' => 'Y-m-d H:i:s',
		'encoding'          => 'UTF-8'
	);
	
	
	public function __construct($conf = array())
	{
		$this->configure($conf);
	}
	
	
	/**
	 * Growable interface implementation
	 * 
	 * @access public static
	 * @return SZ_Rss ( extended )
	 */
	public static function grow()
	{
		return Seezoo::$Importer->library('Rss');
	}
	
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Setting configuration
	 * 
	 * @access public
	 * @param  array $conf
	 */
	public function configure($conf = array())
	{
		$this->setting = array_merge($this->defaultSetting, $conf);
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Parse RSS from file
	 * 
	 * @access public
	 * @param $file
	 */
	public function parseFromFile($file = '')
	{
		if ( ! file_exists($file) )
		{
			return FALSE;
		}
		
		// Does parse target file exists on external server?
		if ( preg_match('/\Ahttps?:', $file) )
		{
			$http     = Seezoo::$Importer->library('Http');
			$response = $http->request('GET', $file);
			if ( $response->status !== 200 )
			{
				return FALSE;
			} 
			return $this->parse($response->body);
		}
		// Else file exists on our server simply file_get_contents
		else
		{
			return $this->parse(file_get_contents($file));
		}
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Parse RSS
	 * 
	 * @access public
	 * @param  string $data
	 * @return mixed
	 */
	public function parse($data)
	{
		$XML = new SimpleXMLElement($data);
		if (!$XML)
		{
			return FALSE;
		}
		
		// get root node name
		$root = strtolower($XML->getName());
		// RSS 1.0 has item data in first property
		if ($root === 'rdf')
		{
			$ret  = $this->_parseRDF($XML);
		}
		// RSS 2.0 has item data in channel child
		else if ($root === 'rss')
		{
			$ret =  $this->_parseRSS2($XML);
		}
		// Atom feed has feed node
		else if ($root === 'feed')
		{
			$ret = $this->_parseAtom($XML);
		}
		else
		{
			throw new Exception('Invalid RSS format!');
			return FALSE;
		}
		return $ret;
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Create RSS data
	 * 
	 * @access public
	 * @param  string $data
	 * @param  mixed $outputFile
	 */
	public function create($data, $outputFile = FALSE)
	{
		$rss = '';
		switch ( $this->setting['type'] )
		{
			case '2.0':
				$rss = $this->_makeRss2($data);
				break;
			case 'atom':
				$rss = $this->_makeAtom($data);
				break;
			default:
				$rss = $this->_makeRss1($data);
				break;
		}
		
		if ( $outputFile !== FALSE )
		{
			if ( ! really_writable($outputFile) )
			{
				return FALSE;
			}
			$fp = @fopen($outputFile, 'wb');
			if ( ! $fp )
			{
				return FALSE;
			}
			flock($fp, LOCK_EX);
			fwrite($fp, $rss);
			flock(LOCK_UN);
			fclose($fp);
			return TRUE;
		}
		
		// send Browser...
		if ( $this->setting['encoding'] !== 'UTF-8' )
		{
			$rss = mb_convert_encoding($rss, $this->setting['encoding'], 'UTF-8');
		}
		header('Content-Type: application/xml; charset="' . $this->setting['encoding'] . '"');
		echo $rss;
		exit;
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Make RSS on Atom format
	 * 
	 * @access protected
	 * @param  string $dat
	 * @return string
	 */
	protected function _makeAtom($dat)
	{
		$site_title = ( !empty($this->setting['site_title']) ) ? $this->setting['site_title'] : '';
		$rss = array(
			'<?xml version="1.0" encoding="UTF-8"?>',
			'<feed xmlns="http://www.w3.org/2005/Atom" ',
			$this->tab()  . 'xmlns:thr="http://purl.org/syndication/thread/1.0" ',
			$this->tab()  . 'xml:lang="ja">',
			$this->tab()  . '<title>' . $site_title . '</title>',
			$this->tab()  . '<link rel="alternate" type="text/html" href="' . $this->setting['list_url'] .'" />',
			$this->tab()  . '<updated>' . date('c', time()) . '</updated>',
			$this->tab()  . '<id>' . $this->setting['feed_url'] . '</id>',
			$this->tab()  . '<author>',
			$this->tab(2) . '<name>' . $site_title . '</name>',
			$this->tab()  . '</author>'
		);
		
		foreach ( $dat as $item )
		{
			if ( is_object($item) )
			{
				$item = get_object_vars($item);
			}
			array_push($rss,
				$this->tab()  . '<entry>',
				$this->tab(2) . '<author><name>' . (( isset($item['author']) ) ? $item['author'] : '') . '</name></author>',
				$this->tab(2) . '<title>' . (( isset($item['author']) ) ? $item['author'] : '') . '</title>',
				$this->tab(2) . '<link rel="alternate" type="text/html" href="' . (( isset($item['url']) ) ? $this->_formatURL($item['url']) : '') . '" />',
				$this->tab(2) . '<id>' . (( isset($item['url']) ) ? $this->_formatURL($item['url']) : '') . '</id>',
				$this->tab(2) . '<updated>' . (( isset($item['date']) ) ? date('c', strtotime($item['date'])) : '') . '</updated>',
				$this->tab(2) . '<category scheme="' . (( isset($item['base_link']) ) ? $item['base_link'] : page_link()) . '" term="' . ((isset($item['category'])) ? $item['category'] : '') . '" />',
				$this->tab(2) . '<summary type="html"><![CDATA[' . (( isset($item['description']) ) ? $item['description'] : '') . ']]></summary>',
				$this->tab()  . '</entry>'
			);
		}
		
		$rss[] = '</feed>';
		return implode("\n", $rss);
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Make RSS on RSS2.0 format
	 * 
	 * @access protected
	 * @param  string $dat
	 * @return string
	 */
	protected function _makeRss2($dat)
	{
		$site_title = ( !empty($this->setting['site_title']) ) ? $this->setting['site_title'] : '';
		$rss = array(
			'<?xml version="1.0" encoding="UTF-8"?>',
			'<rss version="2.0" ',
			$this->tab()  . 'xmlns:dc="http://purl.org/dc/elements/1.1/" ',
			$this->tab()  . 'xmlns:sy="http://purl.org/rss/1.0/modules/syndication/" ',
			$this->tab()  . 'xmlns:admin="http://webns.net/mvcb/" ',
			$this->tab()  . 'xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#">',
			$this->tab()  . '<channel>',
			$this->tab(2) . '<title>' . $site_title . '</title>',
			$this->tab(2) . '<link>' . $this->setting['list_url'] . '</link>',
			$this->tab(2) . '<description>' . $this->setting['description'] . '</description>',
			$this->tab(2) . '<dc:language>ja</dc:language>',
			$this->tab(2) . '<pubDate>' . date('r', time()) . '</pubDate>'
		);
		
		foreach ( $dat as $item )
		{
			if ( is_object($item) )
			{
				$item = get_object_vars($item);
			}
			array_push($rss,
				$this->tab(2) . '<item>',
				$this->tab(3) . '<title>' . (( isset($item['title']) ) ? $item['title'] : '') . '</title>',
				$this->tab(3) . '<link>' . (( isset($item['url']) ) ? $this->_fomartURL($item['url']) : '') . '</link>',
				$this->tab(3) . '<guid isPermaLink="false">' . (( isset($item['url']) ) ? $this->_fomartURL($item['url']) : '') . '</guid>',
				$this->tab(3) . '<description>' . (( isset($item['title']) ) ? $item['title'] : '') .'</description>',
				$this->tab(3) . '<dc:creator>' . (( isset($item['author']) ) ? $item['author'] : '') . '</dc:creator>',
				$this->tab(3) . '<pubDate>' . (( isset($item['date']) ) ? date('r', strtotime($item['date'])) : '')  . '</pubDate>',
				$this->tab(3) . '<category>' . ((isset($item['category'])) ? $item['category'] : '') . '</category>',
				$this->tab(2) . '</item>'
			);
		}
		
		$rss[] = $this->tab() . '</channel>';
		$rss[] = '</rss>';
		
		return implode("\n", $rss);
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Make RSS on RSS1.0 format
	 * 
	 * @access protected
	 * @param  string $dat
	 * @return string
	 */
	protected function _makeRss1($dat)
	{
		$site_title = ( !empty($this->setting['site_title']) ) ? $this->setting['site_title'] : '';
		$rss = array(
			'<?xml version="1.0" encoding="UTF-8"?>',
			'<rdf:RDF xmlns="http://purl.org/rss/1.0/" ',
			$this->tab()  . 'xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" ',
			$this->tab()  . 'xmlns:dc="http://purl.org/dc/elements/1.1" ',
			$this->tab()  . 'xmlns:content="http://purl.org/rss/1.0/modules/content/" ',
			$this->tab()  . 'xml:lang="ja">',
			$this->tab()  . '<channel rdf:about="' . $this->setting['feed_url'] . '">',
			$this->tab(2) . '<title>' . $site_title . '</title>',
			$this->tab(2) . '<link>' . $this->setting['list_url'] . '</link>',
			$this->tab(2) . '<description>' . $this->setting['description'] . '</description>',
			$this->tab(2) . '<dc:date>' . date('c', time()) . '</dc:date>',
			$this->tab(2) . '<dc:language>ja</dc:language>',
			$this->tab(2) . '<items>',
			$this->tab(2) . '<rdf:Seq>'
		);
		foreach ( $dat as $key => $v )
		{
			if ( is_object($v) )
			{
				$v = get_object_vars($v);
			}
			$dat[$key] = $v;
			
			if ( isset($v['url']) )
			{
				$rss[] = $this->tab(2) .'<rdf:li rdf:resource="' . $this->_formatURL($v['url']) . '" />';
			}
		}
		
		$rss[] = $this->tab(2) . '</rdf:Seq>';
		$rss[] = $this->tab(2) . '</items>';
		$rss[] = $this->tab()  . '</channel>';
		
		foreach ( $dat as $item )
		{
			array_push($rss,
				$this->tab()  . '<item rdf:about="' . (( isset($item['url']) ) ? $this->_formatURL($item['url']) : '') . '">',
				$this->tab(2) . '<title>' . (( isset($item['title']) ) ? $item['title'] : '') . '</title>',
				$this->tab(2) . '<link>' . (( isset($item['url']) ) ? $this->_formatURL($item['url']) : '') . '</link>',
				$this->tab(2) . '<description><![CDATA[' . (( isset($item['description']) ) ? $item['description'] : '') . ']]></description>',
				$this->tab(2) . '<dc:date>' . (( isset($item['date']) ) ? date('c', strtotime($item['date'])) : '') . '</dc:date>',
				$this->tab()  . '</item>'
			);
		}
		
		$rss[] = '</rdf:RDF>';
		
		return implode("\n", $rss);
		
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Format http url
	 * 
	 * @access protected
	 * @param  string $url
	 * @return string
	 */
	protected function _formatURL($url)
	{
		if ( preg_match('/\Ahttps?:.+/u', $url) )
		{
			return $url;
		}
		return page_link(ltrim($url, '/'));
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Put a tab
	 * 
	 * @access private
	 * @param  int $cnt
	 * @return string
	 */
	private function tab($cnt = 1)
	{
		$tab = "\t";
		$cnt = 0;
		while ( ++$cnt < $cnt )
		{
			$tab .= "\t";
		}
		return $tab;
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Parse RDF(RSS1.0) format
	 * 
	 * @access protected
	 * @param  SimpleXMLElement $XML
	 * @return array
	 */
	protected function _parseRDF($XML)
	{
		// get XML document namespace
		$ns = $XML->getDocNamespaces();
		// and shift empty first
		array_unshift($ns, '');
		
		// set first XML data
		$dat = array(
			'channel' => array(
				'title'       => trim((string)$XML->channel->title),
				'link'        => trim((string)$XML->channel->link),
				'description' => trim((string)$XML->channel->description)
			)
		);
		// set item data
		foreach ( $XML->item as $v )
		{
			$tmp = array();
			foreach ( $ns as $n )
			{
				foreach ( $v->children($n) as $c )
				{
					$name = $c->getName();
					$tmp[$name] = ( $name === 'date')
					                ? date($this->setting['parse_date_format'], strtotime((string)$c))
					                : (string)$c;
				}
			}
			$dat['item'][] = array_map('trim', $tmp);
		}
		return $dat;
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Parse RSS2.0 format
	 * 
	 * @access protected
	 * @param  SimpleXMLElement $XML
	 * @return array
	 */
	protected function _parseRSS2($XML)
	{
		$ns = $XML->getDocNamespaces();
		array_unshift($ns, '');

		// set item data
		$items    = array();
		$channels = array();
		foreach ( $XML->channel->children() as $v )
		{
			$name = $v->getName();
			if ($name === 'item')
			{
				$items[] = $this->_parse_rss2_item($v, $ns);
			}
			else
			{
				$channels[$name] = (string)$v;
			}
		}
		return array(
			'channel' => array_map('trim', $channels),
			'item'    => $items
		);
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Parse Atom format
	 * 
	 * @access protected
	 * @param  SimpleXMLElement $XML
	 * @return array
	 */
	protected function _parseAtom($XML)
	{
		$ns = $XML->getDocNamespaces();
		array_unshift($ns, '');
		
		// set item data
		$entries　 = array();
		$channels = array();
		foreach (　$XML->children() as $v　)
		{
			$name = $v->getName();
			if ($name === 'entry')
			{
				$entries[] = $this->_parseAtomEntry($v, $ns);
			}
			else
			{
				$channels[$name] = (string)$v;
			}
		}
		return array(
			'channel' => array_map('trim', $channels),
			'item'    => $entries
		);
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Parse piece of RSS2.0 item
	 * 
	 * @access private
	 * @param  SimpleXMLElement $item
	 * @param  array $namespace
	 * @return array
	 */
	private function _paseRSS2Item($item, $namespace)
	{
		$tmp = array();
		foreach ( $namespace as $n )
		{
			foreach ( $item->children($n) as $c )
			{
				$name       = $c->getName();
				$tmp[$name] = (string)$c;
			}
		}
		if ( isset($tmp['pubDate']) )
		{
			$tmp['pubDate'] = date($this->setting['parse_date_format'], strtotime($tmp['pubDate']));
		}
		return array_map('trim', $tmp);
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Parse piece of Atom item
	 * 
	 * @access private
	 * @param  SimpleXMLElement $item
	 * @param  array $namespace
	 * @return array
	 */
	private function _parseAtomEntry($item, $namespace)
	{
		$tmp = array();
		foreach ( $namespace as $n )
		{
			foreach ( $entry->children($n) as $c )
			{
				$name = $c->getName();
				$att  = $c->attributes();
				if ($name === 'link')
				{
					$c = $att['href'];
				}
				$tmp[$name] = ( count($c->children()) > 0 )
				               ? $this->_parseAtomEntry($c, $ns)
				               : (string)$c;
			}
		}
		if ( isset($tmp['updated']) )
		{
			$tmp['updated'] = date($this->setting['parse_date_format'], strtotime((string)$c));
		}
		return array_map('trim', $tmp);
	}
}
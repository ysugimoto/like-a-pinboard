<?php if ( ! defined('SZ_EXEC') ) exit('access_denied');

/**
 * ===============================================================================
 * Seezoo-Framework
 * 
 * A simple MVC/action Framework on PHP 5.1.0 or newer
 * 
 * Mobile device detection
 * 
 * Original library:
 * @see https://bitbucket.org/sugimoto/mobile-detection
 * 
 * @required app/config/mobile.php ( setting file )
 * 
 * @package  Seezoo-Framework
 * @category libraries
 * @author   Yoshiaki Sugimoto <neo.yoshiaki.sugimoto@gmail.com>
 * @license  MIT Licence
 * 
 * ===============================================================================
 */

class SZ_Mobile implements Growable
{
	// Full User-Agent string
	protected $_agent;
	
	// Access IP
	protected $_remote_ip;
	
	// Mobile carrier settings ==========================================
	
	// Mobile carrier flag
	protected $_mobile = FALSE;
	
	// Mobile carrier name
	protected $_mobile_carrier = '';

	// Stack of ip list
	protected $ip_list = array();

	
	// Smartphone settings ==============================================
	
	// Smartphone OS list
	protected $_sp_agents = array(
								'iphone'         => 'iPhone',
								'ipad'           => 'iPad',
								'ipod'           => 'iPod',
								'android'        => 'Android',
								'webos'          => 'WebOS',
								'blackberry'     => 'BlackBerry',
								'windows phone'  => 'WindowsPhone',
								'windows ce'     => 'WindowsMobile'
							);
	
	// Boolean Smartphone flag
	protected $_sp = FALSE;
	
	// OS name
	protected $_sp_os = FALSE;
	
	// OS version
	protected $_os_version = FALSE;
	
	protected $_config = array();
	
	/**
	 * Constructor
	 */
	public function __construct()
	{
		if ( ! file_exists(Application::get()->path . 'config/mobile.php') )
		{
			throw new Exception('Undefined mobile config file!');
		}
		
		include(Application::get()->path . 'config/mobile.php');
		$this->_config = $mobile;
		unset($mobile);
		
		// set a Remote ip_address
		$this->_remote_ip = ( isset($_SERVER['REMOTE_ADDR']) )
		                    ? $_SERVER['REMOTE_ADDR']
		                    : FALSE;
		                    
		// set a User-Agent
		$this->_agent     = ( isset($_SERVER['HTTP_USER_AGENT']) )
		                    ? $_SERVER['HTTP_USER_AGENT']
		                    : FALSE;
		
		if ( ! $this->_remote_ip )
		{
			throw new Exception('Can\'t get Remote IP Address.');
			return;
		}
		
		// first, detect smartphone
		$this->_detect_sp();
		
		// sencond, detect mobile if smartphone is not detected.
		if ( $this->_sp === FALSE )
		{
			$this->_detect_mobile();
		}
	}
	
	
	/**
	 * Growable interface implementation
	 * 
	 * @access public static
	 * @return SZ_Mobile ( extended )
	 */
	public static function grow()
	{
		return Seezoo::$Importer->library('Mobile');
	}
	
	
	// Public methods ================================================
	
	/**
	 * get Accessed User-Agent
	 * @access public
	 * @return string
	 */
	public function get_agent()
	{
		return $this->_agent;
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Access Agent is mobile carrier?
	 * @access public
	 * @return bool
	 */
	public function is_mobile()
	{
		return $this->_mobile;
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Access Agent is Smartphone carrier?
	 * @access public
	 * @return bool
	 */
	public function is_smartphone()
	{
		return $this->_sp;
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * get carrier OS name
	 * @access public
	 * @return string $OS
	 */
	public function os()
	{
		return $this->_sp_os;
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * get OS version
	 * @access public
	 * @retrurn float $os_version;
	 */
	public function version()
	{
		return $this->_os_version;
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * get mobile carrier name
	 * @access public
	 * @return string _mobile_carrier
	 */
	public function carrier()
	{
		return $this->_mobile_carrier;
	}
	
	
	// Carrier judge shortcut methods ================================= //
	
	public function is_docomo()
	{
		return ( $this->_mobile_carrier === 'docomo' ) ? TRUE : FALSE;
	}
	
	
	// ---------------------------------------------------------------
	
	
	public function is_au()
	{
		return ( $this->_mobile_carrier === 'au' ) ? TRUE : FALSE;
	}
	
	
	// ---------------------------------------------------------------
	
	
	public function is_softbank()
	{
		return ( $this->_mobile_carrier === 'softbank' ) ? TRUE : FALSE;
	}
	
	
	// ---------------------------------------------------------------
	
	
	public function is_willcom()
	{
		return ( $this->_mobile_carrier === 'willcom' ) ? TRUE : FALSE;
	}
	
	
	// ---------------------------------------------------------------
	
	
	public function is_iphone()
	{
		return $this->_is_iphone;
	}
	
	
	// ---------------------------------------------------------------
	
	
	public function is_ipad()
	{
		return $this->_is_ipad;
	}
	
	
	// ---------------------------------------------------------------
	
	
	public function is_ipod()
	{
		return $this->_is_ipod;
	}
	
	
	// ---------------------------------------------------------------
	
	
	public function is_android()
	{
		return $this->_is_android;
	}
	
	
	// ---------------------------------------------------------------
	
	
	public function is_webos()
	{
		return $this->_is_webos;
	}
	
	
	// ---------------------------------------------------------------
	
	
	public function is_blackberry()
	{
		return $this->_is_blackberry;
	}
	
	
	// ---------------------------------------------------------------
	
	
	public function is_wp()
	{
		return $this->_is_windowsphone;
	}
	
	
	// ---------------------------------------------------------------
	
	
	public function is_wm()
	{
		return $this->_is_windowsmobile;
	}
	
	
	// ---------------------------------------------------------------
	
	
	public function force_ip_check()
	{
		$this->_config['ip_detection'] = TRUE;
		$this->_detect_mobile();
	}
	
	
	// Private/Protected methods =================================
	
	/**
	 * _detect_sp
	 * SmartPhone carrier detection from User-Agent
	 * @access private
	 * @return void
	 */
	private function _detect_sp()
	{		
		if ( ! $this->_agent )
		{
			return;
		}
		
		$ua = strtolower($this->_agent);
		
		// detect sp
		foreach ( $this->_sp_agents as $key => $agent )
		{
			if ( strpos($ua, $key) !== FALSE )
			{
				$this->_sp = TRUE;
				$this->_sp_os = $agent;
				// set carrier flag
				$this->{'_is_' . strtolower($agent)} = TRUE;
				break;
			}
			else 
			{
				$this->{'_is_' . strtolower($agent)} = FALSE;
			}
		}
		
		// If Smartphone access, detect OS version
		if ( $this->_sp === TRUE )
		{
			$this->_detect_os_version();
		}
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Detect carrier OS version
	 * @access private
	 * @return void
	 */
	private function _detect_os_version()
	{
		switch ( $this->_sp_os )
		{
			case 'Android':
				$regex = '|(?:.+)Android ([0-9]+)\.([0-9]+)(?:.+)|u';
				break;
			case 'WindowsPhone':
				$regex = '|(?:.+)Windows Phone OS ([0-9]+)\.([0-9]+)(?:.+)|u';
				break;
			case 'WindowsMobile':
				$regex = '|(?:.+)IEMobile ([0-9]+)\.([0-9]+)(?:.+)|u';
				break;
			case 'BlackBerry':
				$regex = '|^BlackBerry[0-9]+/([0-9+)\.([0-9]+)(?:.+)|u';
				break;
			case 'iPad':
				$regex = '|(?:.+)CPU OS ([0-9]+)_([0-9]+)(?:.+)|u';
				break;
			default:
				$regex = '|(?:.+)iPhone OS ([0-9]+)_([0-9]+)(?:.+)|u';
				break;
		}
		
		$version = preg_replace($regex, '$1.$2', $this->_agent);
		
		$this->_os_version = floatval($version);
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Mobile carrier detection
	 * @access private
	 * @return void
	 */
	private function _detect_mobile()
	{
		if ( ! $this->_config['ip_detection'] )
		{
			$this->_detect_from_useragent();
			return;
		}
		// Generate mobile ip list
		$cache_filepath = $this->_config['cache_dir']
		                  . $this->_config['cache_file_name'];
		
		// Cache file exsits?
		if ( ! file_exists($cache_filepath) )
		{
			$this->_generate_mobile_ip_list();
		}
		else 
		{
			$this->get_mobile_ip_list_from_cache($cache_filepath);
		}
		
		
		// and, check it
		$this->_check_is_mobile();
		
		// destroy GC
		$this->ip_list = array();
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Mobile access detection from User-Agent string
	 * @access private
	 */
	private function _detect_from_useragent()
	{
		if ( $this->_is_docomo() === TRUE )
		{
			$this->_mobile_carrier = 'docomo';
		}
		else if ( $this->_is_au() === TRUE )
		{
			$this->_mobile_carrier = 'au';
		}
		else if ( $this->_is_softbank() === TRUE )
		{
			$this->_mobile_carrier = 'softbank';
		}
		else if ( $this->_is_willcom() === TRUE )
		{
			$this->_mobile_carrier = 'willcom';
		}
		
		$this->_mobile = ( $this->_mobile_carrier !== '' ) ? TRUE : FALSE;
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * detection access carrier is Docomo?
	 * @access private
	 * @return bool
	 */
	private function _is_docomo()
	{
		return ( preg_match('#\ADoCoMo|\AMozilla.+FOMA#u', $this->_agent) ) ? TRUE : FALSE;
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * detection access carrier is AU (contains Ezweb)?
	 * @access private
	 * @return bool
	 */
	private function _is_au()
	{
		if ( preg_match('#\AKDDI\-|\AMozilla.+KDDI\-#u', $this->_agent) )
		{
			return TRUE;
		}
		else if ( preg_match('#UP\.Browser|\AVodafone#u', $this->_agent) )
		{
			return TRUE;
		}
		return FALSE;
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * detection access carrier is Softbank (contains Vodaphone and J-Phone)?
	 * @access private
	 * @return bool
	 */
	private function _is_softbank()
	{
		$regex = '#\ASoftBank|\ASemulator|\AVemulator|\AMOT\-|\AMOTEMULATOR|\AJ\-PHONE|\AJ\-EMULATOR#u';
		if ( preg_match($regex, $this->_agent) )
		{
			return TRUE;
		}
		return FALSE;
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * detection access carrier is Willcom?
	 * @access private
	 * @return bool
	 */
	private function _is_willcom()
	{
		$regex = '#WILLCOM|DDIPOCKET|WS0[0-9][0-9]SH|MobilePhone#u';
		if ( preg_match($regex, $this->_agent) )
		{
			return TRUE;
		}
		return FALSE;
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Generate carrier ip-band list and create cache
	 * 
	 * @access private
	 */
	private function _generate_mobile_ip_list()
	{
		// do scraping
		foreach ( $this->_config['carrier_urls'] as $carrier => $url )
		{
			$this->ip_list[] = "{$carrier}=========================================";
			$data = @file_get_contents($url);
			if ( ! $data )
			{
				continue;
			}
			if ( $carrier === 'au' )
			{
				$this->_parse_from_html_au($data);
			}
			else 
			{
				$this->_parse_from_html($data);
			}
		}
		
		// Try create cache
		if ( is_writable($this->_config['cache_dir']) )
		{
			$fp = fopen($this->_config['cache_dir'] . $this->_config['cache_file_name'], "wb");
			if ( $fp )
			{
				fwrite($fp, implode("\n", $this->ip_list));
				fclose($fp);
			}
		}
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Generate ip list from cache
	 * @param $cache_filepath
	 */
	private function get_mobile_ip_list_from_cache($cache_filepath)
	{
		// expired check
		$last_modified = filemtime($cache_filepath);
		$expired       = $this->_config['cache_expired_time'];
		
		if ( (int)$last_modified < time() - $expired )
		{
			$this->_generate_mobile_ip_list();
			return;
		}
		
		// create from cache
		$this->ip_list = file($cache_filepath);
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * parse HTML and get ip_list ( for au )
	 * @param $data
	 */
	private function _parse_from_html_au($data)
	{
		
		$regex = "/(\d{2,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}).+\n.+(\/\d{2})/";
		if ( preg_match_all($regex, $data, $matches) )
		{
			$cnt = count($matches[0]) - 1;
			for ( $i = 0; $i <= $cnt; $i++ )
			{
				$this->ip_list[] = $matches[1][$i] . $matches[2][$i];
			}
		}
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * parse HTML and get ip list
	 * @param $data
	 */
	private function _parse_from_html($data)
	{
		$regex = "/\d{2,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}\/\d{2}/";
		if ( preg_match_all($regex, $data, $matches) )
		{
			$cnt = count($matches[0]) - 1;
			for ( $i = 0; $i <= $cnt; $i++ )
			{
				$this->ip_list[] = $matches[0][$i];
			}
		}
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Check access carrier is mobile
	 * 
	 * @access private
	 */
	private function _check_is_mobile()
	{
		$current_carrier = '';
		$remote_ip       = $this->_get_ip_bit($this->_remote_ip);
		
		foreach ( $this->ip_list as $ips )
		{
			if ( ($pos = strpos($ips, '=')) !== FALSE )
			{
				$current_carrier = substr($ips, 0, $pos);
				continue;
			}
			
			$carrier_ip = explode('/', $ips);
			$mask       = $this->_get_mask_bit($carrier_ip[1]);
			$carrier    = $this->_get_ip_bit($carrier_ip[0]);
			
			if ( ($carrier & $mask) == ( $remote_ip & $mask) )
			{
				$this->_mobile = TRUE;
				$this->_mobile_carrier = $current_carrier;
				break;
			}
		}
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Get mask bit
	 * @access private
	 */
	private function _get_mask_bit($bit)
	{
		$mask = 0;
		for( $i = 1; $i <= $bit; $i++){
			$mask++;
			$mask = $mask << 1;
		}
		$mask = $mask << 32-$bit;
		return $mask;
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Get IP bit
	 * @access private
	 */
	private function _get_ip_bit($ip)
	{
		$ips = explode('.', $ip);
		$ipb = (@$ips[0] << 24) | (@$ips[1] << 16) | (@$ips[2] << 8) | (@$ips[3]);
		return $ipb;
	}
}

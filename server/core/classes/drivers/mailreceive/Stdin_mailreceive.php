<?php if ( ! defined('SZ_EXEC') ) exit('access_denied');

/**
 * ====================================================================
 * 
 * Seezoo-Framework
 * 
 * A simple MVC/action Framework on PHP 5.1.0 or newer
 * 
 * 
 * Receive mail from STDIN
 * 
 * @package  Seezoo-Framework
 * @category Drivers
 * @author   Yoshiaki Sugimoto <neo.yoshiaki.sugimoto@gmail.com>
 * @license  MIT Licence
 * 
 * ====================================================================
 */
class SZ_Stdin_mailreceive extends SZ_Mailreceive_driver
{
	
	/**
	 * Get mail from STDIN
	 * 
	 * @access public
	 * @param  int $count
	 * @return SZ_Mail_decoder
	 */
	public function getMail($count)
	{
		// always count 1!
		$count = 1;
		
		$req = Seezoo::getRequest();
		$stdin = $req->stdin();
		
		return new SZ_Mail_decoder($stdin);
	}
}

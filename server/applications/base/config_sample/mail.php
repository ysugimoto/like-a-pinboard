<?php if ( ! defined('SZ_EXEC') ) exit('access_denied');

/**
 * ====================================================================
 * 
 * Seezoo-Framework
 * 
 * A simple MVC/action Framework on PHP 5.1.0 or newer
 * 
 * 
 * Email settings
 * 
 * @package  Seezoo-Framework
 * @category config
 * @author   Yoshiaki Sugimoto <neo.yoshiaki.sugimoto@gmail.com>
 * @license  MIT Licence
 * 
 * ====================================================================
 */

/*
 * --------------------------------------------------------------------
 * Mail sending method
 * 
 * you can choose "smtp" or "php"
 * --------------------------------------------------------------------
 */
$mail['type']      = 'php';

/*
 * --------------------------------------------------------------------
 * Mail-From
 * 
 * Dog mail library use this parameter at default
 * ( enable change in your script )
 * --------------------------------------------------------------------
 */
$mail['from']      = 'you@example.com';

/*
 * --------------------------------------------------------------------
 * Mail-From-Name
 * 
 * Dog mail library use this parameter at default
 * ( enable change in your script )
 * --------------------------------------------------------------------
 */
$mail['from_name'] = '';

/*
 * --------------------------------------------------------------------
 * SMTP setting
 * 
 * If you use SMTP mail sending,
 * please set these parameters.
 * 
 * You can choose authtype belows:
 * NONE     : no auth
 * PLAIN    : AUTH PLAIN
 * LOGIN    : AUTH LOGIN
 * CRAM-MD5 : AUTH CRAM-MD5
 * --------------------------------------------------------------------
 */
$mail['smtp']['hostname']  = 'localhost';
$mail['smtp']['port']      = 25;
$mail['smtp']['authtype']  = 'NONE';
$mail['smtp']['username']  = '';
$mail['smtp']['password']  = '';
$mail['smtp']['keepalive'] = FALSE;



/* ===================================================================
 *  Mail receiver settings
 * ===================================================================*/

 /*
 * --------------------------------------------------------------------
 * Mail receiver
 * 
 * you can choose these:
 *   imap  : IMAP server
 *   pop3  : POP3 server
 *   stdin : get mail from STDIN
 * --------------------------------------------------------------------
 */
$mail['receiver'] = 'stdin';

/*
 * --------------------------------------------------------------------
 * IMAP settings
 * 
 * If you are using a mail server that supports IMAP,
 * please set the following parameters.
 * --------------------------------------------------------------------
 */
$mail['imap']['hostname'] = 'localhost';
$mail['imap']['port']     = 143;
$mail['imap']['email']    = '';
$mail['imap']['password'] = '';
$mail['imap']['ssl']      = FALSE;


/*
 * --------------------------------------------------------------------
 * POP3 settings
 * 
 * If you are using a mail server that supports POP3,
 * please set the following parameters.
 * --------------------------------------------------------------------
 */
$mail['pop3']['hostname'] = 'localhost';
$mail['pop3']['port']     = 110;
$mail['pop3']['email']    = '';
$mail['pop3']['password'] = '';
$mail['pop3']['ssl']      = FALSE;



// End of mail.php
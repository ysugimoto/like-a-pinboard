<?php if ( ! defined('SZ_EXEC') ) exit('access_denied');

/**
 * ====================================================================
 * 
 * Seezoo-Framework
 * 
 * A simple MVC/action Framework on PHP 5.1.0 or newer
 * 
 * 
 * Event settings
 * 
 * @package  Seezoo-Framework
 * @category config
 * @author   Yoshiaki Sugimoto <neo.yoshiaki.sugimoto@gmail.com>
 * @license  MIT Licence
 * 
 * ====================================================================
 */

/*
 * ====================================================================
 * Event definition format
 * 
 * Can be written in the following format,
 * you can specify the timing of the launch event:
 * 
 * <code>
 * Single timing event
 * 
 * $event[<timing>] = array(
 *    'class'   => <classname string>
 *    'function => <function/classmethod string>
 *    'once'    => <bool TRUE or FALSE>
 * );
 * 
 * OR Multiple timing event
 * 
 * $event[<timing>][] = array(
 *    'class'   => <classname>
 *    'function => <functionname>
 *    'once'    => <bool TRUE or FALSE>
 * );
 * </code>
 * 
 * Parameters:
 * <timing>       : Event fire timing name
 * <classname>    : Event fire class name 
 * <functionname> : call function ( method that if classname exists ) name
 * 
 * And please be installed in a app/events/directory,
 * the file name corresponding to the function name / class name.
 * 
 * class/function file was included automatically on fire timing.
 * 
 * ====================================================================
 */




<?php if ( ! defined('SZ_EXEC') ) exit('access_denied');

/**
 * ====================================================================
 * 
 * Seezoo-Framework
 * 
 * A simple MVC/action Framework on PHP 5.1.0 or newer
 * 
 * 
 * MVC base Controller class
 * 
 * @package  Seezoo-Framework
 * @category Classes
 * @author   Yoshiaki Sugimoto <neo.yoshiaki.sugimoto@gmail.com>
 * @license  MIT Licence
 * 
 * ====================================================================
 */

class SZ_Breeder
{
	/**
	 * Request instance
	 * @var Request
	 */
	public $request;
	
	
	/**
	 * Environment instance
	 * @var Environment
	 */
	public $env;
	
	
	/**
	 * View class instance
	 * @var View
	 */
	public $view;
	
	
	/**
	 * Flow methods
	 * @var Flow
	 */
	protected $flows = array();
	
	/**
	 * Routed info object
	 * @var object
	 */
	public $router;
	
	
	/**
	 * Importer class instance
	 * @var Import
	 */
	public $import;
	
	
	/**
	 * Lead instance
	 * @var Lead
	 */
	public $lead;
	
	
	/**
	 * Application instance
	 * @var Applicatiom
	 */
	public $app;
	
	
	public function __construct()
	{	
		$this->app   = Application::get();
		$this->level = Seezoo::sub($this);
		
		// Process level matching
		//if ( $this->level !== $this->app->level )
		//{
		//	throw new RuntimeException('Illigal process number! Direct instantiate is disabled.');
		//}
		
		$this->mode     =  $this->app->mode;
		$this->request  =  Seezoo::getRequest();
		$this->env      =  Seezoo::getENV();
		$this->import   =  Seezoo::$Importer->classes('Importer');
		$this->view     =  new Seezoo::$Classes['View']();
		
		$this->response =& Seezoo::$Response;
		$this->router   =& $this->app->router;
		
		$this->_extractAlias();
		$this->lead = $this->app->router->bootLead();
		
		Injector::injectDIContainer($this, $this->app->router->getInfo('package'));
		Injector::injectByReflection($this);
		
	}
	
	public function getModulePath()
	{
		return $this->router->getInfo('pakcage');
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Extract stacked property-alias
	 * 
	 * @access private
	 */
	private function _extractAlias()
	{
		foreach ( Seezoo::getAliases() as $alias => $prop )
		{
			if ( is_string($prop) )
			{
				if ( isset($this->{$prop}) )
				{
					$this->{$alias} = $this->{$prop};
				}
			}
			else
			{
				$this->{$alias} = $prop;
			}
		}
	}
}
	

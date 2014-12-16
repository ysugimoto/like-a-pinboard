<?php if ( ! defined('SZ_EXEC') ) exit('access_denied');

/**
 * ====================================================================
 * 
 * Seezoo-Framework
 * 
 * A simple MVC/action Framework on PHP 5.1.0 or newer
 * 
 * 
 * Validation
 * 
 * @required seezoo/core/classes/dirvers/validation/Validation_field.php or extended
 * @required seezoo/core/classes/libraries/Verify.php or extended
 * 
 * @package  Seezoo-Framework
 * @category Libraries
 * @author   Yoshiaki Sugimoto <neo.yoshiaki.sugimoto@gmail.com>
 * @license  MIT Licence
 * 
 * ====================================================================
 */

class SZ_Validation extends SZ_Driver implements Growable
{
	/**
	 * Validation field parts
	 * @var array
	 */
	protected $_parts = array();
	
	
	/**
	 * Fieldset group name
	 * @var string
	 */
	protected $_group = 'default';
	
	
	/**
	 * special validate rule regexes
	 * @var string
	 */
	protected $_paramRegex       = '/\A(.+)\[([^\]]+)\]\Z/u';
	protected $_origRegex        = '/\Aorig:([^\[]+)\[?(.+)?\]?\Z/u';
	protected $_syncRegex        = '/\Async:([^\[]+)\[(.+)\]\Z/u';
	protected $_classMethodRegex = '/\A(.+)::(.+)\Z/u';
	protected $_functionalRegex  = '/\A(.+)\(\)\Z/u';
	
	
	/**
	 * Error delimiters
	 * @var array
	 */
	protected $_delimiters = array('<p>', '</p>');
	
	
	/**
	 * Validation target data
	 * @var mixed
	 */
	protected $_targetData;
	
	
	/**
	 * Validateion Field Class 
	 * @var Validateion_Field
	 */
	protected $_filedClass;
	
	
	/**
	 * Verify Library instance
	 * @var Verify
	 */
	protected $_verify;
	
	
	public function __construct()
	{
		parent::__construct();
		
		$this->setGroup();
		
		$this->driver  = $this->loadDriver('Validation_field', FALSE);
		$this->_verify = Seezoo::$Importer->library('Verify');
		
		// append for View
		if ( FALSE !== ($SZ = Seezoo::getInstance()) )
		{
			$SZ->view->assign(array('Validation' => $this));
		}
	}
	
	
	/**
	 * Growable interface implementation
	 * 
	 * @access public static
	 * @return View ( extended )
	 */
	public static function grow()
	{
		return Seezoo::$Importer->library('Validation');
	}
	
	// --------------------------------------------------
	
	
	/**
	 * set error message
	 * 
	 * @access public
	 * @param  string $str
	 * @return bool
	 */
	public function setMessage($rule, $message)
	{
		if ( preg_match($this->_classMethodRegex, $rule, $matches) )
		{
			$rule = $matches[2];
		}
		else if ( preg_match($this->_functionalRegex, $rule, $matches) )
		{
			$rule = $matches[1];
		}
		$this->_verify->setMessage($rule, $message);
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * Create Field Object
	 * 
	 * @access public
	 * @param  string $fieldName
	 * @param  string $label
	 * @return Filed Object
	 */
	public function field($fieldName, $label)
	{
		$obj = new $this->driver($fieldName, $label);
		$this->_parts[$this->_group][$fieldName] = $obj;
		
		return $obj;
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * Set rules at once
	 * 
	 * @access public
	 * @param  array $rules
	 */
	public function setRules($rules = array())
	{
		foreach ( $rules as $rule )
		{
			$this->field($rule['field'], $rule['label'])
			     ->setRules($rule['rules']);
		}
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * Make error strings
	 * 
	 * @access public
	 * @param  string $prefix
	 * @param  string $suffix
	 * @return string
	 */
	public function errorString($prefix = '', $suffix = '')
	{
		$prefix = ( empty($prefix) ) ? $this->_delimiters[0] : $prefix;
		$suffix = ( empty($suffix) ) ? $this->_delimiters[1] : $suffix;
		$msg    = array();
		
		foreach ( $this->_parts[$this->_group] as $v )
		{
			$message = $v->getMessage(FALSE, $prefix, $suffix); 
			if ( ! empty($message) )
			{
				$msg[] = $message;
			}
		}
		return implode("\n", $msg);
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * Import rules from JSON formatted file
	 * 
	 * @access publiv
	 * @param $filePath
	 */
	public function importRulesJSON($filePath)
	{
		if ( ! file_exists($filePath) )
		{
			throw new Exception('import rule file is not exists! file:' . $filePath);
			return;
		}
		
		$dat  = file_get_contents($filePath);
		$json = json_decode($dat);
		
		foreach ( (array)$json as $key => $val )
		{
			$this->field($key, $val->label)
			     ->setRules($val->rules);
		}
		
		unset($json);
		
		return $this;
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * Import rules from XML file
	 * 
	 * @access public
	 * @param  string $filePath
	 * @throws Exception
	 */
	public function importRulesXML($filePath)
	{
		if ( ! file_exists($filePath) )
		{
			throw new InvalidArgumentException('import rule file is not exists! file:' . $filePath);
			return;
		}
		
		$dat = simplexml_load_file($filePath);
		foreach ( $dat->field as $field )
		{
			if ( ! isset($field->name) || ! isset($field->label) || ! isset($field->rules) )
			{
				throw new LogicException('Imported rules XML structure is invalid!');
			}
			$this->field((string)$field->name, (string)$field->label)
			     ->setRules((string)$field->rules);
		}
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * Import rules from Yaml file
	 * 
	 * @access public
	 * @param  string $filePath
	 * @throws Exception
	 */
	public function importRulesYaml($filePath)
	{
		if ( ! file_exists($filePath) )
		{
			throw new Exception('import rule file is not exists! file:' . $filePath);
			return;
		}
		
		$dat = Yaml::loadFile($filePath);
		if ( isset($dat[0]) ) // numbering array
		{
			foreach ( $dat as $value )
			{
				$this->field($value['field'], $value['label'])
				     ->setRules($value['rules']);
			}
		}
		else // hash array
		{
			foreach ( $dat as $field => $value )
			{
				$this->field($field, $value['label'])
				     ->setRules($value['rules']);
			}
		}
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * set error delimiters
	 * 
	 * @access public
	 * @param  string $prefix
	 * @param  string $suffix
	 */
	public function delimiter($prefix, $suffix = '')
	{
		if ( empty($suffix) )
		{
			preg_match('/<([a-zA-Z0-9]+)\s?(?:.+)?>/', $prefix, $match);
			if ( isset($match[1]) )
			{
				$suffix = '</' . $match[1] . '>';
			}
		}
		$this->_delimiters = array($prefix, $suffix);
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * Setup/Change group
	 * @param sring $groupName
	 */
	public function setGroup($groupName = 'default')
	{
		$this->_group = $groupName;
		if ( ! isset($this->_parts[$groupName]) || ! is_array($this->_parts[$groupName]) )
		{
			$this->_parts[$groupName]  = array();
			$this->_errors[$groupName] = array();
		}
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * Set validate target data
	 * 
	 * @access public
	 * @param  mixed $dat
	 */
	public function setData($dat)
	{
		if ( is_array($dat) || is_object($dat) )
		{
			$this->_targetData = $dat;
			return;
		}
		
		switch ( strtolower($dat) )
		{
			case 'get':
				$this->_targetData = $_GET;
				break;
			case 'post':
				$this->_targetData = $_POST;
				break;
			case 'uri':
				$req = Seezoo::getRequest();
				$this->_targetData = $req->uriSegments();
				break;
			default:
				$this->_targetData = $dat;
				break;
		}
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * Run the validation
	 * 
	 * @access public
	 * @param  mixed  $data
	 * @param  string $group
	 * @return bool
	 */
	public function run($data = null, $group = 'default')
	{
		// If data is not exists, use propery or default $_POST.
		if ( ! $data )
		{
			$data = ( $this->_targetData ) ? $this->_targetData : $_POST;
		}
		
		// convert object-to-array
		if ( is_object($data) )
		{
			$data = get_object_vas($data);
		}
		
		$this->_targetData = $data;
		
		// errors count
		$errors    = array();
		// executed stack
		$executed  = array();
		
		// Validate event fire
		Event::fire('before_validation');
		
		// loop of parts
		foreach ( $this->_parts[$group] as $index => $v )
		{
			$name  = $v->getName();
			$rules = $v->getRules();
			// field data is exists?
			if ( ! isset($data[$name]) )
			{
				if ( ! $v->isDelegateValidation() && ! in_array('required', $rules) )
				{
					continue;
				}
				$value = '';
			}
			else
			{
				$value = $data[$name];
			}
			
			// Mark success
			$success = TRUE;
			
			// target data is array?
			$v->setValue($value);
			$is_array = ( is_array($value) ) ? TRUE : FALSE;
			$value    = ( $is_array ) ? $value : array($value);
			
			// Does field enabled to delegete validation?
			if ( $v->isDelegateValidation() )
			{
				$success = $rules[0]->validate($v);
			}
			else
			{
				// loop of rules
				foreach ( $rules as $rule )
				{
					if ( $rule === '' )
					{
						continue;
					}
					
					$class = $this->_verify;
					// Is special syncronized rule?
					if ( preg_match($this->_syncRegex, $rule, $matches) )
					{
						$condition = $this->getField($matches[2]);
						// Is target filed already processed?
						if ( isset($executed[$matches[2]]) && $executed[$matches[2]] === $condition )
						{
							if ( ! $this->{'_sync_' . $matches[1]}($value[0], $condition->getValue()) )
							{
								$msg = sprintf(
									$this->_verify->messages[$matches[1]],
									$condition->getLabel()
								);
								$condition->setMessage($msg);
								$success = FALSE;
							}
						}
						else
						{
							$condition->setRules('required');
							
						}
						continue;
					}
					
					// Parse and format rule, execute class, condition paramter
					$format  = $this->_validateFormat($rule);
					
					// Validation execute
					$success = $this->_execute($v, $format, $value);
					if ( $success === FALSE )
					{
						$errors[] = $name;
					}
				}
				
					// set "validated" value to field and add stack
				$v->setValidatedValue(( $is_array ) ? $value : $value[0]);
				$executed[$name] = $v;
				
				
			}
			
		}
		
		// Validation end event fire
		$status         = new stdClass;
		$status->result = ( count($errors) > 0 ) ? FALSE : TRUE;
		Event::fire('after_validation', $status);
		
		// GC
		unset($errors);
		unset($executed);
		
		return $status->result;
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * Execute validate
	 * 
	 * @access protected
	 * @param  object $field
	 * @param  stdClass $format
	 * @param  array $value ( reference )
	 * @return bool $result
	 */
	protected function _execute($field, stdClass $format, array &$value)
	{
		$errors = array();
		foreach ( $value as $key => $val )
		{
			// verify method execute.
			if ( $format->function )
			{
				$result = call_user_func($format->function, $val);
			}
			else
			{
				$result = $format->class->{$format->rule}($val, $format->condition);
			}
			
			// Does method returns result ( TRUE / FALSE ) flag?
			if ( is_bool($result) )
			{
				if ( $result === FALSE )
				{
					if ( ! isset($this->_verify->messages[$format->rule]) )
					{
						throw new Exception('Undefined Validation message of ' . $format->rule);
					}
					
					// Swap condition string if rule is "matches"
					if ( $format->rule === 'matches' )
					{
						$format->condition = $this->getField($format->extraField)->getLabel();
					}
					// generate error message
					$msg = ( $format->condition !== FALSE )
					         ? sprintf($this->_verify->messages[$format->rule], $field->getLabel(), $format->condition)
					         : sprintf($this->_verify->messages[$format->rule], $field->getLabel());
					$field->setMessage($msg);
					$errors[] = 1;
				}
			}
			// else, method returns processed value.
			else
			{
				$value[$key] = $result;
			}
		}
		
		return ( count($errors) > 0 ) ? FALSE : TRUE;
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * Parse and format validation environment parameters
	 * 
	 * @access private
	 * @param  string $rule
	 * @return stdClass $format
	 */
	protected function _validateFormat($rule)
	{
		$format = new stdClass;
		$format->class      = $this->_verify;
		$format->function   = NULL;
		$format->rule       = $rule;
		$format->condition  = FALSE;
		$format->extraField = NULL;
		
		// Does rule has a condition parameter?
		if ( preg_match($this->_paramRegex, $rule, $matches) )
		{
			list(, $format->rule, $format->condition) = $matches;
			if ( $format->rule === 'matches' )
			{
				$format->extraField = $format->condition;
				$format->condition  = ( isset($this->_targetData[$format->condition]) )
				                       ? $this->_targetData[$format->condition]
				                       : FALSE;
			}
			
			if ( ! method_exists($format->class, $format->rule) )
			{
				throw new BadMethodCallException('Undefined method: ' . get_class($format->class) . '::' . $rule . ' !');
			}
		}
		// Does rule declared by Controller/Process method?
		else if ( preg_match($this->_origRegex, $rule, $matches) )
		{
			// swap class
			$format->class     = Seezoo::getInstance();
			$format->rule      = $matches[1];
			$format->condition = ( isset($matches[2]) ) ? $matches[2] : FALSE;
			
			if ( ! method_exists($format->class, $format->rule) )
			{
				if ( ! isset($format->class->lead) || ! method_exists($format->class->lead, $format->rule) )
				{
					throw new BadMethodCallException('Undefined ' . $format->rule . ' rules method in ' . get_class($format->class) . '!');
				}
				$format->class = $lead;
			}
		}
		// Does rule Class::method formatted?
		else if ( preg_match($this->_classMethodRegex, $rule, $matches) )
		{
			if ( ! class_exists($matches[1]) )
			{
				throw new BadMethodCallException('Validation execute class: ' . $matches[1] . ' is not found!');
			}
			else if ( ! is_callable(array($matches[1], $matches[2])) )
			{
				throw new BadMethodCallException($matches[1] . '::' . $matches[2] . ' is not callable');
			}
			$format->class     = new $matches[1];
			$format->rule      = $matches[2];
		}
		// Does rule function() formatted?
		else if ( preg_match($this->_functionalRegex, $rule, $matches) )
		{
			if ( ! function_exists($matches[1]) )
			{
				throw new BadFunctionCallException('Undefined function:' . $matches[1] . ' is called!');
			}
			$format->function  = $matches[1];
			$format->rule      = $matches[1];
		}
		// No condition.
		else
		{
			if ( ! method_exists($format->class, $rule) )
			{
				throw new BadMethodCallException('Undefined method: ' . get_class($format->class) . '::' . $rule . ' !');
			}
		}
		
		// returns formatted paramters
		return $format;
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * Get field value
	 * 
	 * @access public
	 * @param  string $field
	 * @param  bool   $escape
	 * @return mixed
	 */
	public function value($field = '', $escape = FALSE)
	{
		if ( FALSE === ($obj = $this->getField($field)) )
		{
			return '';
		}
		return ( $escape ) ? prep_str($obj->getValue()) : $obj->getValue();
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * Get error message
	 * 
	 * @access public
	 * @param  string $field
	 * @param  bool   $all
	 * @return string
	 */
	public function error($field, $all = FALSE)
	{
		if ( FALSE === ($obj = $this->getField($field)) )
		{
			return '';
		}
		return $obj->getMessage($all, $this->_delimiters[0], $this->_delimiters[1]);
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * instant check
	 * 
	 * @access public
	 * @param  string $rule
	 * @param  mixed $value
	 * @param  mixed $cond
	 * @return bool
	 */
	public function check($rule, $value, $cond = FALSE)
	{
		if ( method_exists($this->_verify, $rule) )
		{
			if ( is_array($value) )
			{
				foreach ( $value as $val )
				{
					if ( ! $this->_verify->{$rule}($value, $cond) )
					{
						return FALSE;
					}
				}
				return TRUE;
			}
			else
			{
				return $this->_verify->{$rule}($value, $cond);
			}
		}
		return FALSE;
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * Make hidden form input parts
	 * 
	 * @access public
	 * @return string
	 */
	public function getHiddenString()
	{
		$ret = array();
		foreach ( $this->_parts[$this->_group] as $field )
		{
			$ret[] = $field->getHidden();
		}
		return implode("\n", $ret);
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * Get field values
	 * 
	 * @access public
	 * @return array
	 */
	public function getValues()
	{
		$ret = array();
		foreach ( $this->_parts[$this->_group] as $field )
		{
			$ret[$field->getName()] = $field->getValidatedValue(FALSE);
		}
		return $ret;
	}
	
	// --------------------------------------------------
	
	
	/**
	 * get Field object is exists
	 * 
	 * @access public
	 * @param  string $field
	 * @return mixed
	 */
	public function getField($field)
	{
		if ( ! isset($this->_parts[$this->_group])
		    || ! isset($this->_parts[$this->_group][$field]) )
		{
			return FALSE;
		}
		
		$parts = $this->_parts[$this->_group];
		return $parts[$field];
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * Get defined field list
	 * 
	 * @access public
	 * @param  string $group
	 * @return array
	 */
	public function getFields($group = 'default')
	{
		if ( ! isset($this->_parts[$group]) )
		{
			return array();
		}
		return $this->_parts[$group];
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * special validate method handles "sync_required" rule
	 * 
	 * @access protected
	 * @param  $str
	 * @param  object $cond
	 * @return bool
	 */
	protected function _sync_required($str, $cond)
	{
		if ( $this->_verify->required($str) )
		{
			if ( ! $this->_verify->required($cond) )
			{
				return FALSE;
			}
		}
		return TRUE;
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * special validate method handles "synv_blank" rule
	 * 
	 * @access protected
	 * @param  $str
	 * @param  object $cond
	 * @return bool
	 */
	protected function _sync_blank($str, $cond)
	{
		if ( $this->_verify->blank($str) )
		{
			if ( ! $this->_verify->blank($cond) )
			{
				return FALSE;
			}
		}
		return TRUE;
	}
}

<?php if ( ! defined('SZ_EXEC') ) exit('access_denied');

/**
 * ====================================================================
 * 
 * Seezoo-Framework
 * 
 * A simple MVC/action Framework on PHP 5.1.0 or newer
 * 
 * 
 * Validation Field Driver
 * 
 * @required seezoo/core/classes/Verify or extended
 * 
 * @package  Seezoo-Framework
 * @category Drivers
 * @author   Yoshiaki Sugimoto <neo.yoshiaki.sugimoto@gmail.com>
 * @license  MIT Licence
 * 
 * ====================================================================
 */

class SZ_Validation_Field
{
	/**
	 * Field name
	 * @var string
	 */
	protected $_name;
	
	
	/**
	 * Field label
	 * @var string
	 */
	protected $_label;
	
	
	/**
	 * Field value
	 * @var string
	 */
	protected $_value = FALSE;
	
	
	/**
	 * "Validated" Field value
	 * @var string
	 */
	protected $_validatedValue = '';
	
	/**
	 * Validate Rules set
	 * @var array
	 */
	protected $_rules    = array();
	
	
	/**
	 * Error messages
	 * @var array
	 */
	protected $_messages = array();
	
	
	
	/**
	 * Rule reguler exception
	 * @var string
	 */
	protected $_paramRegex = '/\A(.+)\[([^\]]+)\]\Z/u';
	protected $_origRegex  = '/\Aorig:([^\[]+)\[?(.+)?\]?\Z/u';
	protected $_classMethodRegex = '/\A(.+)::(.+)\Z/u';
	protected $_functionalRegex  = '/\A(.+)\(\)\Z/u';
	
	
	
	public function __construct($fieldName, $label)
	{
		$this->_name  = $fieldName;
		$this->_label = $label;
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * Filed name getter
	 * 
	 * @access public
	 * @return string
	 */
	public function getName()
	{
		return $this->_name;
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * Filed label name getter
	 * 
	 * @access public
	 * @return string
	 */
	public function getLabel()
	{
		return $this->_label;
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * Filed value setter
	 * 
	 * @access public
	 * @param  mixed $value
	 * @return string
	 */
	public function setValue($value)
	{
		$this->_value = $value;
		return $this;
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * Validated value setter
	 * 
	 * @access public
	 * @param  mixed $value
	 * @return string
	 */
	public function setValidatedValue($value)
	{
		$this->_validatedValue = $value;
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * Field value getter
	 * 
	 * @access public
	 * @param  bool $escape
	 * @return string
	 */
	public function getValue($escape = FALSE)
	{
		return ( $escape ) ? prep_str($this->_value) : $this->_value;
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * Validated value getter
	 * 
	 * @access public
	 * @param  bool $escape
	 * @return string
	 */
	public function getValidatedValue($escape = FALSE)
	{
		return ( $escape ) ? prep_str($this->_validatedValue) : $this->_validatedValue;
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * Set validate rule
	 * 
	 * @access public
	 * @param  mixed $rules
	 * @return $this
	 */
	public function setRules($rules = '')
	{
		if ( is_array($rules) )
		{
			$this->_rules = $rules;
		}
		else if ( $rules instanceof Validatable )
		{
			$this->_rules[] = $rules;
		}
		else
		{
			foreach ( explode('|', $rules) as $rule )
			{
				if ( ! in_array($rule, $this->_rules) )
				{
					$this->_rules[] = $rule;
				}
			}
		}
		return $this;
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * Delegate validation
	 * 
	 * @access public
	 * @param  object $instance ( Validatealbe interface implemented )
	 * @return $this
	 */
	public function delegate(Validatable $instance)
	{
		$this->_rules = array($instance);
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * Is delegate validation?
	 * 
	 * @access public
	 * @return bool
	 */
	public function isDelegateValidation()
	{
		return ( isset($this->_rules[0])
		         && $this->_rules[0] instanceof Validatable ) ? TRUE : FALSE;
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * Validate rules getter
	 * 
	 * @access public
	 * @return array
	 */
	public function getRules()
	{
		return $this->_rules;
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * Validate Error message setter
	 * 
	 * @access public
	 * @param  string $msg
	 */
	public function setMessage($msg)
	{
		$this->_messages[] = $msg;
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * Message getter
	 * 
	 * @access public
	 * @param  bool $all
	 * @param  string $leftDelimiter
	 * @param  string $rightDelimiter
	 * @return string
	 */
	public function getMessage($all = TRUE, $leftDelimiter = '', $rightDelimiter = '')
	{
		if ( count($this->_messages) === 0 )
		{
			return '';
		}
		$ret = '';
		foreach ( $this->_messages as $msg )
		{
			$ret .= $leftDelimiter . $msg . $rightDelimiter;
			if ( ! $all )
			{
				break;
			}
		}
		
		return $ret;
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * Single validate execute
	 * 
	 * @access public
	 * @param  mixed $value
	 * @return bool
	 */
	public function exec($value)
	{
		$this->_value = $value;
		
		// validate value is array?
		$is_array = ( is_array($value) ) ? TRUE : FALSE;
		$value    = ( $is_array ) ? $value : array($value);
		// load the Verication library
		$success  = TRUE;
		
		if ( $this->isDelegateValidation() )
		{
			###!!!notice!!!####
			// varsion until PHP5.3 causes segmentation fault when member function call that argument with "$this".
			// So we clone $this object and call it.
			$cloned  = clone $this;
			$success = $this->rules[0]->validate($cloned);
		}
		else
		{
			// loop and validate
			foreach ( $this->_rules as $rule )
			{
				if ( $rule === '' )
				{
					continue;
				}
				
				$format  = $this->_validateFormat($rule);
				$success = $this->_execute($format, $value);
			}
		}
		
		// set "validated" value ( maybe same value... )
		$this->setValidatedValue(( $is_array ) ? $value : $value[0]);
		
		// return TRUE(success) / FALSE(failed)
		return $success;
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * Exdute rule method
	 * 
	 * @access protected
	 * @param  stdClass $format
	 * @param  array $value ( reference )
	 * @return bool
	 */
	protected function _execute(stdClass $format, array &$value)
	{
		$verify  = Seezoo::$Importer->library('Verify');
		$success = TRUE;
		
		// value loop and rule-method execute!
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
			// If method returns boolean (TRUE/FALSE), validate error/success.
			if ( is_bool($result) )
			{
				if ( $result === FALSE )
				{
					if ( ! isset($verify->messages[$format->rule]) )
					{
						throw new Exception('Undefined Validation message of ' . $format->rule);
						return FALSE;
					}
					$msg = ( $format->condition !== FALSE )
					         ? sprintf($verify->messages[$format->rule], $this->_label)
					         : sprintf($verify->messages[$format->rule], $this->_label, $format->condition);
					$this->setMessage($msg);
					// switch down flag
					$success = FALSE;
				}
			}
			// else, method returns processed value
			else
			{
				$value[$key] = $result;
			}
		}
		
		return $success;
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * Format validation class/function, rule/metho, condition
	 * 
	 * @access protected
	 * @param  string $rule
	 * @return stdClass
	 */
	protected function _validateFormat($rule)
	{
		$format = new stdClass;
		$format->class     = Seezoo::$Importer->library('Verify');
		$format->function  = NULL;
		$format->rule      = $rule;
		$format->condition = FALSE;
		
		// Does rule has a condition parameter?
		if ( preg_match($this->_paramRegex, $rule, $matches) )
		{
			list(, $format->rule, $format->condition) = $matches;
			if ( $rule === 'matches' )
			{
				throw new LogicException('Cannot use "matches" rule at single validate!');
			}
			else if ( ! method_exists($format->class, $rule) )
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
			
			if ( ! method_exists($format->class, $lead) )
			{
				if ( ! isset($format->class->lead) || ! method_exists($format->class->lead, $format->rule) )
				{
					throw new BadMethodCallException('Undefined ' . $format->rule . ' rules method in ' . get_class($class) . '!');
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
		
		return $format;
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * get Hidden input formatted string
	 * 
	 * @access public
	 * @return string
	 */
	public function getHidden()
	{
		return '<input type="hidden" name="' . $this->getName() . '" value="' . $this->getValue(TRUE) . '" />' . "\n";
	}
}
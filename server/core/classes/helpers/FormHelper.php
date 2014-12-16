<?php if ( ! defined('SZ_EXEC') ) exit('access_denied');

/**
 * ====================================================================
 * 
 * Seezoo-Framework
 * 
 * A simple MVC/action Framework on PHP 5.1.0 or newer
 * 
 * 
 * Form helper
 * 
 * @package  Seezoo-Framework
 * @category helpers
 * @author   Yoshiaki Sugimoto <neo.yoshiaki.sugimoto@gmail.com>
 * @license  MIT Licence
 * 
 * ====================================================================
 */

class SZ_FormHelper implements Growable
{
	/**
	 * Growable interface implementation
	 * 
	 * @access public static
	 * @return View ( extended )
	 */
	public static function grow()
	{
		return Seezoo::$Importer->helper('Form');
	}
	
	
	/**
	 * stack validated value
	 * @var array
	 */
	protected $_validatedValues;
	
	/**
	 * generate <form> tag
	 * 
	 * @access public
	 * @param  string $path
	 * @param  array $attribute
	 * @param  bool $get
	 * @return string
	 */
	public function open($path = '', $attribute = array(), $get = FALSE)
	{
		$method = ( $get ) ? 'get' : 'post';
		return '<form action="' . page_link($path) . '" method="' . $method . '"' . $this->_extractAttribute($attribute) . '>' . "\n";
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * generate <form> tag with multipart
	 * 
	 * @access public
	 * @param  string $path
	 * @param  array $attribute
	 * @return string
	 */
	public function multipart($path = '', $attribute = array())
	{
		return '<form enctype="multipart/form-data" action="' . page_link($path) . '" method="post"' . $this->_extractAttribute($attribute) . '>' . "\n";
	}
	
	public function close()
	{
		return '</form>';
	}
	
	public function text($name, $value = '', $attribute = array())
	{
		if ( is_array($name) )
		{
			$attrs = $name;
		}
		else
		{
			$attrs = array('name' => $name, 'value' => $value);
			$attrs = array_merge($attrs, $attribute);
		}
		
		return '<input type="text"' . $this->_extractAttribute($attrs) . ' />' . "\n";
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * generate submit button
	 * 
	 * @access public
	 * @param  mixed $name
	 * @param  string $value
	 * @param  array $attribute
	 * @return string
	 */
	public function submit($name = '', $value = '', $attribute = array())
	{
		if ( is_array($name) )
		{
			$attrs = $name;
		}
		else
		{
			$attrs = array('name' => $name, 'value' => $value);
			$attrs = array_merge($attrs, $attribute);
		}
		
		return '<input type="submit"' . $this->_extractAttribute($attrs) . ' />' . "\n";
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * generate password input
	 * 
	 * @access public
	 * @param  mixed $name
	 * @param  string$value
	 * @param  array $attribute
	 * @return string
	 */
	public function password($name, $value = '', $attribute = array())
	{
		if ( is_array($name) )
		{
			$attrs = $name;
		}
		else
		{
			$attrs = array('name' => $name, 'value' => $value);
			$attrs = array_merge($attrs, $attribute);
		}
		
		return '<input type="password"' . $this->_extractAttribute($attrs) . ' />' . "\n";
		
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * generate radio button
	 * 
	 * @access public
	 * @param  mixed $name
	 * @param  string $value
	 * @param  bool $checked
	 * @param  string $label
	 * @param  array $attribute
	 * @return string
	 */
	public function radio($name, $value = '', $checked = FALSE, $label = '', $attribute = array())
	{
		if ( is_array($name) )
		{
			$attrs = $name;
		}
		else
		{
			$attrs = array('name' => $name, 'value' => $value, 'checked' => $checked, 'label' => $label);
			$attrs = array_merge($attrs, $attribute);
		}
		
		if ( isset($attrs['checked']) )
		{
			if ( $attrs['checked'] === TRUE )
			{
				$attrs['checked'] = 'checked';
			}
			else
			{
				unset($attrs['checked']);
			}
		}
		
		$labelOpen  = '';
		$labelClose = '';
		if ( isset($attrs['label']) )
		{
			if ( ! empty($attrs['label']) )
			{
				$labelOpen  = '<label>';
				$labelClose = '&nbsp;' . prep_str($attrs['label']) . '</label>';
			}
			unset($attrs['label']);
		}
		
		return $labelOpen . '<input type="radio"' . $this->_extractAttribute($attrs) . ' />' . $labelClose . "\n";
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * generate checkbox
	 * 
	 * @access public
	 * @param  mixed $name
	 * @param  string $value
	 * @param  bool $checked
	 * @param  string $label
	 * @param  array $attribute
	 * @return string
	 */
	public function checkbox($name, $value = '', $checked = FALSE, $label = '', $attribute = array())
	{
		if ( is_array($name) )
		{
			$attrs = $name;
		}
		else
		{
			$attrs = array('name' => $name, 'value' => $value, 'checked' => $checked, 'label' => $label);
			$attrs = array_merge($attrs, $attribute);
		}
		
		if ( isset($attrs['checked']) )
		{
			if ( $attrs['checked'] === TRUE )
			{
				$attrs['checked'] = 'checked';
			}
			else
			{
				unset($attrs['checked']);
			}
		}
		
		$labelOpen  = '';
		$labelClose = '';
		if ( isset($attrs['label']) )
		{
			if ( ! empty($attrs['label']) )
			{
				$labelOpen  = '<label>';
				$labelClose = '&nbsp;' . prep_str($attrs['label']) . '</label>';
			}
			unset($attrs['label']);
		}
		
		return $labelOpen . '<input type="checkbox"' . $this->_extractAttribute($attrs) . ' />' . $labelClose . "\n";
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * generate selectbox
	 * 
	 * @access public
	 * @param  mixed $name
	 * @param  array $value
	 * @param  bool  $selected
	 * @param  array $attribute
	 * @return string
	 */
	public function selectbox($name, $values = array(), $selected = FALSE, $attribute = array())
	{
		if ( is_array($name) )
		{
			$attrs = $name;
		}
		else
		{
			$attrs = array('name' => $name, 'selected' => $selected);
			$attrs = array_merge($attrs, $attribute);
		}
		
		$selected = FALSE;
		if ( isset($attrs['selected']) )
		{
			$selected = $attrs['selected'];
			unset($attrs['selected']);
		}
		
		$options = array();
		foreach ( $values as $key => $val )
		{
			if ( is_array($val) )
			{
				$options[] = '<optgroup label="' . prep_str($key) . '">';
				foreach ( $val as $key2 => $val2 )
				{
					$selected_str = ( $val2 == $selected ) ? ' selected="selected"' : '';
					$options[] = '<option value="' . prep_str($key2) . '"' . $selected_str . '>' . prep_str($val2) . '</options>';
				}
				$options[] = '</optgroup>';
			}
			else
			{
				$selected_str = ( $val == $selected ) ? ' selected="selected"' : '';
				$options[] = '<option value="' . prep_str($key) . '"' . $selected_str . '>' . prep_str($val) . '</options>';
			}
		}
		
		return '<select' . $this->_extractAttribute($attrs) . '>' . "\n"
		        . implode("\n", $options) . "\n"
		        . '</select>' . "\n";
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * generate textarea
	 * 
	 * @access public
	 * @param  mixed $name
	 * @param  string $value
	 * @param  int  $cols
	 * @param  int $rows
	 * @param  array $attribute
	 * @return string
	 */
	public function textarea($name, $value = '', $cols = 60, $rows = 8, $attribute = array())
	{
		if ( is_array($name) )
		{
			$attrs = $name;
		}
		else
		{
			$attrs = array('name' => $name, 'value' => $value, 'cols' => $cols, 'rows' => $rows);
			$attrs = array_merge($attrs, $attribute);
		}
		
		if ( isset($attrs['value']) )
		{
			$value = $attrs['value'];
			unset($attrs['value']);
		}
		
		return '<textarea' . $this->_extractAttribute($attrs) . ">\n"
		         . prep_str($value) . "\n"
		         . '</textarea>' . "\n";
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * generate file upload input
	 * 
	 * @access public
	 * @param  mixed $name
	 * @param  array $attribute
	 * @return string
	 */
	public function file($name, $attribute = array())
	{
		if ( is_array($name) )
		{
			$attrs = $name;
		}
		else
		{
			$attrs = array('name' => $name);
			$attrs = array_merge($attrs, $attribute);
		}
		
		return '<input type="file"' . $this->_extractAttribute($attrs) . ' />' . "\n";
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * generate hidden input
	 * 
	 * @access public
	 * @param  mixed $name
	 * @param  string $value
	 * @param  array $attribute
	 * @return string
	 */
	public function hidden($name, $value = '', $attributes = array())
	{
		$out = '';
		if ( is_array($name) )
		{
			foreach ( $name as $key => $val )
			{
				$out .= '<input type="hidden" name="' . prep_str($key) . '" value="' . prep_str($val) . '" />';
			}
		}
		else
		{
			$attrs = array('name' => $name, 'value' => $value);
			$attrs = array_merge($attrs, $attributes);
			
			$out .= '<input type="hidden"' . $this->_extractAttribute($attrs) . ' />';
		}
		return $out . "\n";
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * parse and generate attribute strings
	 * 
	 * @access protected
	 * @param  array $attribute
	 * @return string
	 */
	protected function _extractAttribute($attributes)
	{
		$attr   = array();
		foreach ( $attributes as $key => $att )
		{
			$attr[] = prep_str($key) . '="' . prep_str($att) . '"';
		}
		return ' ' . implode(' ', $attr);
	}
}
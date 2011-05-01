<?php
/**
 * PW_Form
 *
 * A helper class to build a form based on a PW_Model object
 *
 * This class primarily does these things:
 * 1) Renders the markup of the form fields
 * 2) Adds error messages for any validation errors that are found
 *
 * @package PW_Framework
 * @since 1.0
 */

class PW_Form
{
	protected $_model;
	
	protected $_fields;
	
	public function __construct( $model = null )
	{
		$this->_model = $model;
	}
	
	/**
	 * Adds a field to the form object to be displayed later
	 * @param string $name The name of the model attribute (should be a key in the option array)
	 * @param string $type The type of input (should correspond to a PW_HTML method, e.g., 'textfield' or 'checkbox_list')
	 * @param array $atts Any html attributes you want to pass to the input field
	 */
	public function add_field( $label, $name, $type = 'textfield', $atts = array() )
	{
		$zc = new PW_Zen_Coder;
		
		$errors = $this->_model->get_errors();
		$error = isset($errors[$name]) ? $zc->expand('div.pw-error-message', $errors[$name]) : null;
		
		
		// get the value of the model attribute by this name
		$value = $error ? $this->_model->input[$name] : $this->_model->$name;
		
		// add the model's option name for easy getting from the $_POST variable after submit
		$name = $this->_model->get_name() . '[' . $name . ']';
		
		// create the id from the name
		$id = PW_HTML::get_id_from_name( $name );
		
		if ( $type == 'textfield' ) {
			
			// create the label, value, end error (if one exists) elements
			
			$label = $zc->expand('div.label>label[for="' . $id . '"]', $label);
			$value = $zc->expand('div.field' . ($error ? '.pw-error' : ''), PW_HTML::textfield( $name, $value, $atts) . $error );
			
			// insert them inside a list item and store them in _fields
			$this->_fields[] = '<li>' . $label . $value . '</li>';						
		}
	}
	
	
	
	/**
	 * Renders the markup for all added fields
	 * @param bool $echo Whether or not to echo the rendered markup
	 * @return string The rendered markup (only if $output is set to false)
	 */
	public function render_fields( $echo = true )
	{
		$output = '<ol>';
		foreach( $this->_fields as $field ) {
			$output .= $field;
		}
		$output .= '</ol>';
	 	
		if ($echo) {
			echo $output;
		} else {
			return $output;
		}
	}
	
	
	
	
	
	
}
<?php
/**
 * PW_Settings_Form
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

class PW_Settings_Form
{
	protected $_model;

	
	public function __construct( $model = null )
	{
		$this->_model = $model;
	}

	public function set_model( $model ) {
		$this->_model = $model;
	}
	
	/**
	 * @param string $property The model option property
	 * @param array $atts @see PW_HTML::tag() for details
	 * @param string $unchecked_value An optional default value in case the box is left unchecked
	 * @param string $extra Any addition markup you want to display after the input element
	 * @return string The generated HTML markup
	 * @since 1.0
	 */
	public function checkbox( $property, $atts=array(), $unchecked_value, $extra = '' )
	{
		$zc = new PW_Zen_Coder;
		extract( $this->get_field_data_from_model($property) ); // return $error, $label, $name, $value, $id
		
		// Whether or not the checked is checked
		$selected = $this->_model->$property == $value;
		
		// create the label, value, end error (if one exists) elements		
		$label = $zc->expand('div.label>label[for="' . $id . '"]', $label);
		$value = $zc->expand('div.field' . ($error ? '.pw-error' : ''), PW_HTML::checkbox( $name, $selected, $atts, $unchecked_value) . $extra . $error );

		return '<li>' . $label . $value . '</li>';		
	}
	
	/**
	 * @param string $property The model option property
	 * @param array $items A list of value=>label pairs representing each individual option elements
	 * @param string $separator Markup to put between each radio button
	 * @param array $atts @see PW_HTML::tag() for details
	 * @param string $extra Any addition markup you want to display after the input element
	 * @return string The generated HTML markup
	 * @since 1.0
	 */
	public function radio_button_list( $property, $items, $separator, $atts=array(), $extra = '' )
	{
		$zc = new PW_Zen_Coder;
		extract( $this->get_field_data_from_model($property) ); // return $error, $label, $name, $value, $id
		
		// create the label, value, end error (if one exists) elements		
		$label = $zc->expand('div.label>label[for="' . $id . '"]', $label);
		$value = $zc->expand('div.field' . ($error ? '.pw-error' : ''), PW_HTML::radio_button_list( $name, $items, $value, $separator, $atts) . $error );

		return '<li>' . $label . $value . '</li>';		
	}
	
	/**
	 * @param string $property The model option property
	 * @param array $items A list of value=>label pairs representing each individual option elements
	 * @param array $atts @see PW_HTML::tag() for details
	 * @param string $extra Any addition markup you want to display after the input element
	 * @return string The generated HTML markup
	 * @since 1.0
	 */
	public function select( $property, $items, $atts=array(), $extra = '' )
	{
		$zc = new PW_Zen_Coder;
		extract( $this->get_field_data_from_model($property) ); // return $error, $label, $name, $value, $id
		
		// create the label, value, end error (if one exists) elements		
		$label = $zc->expand('div.label>label[for="' . $id . '"]', $label);
		$value = $zc->expand('div.field' . ($error ? '.pw-error' : ''), PW_HTML::select( $name, $items, $value, $atts) . $error );

		return '<li>' . $label . $value . '</li>';		
	}
	
	
	/**
	 * @param string $property The model option property
	 * @param array $atts @see PW_HTML::tag() for details
	 * @param string $extra Any addition markup you want to display after the input element
	 * @return string The generated HTML markup
	 * @since 1.0
	 */
	public function textfield( $property, $atts=array(), $extra = '' )
	{
		$zc = new PW_Zen_Coder;
		extract( $this->get_field_data_from_model($property) ); // return $error, $label, $name, $value, $id
		
		// create the label, value, end error (if one exists) elements		
		$label = $zc->expand('div.label>label[for="' . $id . '"]', $label);
		$value = $zc->expand('div.field' . ($error ? '.pw-error' : ''), PW_HTML::textfield( $name, $value, $atts) . $error );

		return '<li>' . $label . $value . '</li>';		
	}
	
	
	/**
	 * @param string $property The model option property
	 * @return array An array of the property's id, name (the HTML attribute), label, value, and error (if one exists)
	 * @since 1.0
	 */
	protected function get_field_data_from_model( $property )
	{	
		$errors = $this->_model->get_errors();
		$error = isset($errors[$property]) ? $zc->expand('div.pw-error-message', $errors[$property]) : null;
		
		// get the label of this property
		$labels = $this->_model->labels();
		$label = $labels[$property];
		
		// get the value of the model attribute by this name
		// if there was a validation error, get the previously submitted value
		// rather than what's stored in the database
		$value = $error ? $this->_model->input[$property] : $this->_model->$property;
		
		// add the model's option name for easy getting from the $_POST variable after submit
		$name = $this->_model->get_name() . '[' . $property . ']';
		
		// create the id from the name
		$id = PW_HTML::get_id_from_name( $property );
		
		return array( 'error'=>$error, 'label'=>$label, 'value'=>$value, 'name'=>$name, 'id'=>$id );
	}
}
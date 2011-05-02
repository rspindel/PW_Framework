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
	// Don't worry about creating extra markup if {extra} or {error} is empty.
	// All empty tags are removed before output.
	public $template = 
		'<li>
			<div class="label">{label}</div>
			<div class="field {error_class}">
				{field}
				<span class="description">{desc}</span>
				<div class="extra">{extra}</div>
				<div class="{error_class}">{error}</div>
			</div>
		</li>';

	public $error_class = 'pw-error';


	protected $_model;
	
	
	public function __construct( $model = null )
	{
		$this->_model = $model;
	}

	public function set_model( $model ) {
		$this->_model = $model;
	}
	
	public function render_field($label, $field, $desc, $extra, $error)
	{	
		$output = str_replace(
			array('{label}','{field}','{desc}','{extra}','{error}', '{error_class}'),
			array($label, $field, $desc, $extra, $error, $error ? $this->error_class : '' ),
			$this->template
		);
		
		// then remove any empty tags
		$output = preg_replace('/<[^>\/]+><\/[^>]+>/', '', $output);

		return $output;
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
		extract( $this->get_field_data_from_model($property) ); // returns $error, $label, $name, $value, $id
		
		// Make sure value is set (default to "1")
		$atts['value'] = isset($atts['value']) ? $atts['value'] : "1";
		
		// Determine if the checkbox should be selected
		$selected = $value == $atts['value'];

		$field = PW_HTML::checkbox( $name, $selected, $atts, $unchecked_value) . PW_HTML::label($desc, $id);
		
		// set the $desc value to '' because the checkbox is already using it for generate the label		
		return $this->render_field($label, $field, '', $extra, $error);	
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
	public function checkbox_list( $property, $items, $separator, $atts=array(), $extra = '' )
	{
		extract( $this->get_field_data_from_model($property) ); // returns $error, $label, $name, $value, $id
		$field = PW_HTML::checkbox_list( $name, $items, $value, $separator, $atts);
		
		echo esc_html($field);
		
		return $this->render_field($label, $field, $desc, $extra, $error);
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
		extract( $this->get_field_data_from_model($property) ); // returns $error, $label, $name, $value, $id
		$field = PW_HTML::radio_button_list( $name, $items, $value, $separator, $atts);
		return $this->render_field($label, $field, $desc, $extra, $error);
	}
	
	
	/**
	 * Create a new section with title and optional description
	 * @param string $title The section title
	 * @param string $desc Optional description text to go below the title
	 * @return string The generated HTML markup
	 * @since 1.0
	 */
	public function section( $title, $desc = null )
	{
		$title = '<h3>' . $title . '</h3>';
		$desc = $desc ? '<p>' . $desc . '</p>' : '';
		return $title . $desc;
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
		extract( $this->get_field_data_from_model($property) ); // returns $error, $label, $name, $value, $id
		
		$label = PW_HTML::label($label, $id);
		$field = PW_HTML::select( $name, $items, $value, $atts);
		
		return $this->render_field($label, $field, $desc, $extra, $error);	
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
		extract( $this->get_field_data_from_model($property) ); // returns $error, $label, $desc, $name, $value, $id
		
		$label = PW_HTML::label($label, $id);
		$field = PW_HTML::textfield( $name, $value, $atts);
		
		return $this->render_field($label, $field, $desc, $extra, $error);
	}
	
	
	/**
	 * @param string $property The model option property
	 * @return array An array of the property's id, name (the HTML attribute), label, desc, value, and error (if one exists)
	 * @since 1.0
	 */
	protected function get_field_data_from_model( $property )
	{	
		$zc = new PW_Zen_Coder;
				
		$errors = $this->_model->get_errors();
		$error = isset($errors[$property]) ? $zc->expand('div.pw-error-message', $errors[$property]) : null;
		
		// get the label and description of this property
		$labels = $this->_model->data();
		$label = isset($labels[$property]['label']) ? $labels[$property]['label'] : '';
		$desc = isset($labels[$property]['desc']) ? $labels[$property]['desc'] : '';
		
		// get the value of the model attribute by this name
		// if there was a validation error, get the previously submitted value
		// rather than what's stored in the database
		$value = $error ? $this->_model->input[$property] : $this->_model->$property;
		
		// add the model's option name for easy getting from the $_POST variable after submit
		$name = $this->_model->get_name() . '[' . $property . ']';
		
		// create the id from the name
		$id = PW_HTML::get_id_from_name( $name );
		
		return array( 'error'=>$error, 'label'=>$label, 'desc'=>$desc, 'value'=>$value, 'name'=>$name, 'id'=>$id );
	}
}
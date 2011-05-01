<?php
/**
 * PW_Model
 *
 * The base model object for WordPress option(s) data (stored in the options table)
 *
 * This class primarily does these things:
 * 1) Stores default values and parsing them up request
 * 2) Adding validation rules
 *
 * @package PW_Framework
 * @since 1.0
 */

class PW_Model
{
	/**
	 * If a form was submitted, this will be the value of the submitted option data
	 * @since 1.0
	 */
	public $input = array();
	
	
	/**
	 * The title of options
	 * This value is used as the default value for both the options page heading and nav menu text
	 * @since 1.0
	 */
	protected $_title = '';
	
	/**
	 * The name of the option in the options table
	 * This value must be overridden in a subclass.
	 * @since 1.0
	 */
	protected $_name = '';
	
	/**
	 * The current value of the option parsed against the default value
	 * @since 1.0
	 */
	protected $_option = array();
		
	/**
	 * An array of validation errors if any exist
	 * @since 1.0
	 */
	protected $_errors = array();
	
	/**
	 * Whether or not the option should be stored as autoload, defaults to TRUE
	 * @since 1.0
	 */
	protected $_autoload = true;

	/**
	 * Associate the option with this model instance. If the option doesn't exist, create it
	 * @since 1.0
	 */
	public function __construct()
	{
		// first of all, make sure the option name is declared in this model object
		if ( !$this->_name ) {
			wp_die( 'Error: the $_name varible must be specified to use subclasses of PW_Model. It should be the same as the option name in the options table.' );
		}
		
		// if the option is already stored in the database, get it and merge it with the defaults;
		// otherwise, store the defaults
		if ( $option = get_option($this->_name) ) {
			$this->_option = $this->merge_with_defaults($option);
		} else {
			add_option( $this->_name, $this->defaults(), '', $this->_autoload );
			$this->_option = $this->defaults();
		}
	}
	
	
	/**
	 * PHP getter magic method.
	 * This method is overridden so that option attributes can be accessed like properties.
	 * @param string $name The key in the option array
	 * @return mixed property value
	 * @see getAttribute
	 */
	public function __get($name)
	{
		
		if ( isset($this->_option[$name]) ) {
			return $this->_option[$name];
		}
	}
	
	
	/**
	 * PHP setter magic method.
	 * This method is overridden so that option attributes can be accessed like properties.
	 * @param string $name The key in the option array
	 * @param mixed $value The value to set
	 */
	public function __set( $name, $value )
	{
		if ( isset($this->_option[$name]) ) {
			$this->_option[$name] = $value;
		}
	}
	
	
	/**
	 * Adds an error
	 * @param string $attribute The option attribute name
	 * @param string $message The error message
	 * @since 1.0
	 */
	public function add_error( $attribute, $message )
	{
		// Only add an error if an error for this attribute doesn't already exists
		// Only the first error encountered will be reported, order the validation rules based on this
		if ( empty($this->_errors[$attribute]) ) {
			$this->_errors[$attribute] = $message;
		}
	}

	
	/**
	 * Validates the option against the validation rules returned by $this->rules()
	 * @param array $option of option to be validated.
	 * @return array The default attributes and values
	 * @since 1.0
	 */
	public function validate($option)
	{
		$valid = true;
		$rules = $this->rules();
		foreach( $rules as $rule)
		{
			// remove spaces and then split up the comma delimited attribute string into an array
			$attributes = str_replace(' ', '', $rule['attributes']);
			$attributes = strpos($attributes, ',') === false ? array($attributes) : explode(',', $attributes);
			foreach ($attributes as $attribute) {
				if ( $error = call_user_func($rule['validator'], $option[$attribute]) ) {
					$message = isset($rule['message']) ? $rule['message'] : $error;
					$message = str_replace("{attribute}", $attribute, $message);
					$this->add_error( $attribute, $message );
					$valid = false;
				}
			}
		}
		return $valid;
	}
	
	
	/**
	 * Save the option to the database if (and only if) the option passes validation
	 * @param array $option The option value to store
	 * @return boolean Whether or not the option was successfully saved
	 * @since 1.0
	 */
	public function save( $option )
	{
		if ( $this->validate($option) ) {
			$this->_errors = array();
			$this->_option = $option;
			update_option( $this->_name, $option );
			return true;
		} else {
			return false;
		}
	}
	
	
	/**
	 * Return any validation errors that exist
	 * @return array a list of validation errors keyed to the option array keys
	 * @since 1.0
	 */	
	public function get_errors()
	{
		return $this->_errors;
	}
	
	/**
	 * Get the current value of the option
	 * @since 1.0
	 */
	public function get_option()
	{
		if ( $this->_option ) {
			return $this->_option;
		} else {
			return $this->_option = $this->merge_with_defaults( get_option($this->_name) );
		}
	}
	
	/**
	 * Return the name (should be the option name)
	 * @return string The title
	 * @since 1.0
	 */	
	public function get_name()
	{
		return $this->_name;
	}
	
	/**
	 * Return the title
	 * @return string The title
	 * @since 1.0
	 */	
	public function get_title()
	{
		return $this->_title;
	}

	
	
	/**
	 * Returns an array specifying the default option property values
	 * @return array The default property values (ex: array( $property => $value ))
	 * @since 1.0
	 */
	protected function defaults()
	{
		return array();
	}
	
	
	/**
	 * Returns an array specifying the various labels and descriptions associated with each property
	 * HTML characters are allowed within the label and description string in case you want to get
	 * more complex, but make sure you check with the PW_Settings_Form::template() so your
	 * markup doesn't clash
	 * @return array The property labels
	 * @since 1.0
	 */
	protected function labels()
	{
		/* Override like this:
		return array(
			'prop1' => array(
				'label' => 'Prop1 Label',
				'desc' => 'This is a description of Prop1',
			),
			'prop2' => array(
				'label' => 'Prop2 Label',
			),
			'prop3' => array(
				'desc' => 'Prop3 only has a description',
			),

		)
		*/
		return array();
	}

	
	
	/**
	 * Merges an option with the defaults from self::defaults(). The default uses wp_parse_args()
	 * Override in a child class for custom merging.
	 * @return array The merged option
	 * @since 1.0
	 */
	protected function merge_with_defaults( $option )
	{
		return wp_parse_args( $option, $this->defaults() );
	}
	
	
	
	
}
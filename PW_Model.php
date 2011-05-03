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
	 * Whether or option data was just updated
	 * @since 1.0
	 */
	protected $_updated = false;
	
	
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
	 * This method is overridden so that option properties can be directly accessed
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
	 * This method is overridden so that option properties can be directly accessed
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
	 * @param string $property The option property name
	 * @param string $message The error message
	 * @since 1.0
	 */
	public function add_error( $property, $message )
	{
		// Only add an error if an error for this property doesn't already exists
		// Only the first error encountered will be reported, order the validation rules based on this
		if ( empty($this->_errors[$property]) ) {
			$this->_errors[$property] = $message;
		}
	}

	
	/**
	 * Validates the option against the validation rules returned by $this->rules()
	 * @param array $option of option to be validated.
	 * @return array The default properties and values
	 * @since 1.0
	 */
	public function validate($option)
	{
		$valid = true;
		$rules = $this->rules();
		foreach( $rules as $rule)
		{
			// remove spaces and then split up the comma delimited property string into an array
			$properties = str_replace(' ', '', $rule['properties']);
			$properties = strpos($properties, ',') === false ? array($properties) : explode(',', $properties);
			foreach ($properties as $property)
			{
				// set the input to null if no value was passed but a validation rules was set
				// this will allow for an error in a situation where someone used firebug to delete HTML dynamically
				$input = isset($option[$property]) ? $option[$property] : null;
				
				// create an array of values from the rule definition to pass as method arguments to the callback function
				$args = $rule;
				array_unshift($args, $input);
				unset($args['properties']);
				unset($args['validator']);
				unset($args['message']);
				
				if ( $error = call_user_func_array( $rule['validator'], $args) ) {
					$message = isset($rule['message']) ? $rule['message'] : $error;
					$message = str_replace("{property}", $this->get_label($property), $message);
					$this->add_error( $property, $message );
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
			$this->_updated = true;
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
	 * Return the properties label
	 * @param string $property The option property
	 * @return string The label of the property from the data array
	 * @since 1.0
	 */	
	public function get_label( $property )
	{
		$data = $this->data();
		if ( isset($data[$property]['label']) ) {
			return $data[$property]['label'];
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
	 * Returns whether or not the option was just updated
	 * @return boolean
	 * @since 1.0
	 */	
	public function was_updated()
	{
		return $this->_updated;
	}
	
	
	/**
	 * Returns an array specifying the default option property values
	 * @return array The default property values (ex: array( $property => $value ))
	 * @since 1.0
	 */
	protected function defaults()
	{
		$defaults = array();
		$data = $this->data();
		foreach($data as $property=>$value) {
			$defaults[$property] = isset($value['default']) ? $value['default'] : '';
		}
		return $defaults;
	}
	
	
	/**
	 * Returns a multi-dimensional array of the label, description, and default value of each property
	 * HTML characters are allowed within the label and description strings
	 * @return array The property labels
	 * @since 1.0
	 */
	protected function data()
	{
		/* Override would look like this:
		return array(
			'prop1' => array(
				'label' => 'Prop1 Label',
				'desc' => 'This is a description of Prop1',
				'default' => 'Foo',
			),
			'prop2' => array(
				'label' => 'Prop2 Label',
				'default' => 'Bar'
			),
			'prop3' => array(
				'desc' => 'Prop3 only has a description',
			),
		)
		*/
		return array();
	}


	/**
	 * Returns a multi-dimensional array of the validation rules
	 * each returned rule is an array with the following keys:
	 * 1) 'properties' => a comma separated list of option property names
	 * 2) 'validator' => a php callback function that returns true if valid and false or an error message if invalid
	 * 3) 'message' => (optional) a custom message to override the default one (use {property} to refer to that property's label value)
	 * 4) '...' => (optional) Addition key-value pairs that will be passed to the callback function (order matters!)
	 * @return array The validation rules
	 * @since 1.0
	 */
	protected function rules()
	{
		/* Override would look like this:
		return array(
			array(
				'properties' => 'year_count, year_format, year_template',
			 	'validator'=> array('PW_Validator', 'required')
				'message' => '{property} is required, biatch!'
			),
			array(
				'properties' => 'year_format',
			 	'validator'=> array('PW_Validator', 'match')
				'message' => 'There is an error on field {property}.'
				'pattern' => '/[1-9]{2,4}/',
			),
			array(
				'properties' => 'order',
				'validator' => array('PW_Validator', 'in_array'),
				'haystack' => array('ASC','DESC'),
			),		
		);
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
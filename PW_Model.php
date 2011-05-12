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

class PW_Model extends PW_Object
{
	/**
	 * @var bool Whether or not to use ajax validation on the form
	 * @since 1.0
	 */
	protected $_ajax_validation = true;
	
	
	/**
	 * If a form was submitted, this will be the value of the submitted option data
	 * @since 1.0
	 */
	protected $_input = array();
	
	
	/**
	 * Whether or option data was just updated
	 * @since 1.0
	 */
	protected $_updated = false;
	
	
	/**
	 * The title of this model
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
	 * The user capability required to edit this model's option
	 * @since 1.0
	 */
	protected $_capability = 'manage_options';
		
	/**
	 * An array of validation errors if any exist
	 * @since 1.0
	 */
	protected $_errors = array();
	
	/**
	 * Whether or not the option should be stored as autoload, defaults to 'yes'
	 * @var string 'yes' or 'no'
	 * @since 1.0
	 */
	protected $_autoload = 'yes';

	
	/**
	 * @var string The controller associated with this model
	 */
	protected $_controller;


	/**
	 * Associate the option with this model instance. If the option doesn't exist, create it
	 * @since 1.0
	 */
	public function __construct()
	{
		$this->get_option();
		
		// If the POST data is set and the nonce checks out, validate and save any submitted data
		if ( isset($_POST[$this->_name]) && check_admin_referer( $this->_name . '-options' ) ) {
			
			// get the options from $_POST
			$this->_input = stripslashes_deep($_POST[$this->_name]);
			
			// save the options
			$this->save($this->_input);
		}
		 		
		// add the actions for ajax validation
		if ($this->_ajax_validation) {
			wp_register_script( 'pw_ajax_validation', PW_FRAMEWORK_URL . '/js/ajax_validation.js', 'jquery', false, true );
			add_action( 'admin_print_footer_scripts', array($this, 'add_ajax_validation' ) );
		}
	}
	
	
	/**
	 * @see parent
	 * @since 1.0
	 */
	public function __get( $name )
	{
		// If we're getting $this->_option, return $this->get_option() instead
		if ( 'option' == $name ) {
			return $this->get_option();
		} else {
			return parent::__get( $name );
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
	public function validate($input)
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
				// set the field to null if no value was passed but a validation rules was set
				// this will allow for an error in a situation where someone used firebug to delete HTML dynamically
				$field = isset($input[$property]) ? $input[$property] : null;
				
				// create an array of values from the rule definition to pass as method arguments to the callback function
				$args = $rule;
				array_unshift($args, $field);
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
		
		// Add an alert for any errors
		if ( $this->errors ) {
			PW_Alerts::add(
				'error',
				'<p><strong>Please fix the following errors and trying submitting again.</strong></p>' . ZC::r('ul>li*' . count($this->errors), array_values($this->errors) ) ,
				0
			);
		}
			
		return $valid;
	}
	
	
	/**
	 *
	 */
	public function add_ajax_validation()
	{
		wp_localize_script( 'pw_ajax_validation', 'ajax_validation', array(
			'call_ajax' => 'true'
		));
		wp_print_scripts( 'pw_ajax_validation' );
	}
	
	
	/**
	 * Save the option to the database if (and only if) the option passes validation
	 * @param array $option The option value to store
	 * @return boolean Whether or not the option was successfully saved
	 * @since 1.0
	 */
	public function save( $input )
	{
		if ( $this->validate($input) ) {
			$this->_errors = array();
			$this->_option = $input;
			update_option( $this->_name, $this->_option);
			PW_Alerts::add('updated', '<p><strong>Settings Saved</strong></p>' );				
			return true;
		}
		// If you get to here, return false
		return false;
	}
	
	
	/**
	 * if the option is already stored in the database, get it and merge it with the defaults;
	 * otherwise, store the defaults
	 * @since 1.0
	 */
	public function get_option()
	{
		// If the option is already set in this model, return that
		if ( $this->_option ) {
			return $this->_option;
		} else {
			// If the option exists in the database, merge it with the defaults and return
			if ( $this->_option = get_option($this->_name) ) {
				return $this->_option = $this->merge_with_defaults( $this->_option );
			}
			// Still here? That means you need to create a new option with the default values
			add_option( $this->_name, $this->_option = $this->defaults(), '', $this->_autoload );
			return $this->_option;
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
	
	/**
	 * List any properties that should be readonly
	 * Call array_merge() with parent::readonly() when subclassing to add more values
	 * @return array A list of properties the magic method __set() can't access
	 * @since 1.0
	 */
	protected function readonly()
	{ 
		return array_merge( parent::readonly(), array('name', 'option', 'title') );
	}

}
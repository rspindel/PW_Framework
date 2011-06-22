<?php
/**
 * PW_MultiModel
 *
 * A PW_MultiModel stores an option that is an array of model objects
 *
 * The basic structure of the option is as follows:
 *  array(
 *  	0 => array(
 *  		'prop1' => 'prop1 value',
 *  		'prop2' => 'prop2 value',
 *  		'prop3' => 'prop3 value',
 *  	),
 *  	1 => array(
 *  		'prop1' => 'prop1 a different value',
 *  		'prop2' => 'prop2 a different value',
 *  		'prop3' => 'prop3 a different value',
 *  	),
 *  	'auto_id' => 2,
 *  )
 *
 * @package PW_Framework
 * @since 0.1
 */

class PW_MultiModel extends PW_Model
{	
	/**
	 * @var int The model instance (the option array key) currently being used
	 * @since 0.1
	 */
	protected $_instance;


	/**
	 * @var int The singular title to describe a single model instance
	 * @since 0.1
	 */
	protected $_singular_title = '';
	
	
	/**
	 * Check to see if this model should be deleted, then run parent::__construct()
	 * @since 0.1
	 */
	public function __construct()
	{				
		$this->get_option();
	}
	
	
	/**
	 * PHP getter magic method.
	 * This method is overridden so that model properties can be directly accessed
	 * @param string $name The key in the option array
	 * @return mixed property value
	 * @see getAttribute
	 */
	public function __get($name)
	{	
		if ( 'singular_title' == $name ) {
			return $this->_singular_title ? $this->_singular_title : $this->_title;
		}
		return parent::__get($name);
	}

	
	/**
	 * Save the option to the database if (and only if) the option passes validation
	 * @param array $option The option value to store
	 * @param int $instance The instance to save
	 * @return boolean Whether or not the option was successfully saved
	 * @since 0.1
	 */
	public function save( $input, $instance = 0 )
	{	
		if ( $this->validate($input) ) {
			$this->_errors = array();
						
			// set the instance ID and increment the auto_id
			$instance = $instance == 0 ? $this->_option['auto_id']++ : $_POST['_instance'];				
			$this->_option[$instance] = $input;
			$this->update_option($this->_option);
			PW_Alerts::add('updated', '<p><strong>Settings Saved</strong></p>' );				
			return true;
		}
		// If you get to here, return false
		return false;
	}
	
	
	/**
	 * Returns an array specifying the default option property values as index 0, and an auto_id of 1
	 * @return array The default property values
	 * @since 0.1
	 */
	protected function defaults()
	{
		$defaults = array();
		$data = $this->data();
		foreach($data as $property=>$value) {
			$defaults[$property] = isset($value['default']) ? $value['default'] : '';
		}
		return array( 0 => $defaults, 'auto_id' => 1 );
	}
	
	
	/**
	 * Merges a single model at a certain index within the multi model with the defaults from self::defaults()
	 * Override in a child class for custom merging.
	 * @see parent
	 * @return array The merged option
	 * @since 0.1
	 */
	protected function merge_with_defaults( $option )
	{		
		$defaults = $this->defaults();
		
		foreach( $option as $key=>$instance )
		{			
			if ( $key !== 'auto_id')  {
				$option[$key] = wp_parse_args( $instance, $defaults[0] );
			}
		}
	
		return $option;
	}
	
	
	/**
	 * Return true if the instance value is 0, meaning we're on a 'create new' tab
	 * @return bool
	 * @since 0.1
	 */	
	public function is_new()
	{
		return (int) $this->_instance === 0;
	}

	
	/**
	 * List any properties that should be readonly
	 * Call array_merge() with parent::readonly() when subclassing to add more values
	 * @see parent
	 * @return array A list of properties the magic method __set() can't access
	 * @since 0.1
	 */
	protected function readonly()
	{ 
		return array_merge( parent::readonly(), array('singular_title') );
	}
	
	
}
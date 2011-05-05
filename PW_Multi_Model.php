<?php
/**
 * PW_Multi_Model
 *
 * A PW_Multi_Model stores an option that is an array of model objects
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
 *  	'auto_id' => 2
 *  )
 *
 * @package PW_Framework
 * @since 1.0
 */

class PW_Multi_Model extends PW_Model
{	
	/**
	 * @var int The model instance (the option array key) currently being used
	 * @since 1.0
	 */
	protected $_instance;
	
	
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
			$this->_option = $option;
			$this->_updated = true;
			update_option( $this->_name, $input );
			return true;
		} else {
			return false;
		}
	}
	
	
	/**
	 * Returns an array specifying the default option property values as index 0, and an auto_id of 1
	 * @return array The default property values
	 * @since 1.0
	 */
	protected function defaults()
	{
		$defaults = array();
		$data = $this->data();
		foreach($data as $property=>$value) {
			$defaults[$property] = isset($value['default']) ? $value['default'] : '';
		}
		return array( 0 => $defaults, 'auto_id' => 1);
	}
	
	
	/**
	 * Merges a single model at a certain index within the multi model with the defaults from self::defaults()
	 * Override in a child class for custom merging.
	 * @return array The merged option
	 * @since 1.0
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
	
	
}
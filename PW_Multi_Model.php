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
	/*
	array(
		0 => array(
			'prop1' => 'prop1 value',
			'prop2' => 'prop2 value',
			'prop3' => 'prop3 value',
		),
		1 => array(
			'prop1' => 'prop1 a different value',
			'prop2' => 'prop2 a different value',
			'prop3' => 'prop3 a different value',
		),
		'auto_id' => 2
	)
	*/
	
	/**
	 * @var int The model instance (the option array key) currently being used
	 * @since 1.0
	 */
	protected $_instance;
	
	/**
	 * Associate the option with this model instance. If the option doesn't exist, create it
	 * @since 1.0
	 */
	public function __construct()
	{
		// first of all, make sure the option name is declared in this model object
		if ( !$this->_name ) {
			wp_die( 'Error: the $_name variable must be specified to use subclasses of PW_Model. It should be the same as the option name in the options table.' );
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
			if ($key != 'auto_id') {
				$option[$key] = wp_parse_args( $instance, $defaults[0] );
			}
		}
		return $option;
	}
	
	
}
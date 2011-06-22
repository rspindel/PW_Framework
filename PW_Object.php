<?php
/**
 * PW_Object
 *
 * The base class for all PW_Framework object
 * Defines the common methods of all PW_Framework objects such as magic methods
 *
 * @package PW_Framework
 * @since 0.1
 */

class PW_Object
{
	/**
	 * PHP getter magic method.
	 * This method is overridden so that object properties can be directly accessed
	 * To override and create your own getter method, create a method called "get_" . {variable name}
	 * @param string $name The key in the option array
	 * @return mixed property value
	 * @see getAttribute
	 */
	public function __get($name)
	{			
		if ( method_exists($this, 'get_' . $name) ) {
			return $this->{'get_' . $name}();
		}
		
		if ( property_exists($this, "_" . $name) ) {
			return $this->{"_" . $name};
		}
	}


	/**
	 * PHP setter magic method.
	 * This method is overridden so that object properties can be directly accessed
	 * To override and create your own setter method, create a method called "set_" . {variable name}
	 * @param string $name The key in the option array
	 * @param mixed $value The value to set
	 * @since 0.1
	 */
	public function __set( $name, $value )
	{
		// Throw an error is a script is trying to access a read-only property
		if ( in_array($name, $this->readonly() ) ) {
			$backtrace = debug_backtrace();
			wp_die( '<strong>Error:</strong> ' . get_class($backtrace[0]['object']) . '::' . $name . ' is read-only. <br /><strong>' . $backtrace[0]['file'] . '</strong> on line <strong>' . $backtrace[0]['line'] . '</strong>' );
			exit();
		}
		
		if ( method_exists($this, 'set_' . $name) ) {
			return $this->{'set_' . $name}($value);
		}
		
		if ( property_exists($this, "_" . $name) ) {
			$this->{"_" . $name} = $value;
		}
	}


	/**
	 * List any properties that should be readonly
	 * Call array_merge() with parent::readonly() when subclassing to add more values
	 * @return array A list of properties the magic method __set() can't access
	 * @since 0.1
	 */
	protected function readonly()
	{ 
		return array();
	}
}
<?php
/**
 * PW_Object
 *
 * The base class for all PW_Framework object
 * Defines the common methods of all PW_Framework objects such as magic methods
 *
 * @package PW_Framework
 * @since 1.0
 */

class PW_Object
{
	/**
	 * PHP getter magic method.
	 * This method is overridden so that object properties can be directly accessed
	 * @param string $name The key in the option array
	 * @return mixed property value
	 * @see getAttribute
	 */
	public function __get($name)
	{	
		if ( isset($this->{"_" . $name}) ) {
			return $this->{"_" . $name};
		}
	}


	/**
	 * PHP setter magic method.
	 * This method is overridden so that object properties can be directly accessed
	 * @param string $name The key in the option array
	 * @param mixed $value The value to set
	 * @since 1.0
	 */
	public function __set( $name, $value )
	{
		// Throw an error is a script is trying to access a read-only property
		if ( in_array($name, $this->readonly() ) ) {
			$backtrace = debug_backtrace();
			wp_die( '<strong>Error:</strong> ' . get_class($backtrace[0]['object']) . '::' . $name . ' is read-only. <br /><strong>' . $backtrace[0]['file'] . '</strong> on line <strong>' . $backtrace[0]['line'] . '</strong>' );
			exit();
		}
	
		if ( isset($this->{"_" . $name}) ) {
			$this->{"_" . $name} = $value;
		}
	}


	/**
	 * List any properties that should be readonly
	 * Call array_merge() with parent::readonly() when subclassing to add more values
	 * @return array A list of properties the magic method __set() can't access
	 * @since 1.0
	 */
	protected function readonly()
	{ 
		return array();
	}
}
<?php
/**
 * PW_Validator
 *
 * A collection of predefined, static validation methods
 *
 * @package PW_Framework
 * @since 1.0
 */


class PW_Validator
{
	/**
	 * Returns an error message if email is invalid, otherwise returns nothing
	 * @param sring $input The value to validate
	 * @param array $args Optional values specified in the rules() declaration
	 * @return string (only if invalid)
	 * @since 1.0
	 */
	public static function email( $input, $args = null )
	{
		/**
		 * The regular expression used to validate the attribute value.
		 * @see http://www.regular-expressions.info/email.html
		 */
		$pattern='/^[a-zA-Z0-9!#$%&\'*+\\/=?^_`{|}~-]+(?:\.[a-zA-Z0-9!#$%&\'*+\\/=?^_`{|}~-]+)*@(?:[a-zA-Z0-9](?:[a-zA-Z0-9-]*[a-zA-Z0-9])?\.)+[a-zA-Z0-9](?:[a-zA-Z0-9-]*[a-zA-Z0-9])?$/';
		if ( !preg_match($pattern, $input) ) {
			return "{property} must be a valid e-mail address.";
		}
	}
	
	/**
	 * Returns an error message if $input doesn't match the passed regular expression
	 * 
	 * @param sring $input The value to validate
	 * @return string (only if invalid)
	 * @since 1.0
	 */
	public static function match( $input, $args = null )
	{
		if ( isset($args['pattern']) && !preg_match($args['pattern'], $input) ) {
			return 'Invalid value for {property}';
		}
	}
	
	/**
	 * Returns an error message if $input is empty, otherwise returns nothing
	 * @param sring $input The value to validate
	 * @return string (only if invalid)
	 * @since 1.0
	 */
	public static function required( $input, $args = null  )
	{
		if ( $input === '' || $input === null ) {
			return "The '{property}' field is required.";
		}
	}	
}
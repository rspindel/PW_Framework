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
	 * Returns an error message if $input is empty, otherwise returns nothing
	 * @param sring $input The value to validate
	 * @return string (only if invalid)
	 * @since 1.0
	 */
	public static function required( $input ) {
		if ( !$input ) {
			return "The {attribute} field is required.";
		}
	}
	
	/**
	 * Returns an error message if email is invalid, otherwise returns nothing
	 * @param sring $input The value to validate
	 * @return string (only if invalid)
	 * @since 1.0
	 */
	public static function email( $input ) {
		/**
		 * The regular expression used to validate the attribute value.
		 * @see http://www.regular-expressions.info/email.html
		 */
		$pattern='/^[a-zA-Z0-9!#$%&\'*+\\/=?^_`{|}~-]+(?:\.[a-zA-Z0-9!#$%&\'*+\\/=?^_`{|}~-]+)*@(?:[a-zA-Z0-9](?:[a-zA-Z0-9-]*[a-zA-Z0-9])?\.)+[a-zA-Z0-9](?:[a-zA-Z0-9-]*[a-zA-Z0-9])?$/';
		if ( !preg_match($pattern, $input) ) {
			return "Please enter a valid e-mail address.";
		}
	}
	
}
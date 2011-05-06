<?php
/*
Plugin Name: PW_Zen_Coder
Plugin URI: http://philipwalton.com/2011/04/06/zen-coding-for-php-and-wordpress/
Description:
Version: 0.1
Author: Philip Walton
Author URI: http://philipwalton.com
*/


class ZC
{	
	/**
	 * Expand a css-style selector according to the 'Zen Coding' specifications
	 * @link http://code.google.com/p/zen-coding/
	 * @uses this->tag()
	 *
	 * @param string $selector a css-style selector based on the Zen Coding' specifications
	 * @param string|array $innerHTML the contents for the last element in the selector string,
	 * if this is an array and it's part of an iteration loop, the innerHTML is assigned by index
	 * @return string the entire HTML element
	 */
	public function r($selector = '', $text = null)
	{	
		// retrieve the passed arguments
		$args = func_get_args();

		// if the selector is null, return the content (the last $arg) and don't call recursively
		if ($selector === null) {
			return end($args) ? end($args) : null;
		}

		// break the selector into its root element and its children
		if ( strpos( $selector, '>' ) !== false ) {
			$root = substr( $selector, 0, strpos( $selector, '>' ) );
			$children = substr($selector, strpos( $selector, '>' ) + 1 );
		} else {
			$root = $selector;
			$children = null;
		}
		
		// set the $children selector as the first argument (in order to call recursively)
		$args[0] = $children;
		
		// if there is a root selector to search through, check it classes, id, atts, etc.
		if ($root) {		
			// set the defaults
			$atts = array();
			$arg_index = null;
			$HTML = '';
			
			// a list of characters to not match (because they identify classes, ids, atts, etc.)
			$i = '{#\*\.\['; 
			
			// if there is an attribute specified, capture it and remove it from the slector
			// (because attributes can contain the dot and hash characters, which will mess up later matches)
			if ( preg_match("/\[.*\]/", $root, $att) ) {
				$root = preg_replace('/\[.*\]/', '', $root);

				// separate the $att variable into its property and value
				if ( preg_match('/([\w-]+)=[\'\"]?([^\'\"\]]*)[\'\"]?/', $att[0], $att_selector) )
					$atts[$att_selector[1]] = $att_selector[2];
			}
				
			// get the tag name, default to 'div'
			$name = preg_match("/^[^$i]+/", $root, $tag) ? $tag[0] : 'div';

			// if there is an ID specified
			if ( preg_match("/#[^$i]+/", $root, $id) ) {
				$atts['id'] = substr($id[0], 1);
			}
			
			// if there is a class specified
			if ( preg_match("/\.[^\[{#\*]+/", $root, $class) ) {
				$atts['class'] = str_replace('.', ' ', substr($class[0], 1));
			}
			
			// if there is an iteration number specified
			if ( preg_match("/\*\d+/", $root, $count) ) {
				$iterations = (int) substr($count[0], 1);
			}
			
			// if there is a passed value, get its index in the argument list
			if ( preg_match("/{%\d+}/", $root, $index) ) {
				$arg_index = (int) preg_replace('/\D/', '', $index[0]);
				
				// make sure the are the appropriate number of arguments, otherwise return an error
				if ( count($args) - 1 <= $arg_index ) {
					return "Syntax Error: Incorrect number of arguments";
				}
				
			}
			
			// if there are iterations for this element, go through each of them, otherwise just create the element
			if ( isset($iterations) ) { 
				
				for ( $i = 0; $i < $iterations; $i++ )
				{
					// flatten the arguments for this iteration
					$iteration_args = self::flatten_args( $args, $i );
					
					// get the attributes for this iteration (merging with existing atts if needed)
					$iteration_atts = $arg_index ? array_merge( $atts, $iteration_args[$arg_index] ) : $atts;
										
					// check the attributes for the '$' character and replace accordingly
					foreach($iteration_atts as $key=>$value) {
						if ( preg_match( '/\$+/', $value, $dollars ) ) {
							$index_formatted = str_pad($i+1, strlen($dollars[0]), '0', STR_PAD_LEFT);
							$iteration_atts[$key] = str_replace( $dollars[0], $index_formatted, $value);
						}	
					}
					
					// create the element for this iteration, nest it with any children elements
					$HTML .= PW_HTML::tag(
						$name,
						call_user_func_array( array('ZC', 'r'), $iteration_args ),				
						$iteration_atts
					);
				}
			} else {
				$HTML .= PW_HTML::tag(
					$name,
					call_user_func_array( array('ZC', 'r'), $args ),				
					$arg_index ? array_merge( $atts, $args[$arg_index] ) : $atts
				);
			}
			return $HTML;			
		}

	}
	
	/**
	 * Calls self::r() and echos the result instead of returning it
	 */
	public static function e() {
		echo call_user_func_array( array('ZC', 'r'), func_get_args() );
	}


	/**
	 * Consumes a list of arrays and an index and returns a new list of but where
	 * every array in the list has been reduced to only the value at the passed index
	 *
	 * @param array $args a list of arguments
	 * @param int $index the index to flatten to
	 * @return array the flattened list of arguments
	 */
	protected function flatten_args( $args = array(), $index = 0 )
	{
		// loop through the existing $args and get their index
		$flattened_args = array();
		foreach ($args as $arg) 
		{	
			// if $arg is not an array, store it and move on
			if ( !is_array($arg) ) {
				$flattened_args[] = $arg; continue;
			}
			
			// check to see if the first key of the array is a string, which means it's not a list of arrays
			if ( is_string( key($arg) ) ) {
				$flattened_args[] = $arg; continue;
			}
			
			// still here? store the array at this iteration's index, or use the last index if none exists
			$flattened_args[] = isset( $arg[$index] ) ? $arg[$index] : end($arg);
		}
		return $flattened_args;
	}
		
} // Class PW_Zen_Coder
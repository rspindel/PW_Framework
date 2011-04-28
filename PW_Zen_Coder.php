<?php
/*
Plugin Name: PW_Zen_Coder
Plugin URI: http://philipwalton.com
Description:
Version: 1.0
Author: Philip Walton
Author URI: http://philipwalton.com
*/


class PW_Zen_Coder
{	
	/**
	 * Expand a css-style selector according to the 'Zen Coding' specifications
	 * @link http://code.google.com/p/zen-coding/
	 * @uses PW_HTML::tag()
	 *
	 * @param string $selector a css-style selector based on the Zen Coding' specifications
	 * @param string|array $innerHTML the contents for the last element in the selector string,
	 * if this is an array and it's part of an iteration loop, the innerHTML is assigned by index
	 * @return string the entire HTML element
	 */
	public function expand($selector = '', $text = null) {
		
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
		
		// build the regular expression that parses the selector
		$regex = '/';
		$regex .= '([^\[{%#*\.]+)?'; // optionally find the tag name
		$regex .= '(#[^\[{%*\.]+)?'; // next see if there is an id
		$regex .= '(\.[^\[{%*]+)?'; // see if there are classes
		$regex .= '(\[[^\[{%*]+\])?'; // see if there is an attributes
		$regex .= '(\*\d+)?'; // see if there should be more than 1 iteration
		$regex .= '({%\d+})?'; // see if there is a passed value
		$regex .= '/';

		if ($root && preg_match($regex, $root, $matches)) {
				
			// set the defaults
			$atts = array();
			$arg_index = null;
			$HTML = '';
				
			// get the tag name, default to 'div'
			$name = isset($matches[1]) && $matches[1] ? $matches[1] : 'div';
			
			// if there is an ID specified
			if (isset($matches[2]) && $matches[2]) {
				$atts['id'] = substr($matches[2], 1);
			}
				
			// if there is a class specified
			if (isset($matches[3]) && $matches[3]) {
				$atts['class'] = str_replace('.', ' ', substr($matches[3], 1));
			}
			
			// if there is an attribute specified
			if (isset($matches[4]) && $matches[4]) {
				if ( preg_match('/([\w-]+)=([\w-]+)/', $matches[4], $att_selector) ) {
					$atts[$att_selector[1]] = $att_selector[2];
				}
			}
			
			// if there is an iteration number specified
			if (isset($matches[5]) && $matches[5]) {
				$iterations = (int) substr($matches[5], 1);
			}
			
			// if there is a passed value, get its index in the argument list
			if (isset($matches[6]) && $matches[6]) {
				// get the index passed in $matches[5], ex: {%2} corresponds to $args[2]
				$arg_index = (int) preg_replace('/\D/', '', $matches[6]);
			}
			
			// if there are iterations for this element, go through each of them, otherwise just create the element
			if ( isset($iterations) ) { 
				for ( $i = 0; $i < $iterations; $i++ )
				{
					// flatten the arguments for this iteration
					$iteration_args = $this->flatten_args( $args, $i );
					
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
						call_user_func_array( array($this, 'expand'), $iteration_args ),				
						$iteration_atts
					);
				}
			} else {
				$HTML .= PW_HTML::tag(
					$name,
					call_user_func_array( array($this, 'expand'), $args ),				
					$arg_index ? array_merge( $atts, $args[$arg_index] ) : $atts
				);
			}
			return $HTML;			
		}

	}

	
	/**
	 * Consumes a list of args and an index and returns a new list of args but where
	 * every array in the list has been reduced to only the array at the passed index
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
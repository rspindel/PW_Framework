<?php
/**
 * PW_HTML
 *
 * An HTML helper class for programmatically creating markup
 *
 * @package PW_Framework
 * @since 1.0
 */

class PW_HTML
{	
	/**
	 * Converts a name attribute (ex: Post[author][name]) into a unique ID
	 * @param string $name the name attribute for a form field
	 * @return string the generated id attribute value
	 * @since 1.0
	 */
	public static function get_id_from_name($name)
	{
		return str_replace(array('[]', '][', '[', ']'), array('', '_', '_', ''), $name);
	}
	
	
	/**
	 * Creates and returns the output for an HTML element
	 * @param string $name The tag name of the HTML element (ex:div for <div>)
	 * @param string $text The HTML markup placed between the tags. Pass '' to produce <p></p> and null to produce <p />
	 * @param array $atts An optional array (keyed: property=>value) of additional HTML attributes
	 * @return string The generated HTML markup
	 * @since 1.0
	 */
	public static function tag($name, $text='', $atts=array())
	{		
		// escape the tag name
		$name = tag_escape($name);
		
		$output = "<$name";
		
		if ( is_array($atts) ) {
			foreach( $atts as $property=>$value)
			{
				// convert all attribute names to lowecase and remove non-letter characters
				$property = preg_replace('/[^a-z]/', '', strtolower($property));
			
				// esc_url if the attribute type is: cite, codebase, href, src
				if (in_array($property, array('cite', 'codebase', 'href', 'src'))) {
					$value = esc_url($value);
				}
							
				// escape the attribute values
				$value = esc_attr($value);
			
				$output .= " {$property}=\"{$value}\"";
			}
		}
		$output .= ($text !== null) ? ">$text</$name>" : " />";
		return $output;
	}

	
	/**
	 * Creates and returns the output for a checkbox.
	 * Optionally creates a hidden input field which represents the default if the box is left unchecked
	 * @param string $name The name attribute	
	 * @param bool $selected Whether or not the checkbox should be selected
	 * @param array $atts @see self::tag() for details
	 * @param string $unchecked_value An optional default value in case the box is left unchecked
	 * @return string The generated HTML markup
	 * @since 1.0
	 */
	public static function checkbox( $name, $selected, $atts=array(), $unchecked_value=null )
	{	
		$atts['name'] = $name;
		$atts['id'] = self::get_id_from_name($name);
		$atts['type'] = 'checkbox';
		$atts['value'] = isset($atts['value']) ? $atts['value'] : "1";
		if ($selected) {
			$atts['checked'] = 'checked';
		}
		$output = $unchecked_value ? self::tag('input', null, array('type'=>'hidden', 'value'=>$unchecked_value, 'name'=>$name)) : "";
		$output .= self::tag('input', null, $atts);
		return $output;
	}
	
	
	/**
	 * Creates and returns a list of checkbox elements
	 * @param string $name The name attribute
	 * @param array $items A list of value=>label pairs representing each individual radio button
	 * @param mixed $selected The value of the selected checkbox. Can be a string or an array.
	 * @param string $separator Optional markup to put between each radio button
	 * @param array $atts @see self::tag() for details
	 * @param string $template Indicates how each radio button and label will be displayed (use: {input} and {label})
	 * @param array $label_atts similar to $atts, but applied to the label element
	 * @return string The generated HTML markup
	 * @since 1.0
	 */
	public static function checkbox_list($name, $items, $selected, $separator = '', $atts=array(), $template='{input}{label}', $label_atts=array() )
	{
		// add '[]' to the end of the name if it's not already there (to collect an array of values)
		if ( substr($name, -2) !== '[]' ) {
			$name .= '[]';
		}
		
		$atts['name'] = $name;
		$atts['type'] = 'checkbox';
		$item_index = 0;
		foreach($items as $item_value=>$item_label)
		{
			$item_atts = $atts;
			$item_atts['id'] = $label_atts['for'] = self::get_id_from_name($name) . '_' . $item_index++;
			$item_atts['value'] = $item_value;
			if ( in_array($item_value, (array) $selected) ) {				
				$item_atts['checked'] = 'checked';
			}
			$temp_items[] = str_replace(
				array( '{input}', '{label}' ),
				array( self::tag('input', null, $item_atts), self::tag('label', $item_label, $label_atts ) ),
				$template
			);
		}
		return implode($temp_items, $separator);
	}


	/**
	 * Creates and returns the output for an html label
	 * @param string $text @see self::tag() for details
	 * @param string $for The for attribute
	 * @param array $atts @see self::tag() for details
	 * @return string The generated HTML markup
	 * @since 1.0
	 */
	public static function label($text = '', $for = null, $atts = array())
	{
		if ($for) {
			$atts['for'] = $for;
		}
		return self::tag('label', $text, $atts);
	}
	
	
	/**
	 * Creates and returns the output for an html link (<a> tag)
	 * @param string $href The URI to link to
	 * @param string $text @see self::tag() for details
	 * @param array $atts @see self::tag() for details
	 * @return string The generated HTML markup
	 * @since 1.0
	 */
	public static function link($href = '', $text = '', $atts = array())
	{
		$atts['href'] = $href;
		return self::tag('a', $text, $atts);
	}
	
	
	/**
	 * Creates and returns the output for an input element of type = "password"
	 * @param string $name The name attribute
	 * @param array $atts @see self::tag() for details
	 * @return string The generated HTML markup
	 * @since 1.0
	 */
	public static function password( $name = '', $atts = array() )
	{
		$atts['type'] = 'password';
		$atts['name'] = $name;
		$atts['id'] = self::get_id_from_name($name);
		return self::tag('input', null, $atts);
	}
	
	
	/**
	 * Creates and returns a list of radio button elements
	 * @param string $name The name attribute
	 * @param array $items A list of value=>label pairs representing each individual radio button
	 * @param string $selected The value of the selected radio button (checked against value in items array)
	 * @param string $separator Optional markup to put between each radio button
	 * @param array $atts @see self::tag() for details
	 * @param string $template Indicates how each radio button and label will be displayed (use: {input} and {label})
	 * @param array $label_atts similar to $atts, but applied to the label element
	 * @return string The generated HTML markup
	 * @since 1.0
	 */
	public static function radio_button_list($name, $items, $selected, $separator = '', $atts = array(), $template = '{input}{label}', $label_atts = array() )
	{
		$atts['name'] = $name;
		$atts['type'] = 'radio';
		$item_index = 0;
				
		foreach($items as $item_value=>$item_label)
		{
			$item_atts = $atts;
			$item_atts['id'] = $label_atts['for'] = self::get_id_from_name($name) . '_' . $item_index++;
			$item_atts['value'] = $item_value;
			
			if ( (string) $selected === (string) $item_value ) {	
				$item_atts['checked'] = 'checked';
			}
			$temp_items[] = str_replace(
				array( '{input}', '{label}' ),
				array( self::tag('input', null, $item_atts), self::tag('label', $item_label, $label_atts ) ),
				$template
			);
		}
		return implode($temp_items, $separator);
	}
	
	
	/**
	 * Creates and returns a select dropdown
	 * @param string $name The name attribute
	 * @param array $items A list of value=>label pairs representing each individual option elements
	 * @param string $selected The value of the selected option (checked against value in items array)
	 * @param array $atts @see self::tag() for details
	 * @return string The generated HTML markup
	 * @since 1.0
	 */	
	public static function select($name, $items, $selected, $atts = array())
	{
		$atts['name'] = $name;
		$atts['id'] = self::get_id_from_name($name);
		$text = '';
		foreach($items as $item_value=>$item_text) {
			$item_atts = array('value'=>$item_value);
			if ( (string) $selected === (string) $item_value ) {
				$item_atts['selected'] = 'selected';
			}
			$text .= self::tag('option', $item_text, $item_atts);
		}
		return self::tag('select', $text, $atts);
	}
	
	
	/**
	 * Creates and returns the output for an input element of type="text"
	 * @param string $name The name attribute
	 * @param string $text The default text content
	 * @param array $atts @see self::tag() for details
	 * @return string The generated HTML markup
	 * @since 1.0
	 */
	public static function textfield( $name = '', $text = '', $atts=array() )
	{
		$atts['type'] = 'text';
		$atts['name'] = $name;
		$atts['value'] = $text;
		$atts['id'] = self::get_id_from_name($name);
		return self::tag('input', null, $atts);
	}

} // Class PW_HTML
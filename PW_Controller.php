<?php
/**
 * PW_Controller
 *
 * The base controller class for the PW_Framework
 *
 * This class primarily does these things:
 * - Handle adding scripts and styles to public vs admin pages
 * - Creates a model object and collects form input data to write back to it
 *
 * @package PW_Framework
 * @since 0.1
 */


class PW_Controller extends PW_Object
{	
	/**
	 * @var string The file path of this plugin's main script
	 * @since 0.1
	 */
	protected $_plugin_file;


	/**
	 * An array of scripts that will be enqueued.
	 * Scripts in this array are always loaded, regardless of what page your on (admin or public).
	 * Only add scripts to this array after you've conditionally checked to ensure that they should
	 * be loaded on the current page.
	 * @var array A list of arrays in the form array( $handle, $src, $deps, $ver, $in_footer ) {@link http://codex.wordpress.org/Function_Reference/wp_enqueue_script}
	 * @since 0.1
	 */
	protected $_scripts = array();


	/**
	 * An array of styles that will be enqueued.
	 * Styles in this array are always loaded, regardless of what page your on (admin or public).
	 * Only add styles to this array after you've conditionally checked to ensure that they should
	 * be loaded on the current page.
	 * @var array A list of arrays in the form array( $handle, $src, $deps, $ver, $media) {@link http://codex.wordpress.org/Function_Reference/wp_enqueue_style}
	 * @since 0.1
	 */
	protected $_styles = array();


	/**
	 * @var string The currently loaded view file.
	 * @since 0.1
	 */
	protected $_view;

	
	/**
	 * The controller constructor.
	 * @param string $plugin_file The plugin's main php file
	 * @since 0.1
	 */
	public function __construct()
	{					
		// add action hook for public and/or admin pages
		if ( is_admin() ) {
			add_action( 'admin_init', array($this, 'on_admin_page') );
		} else {
			add_action( 'init', array($this, 'on_public_page') );	
		}
	}
	

	/**
	 * This method is called from the init hook on any admin page
	 * @since 0.1
	 */
	public function on_public_page()
	{
		// add the hooks to enqueue the appropriate public scripts and styles
		add_action( 'wp_enqueue_scripts', array($this, 'enqueue_scripts') );
		add_action( 'wp_print_styles', array($this, 'print_styles') );
	}


	/**
	 * This method is called from the admin_init hook on any admin page
	 * @since 0.1
	 */
	public function on_admin_page()
	{
		add_action( 'admin_enqueue_scripts', array($this, 'enqueue_scripts') );
		add_action( 'admin_print_styles', array($this, 'print_styles') );
		
		// Register the admin_notices hook which calls PW_Alerts
		add_action( 'admin_notices', array('PW_Alerts', 'render') );		
	}
	
	
	/**
	 * Renders a view file.
	 * This method includes the view file as a PHP script
	 * and captures the display result if required.
	 * @param array $vars An array variables to extract and pass to the view file
	 * @param string $file The view file (defaults to this controller's view)
	 * @param boolean $output Set as false to return as a string
	 * @since 0.1
	 */
	public function render( $vars = array(), $file = null, $output = true )
	{
		// extract $vars so they can be used in the view file
		extract( $vars );
		
		if ( is_file($file) ) {
			if ( $output) {
				require($file);
			} else {
				ob_start();
				ob_implicit_flush(false);
				require($file);
				return ob_get_clean();
			}
		}
	}
	
	
	/**
	 * Enqueues all the scripts in $this->_scripts.
	 * Called from either the wp_enqueue_scripts or admin_enqueue_scripts hook
	 * @since 0.1
	 */
	public function enqueue_scripts()
	{
		foreach ($this->_scripts as $script)
		{
			call_user_func_array( 'wp_enqueue_script', $script );
		}
	}
	
	
	/**
	 * Prints all the styles in $this->_styles.
	 * Called from either the wp_print_styles or admin_print_styles hook
	 * @since 0.1
	 */
	public function print_styles()
	{
		foreach ($this->_styles as $style)
		{
			call_user_func_array( 'wp_enqueue_style', $style );
		}
	}
}
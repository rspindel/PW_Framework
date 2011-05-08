<?php
/**
 * PW_Controller
 *
 * The base controller class to facilitate interaction between a model and views
 *
 * This class primarily does these things:
 * 1) Adds the appropriate hooks to display an options page
 * 2) Creates a model object and collects form input data to write back to it
 *
 * @package PW_Framework
 * @since 1.0
 */


class PW_Controller extends PW_Object
{
	/**
	 * @var string The admin page where the settings form will be rendered
	 * @since 1.0
	 */
	protected $_admin_page = 'options-general.php';

	/**
	 * @var PW_Model the currently loaded model instance.
	 * @since 1.0
	 */
	protected $_model;
	
	/**
	 * @var string The file path of this plugin's main script
	 * @since 1.0
	 */
	protected $_plugin_file;

	/**
	 * @var array The submenu page data
	 * @since 1.0
	 */
	protected $_submenu;	

	/**
	 * @var PW_View the currently loaded view instance.
	 * @since 1.0
	 */
	protected $_view;
	
	/**
	 * @var array An array of additional data you want to pass to the view
	 * @since 1.0
	 */
	protected $_view_data;
	
	/**
	 * The controller constructor.
	 * @param string $plugin_file The plugin's main php file
	 * @since 1.0
	 */
	public function __construct( $plugin_file )
	{			
		$this->_plugin_file = plugin_basename($plugin_file);
		
		// add action hook for admin pages
		add_action( 'admin_init', array($this, 'on_admin_page') );
		
		// add action hook for public pages
		if ( !is_admin() ) {
			add_action( 'init', array($this, 'on_public_page') );
		}
	}
	
	/**
	 * @see parent
	 * @since 1.0
	 */
	public function __set( $name, $value )
	{
		// If we're setting the model, make sure to set $this as the controller in the passed model object
		if ( 'model' == $name ) {
			$this->_model = $value;
			$value->controller = $this;
		} else {
			parent::__set( $name, $value );
		}
	}

	/**
	 * This method is called from the init hook on any admin page
	 * @since 1.0
	 */
	public function on_public_page()
	{
		$this->enqueue_scripts_and_styles( $this->public_scripts(), $this->public_styles() );
	}


	/**
	 * This method is called from the admin_init hook on any admin page
	 * @since 1.0
	 */
	public function on_admin_page()
	{
		$this->enqueue_scripts_and_styles( $this->admin_scripts(), $this->admin_styles() );
	}
	
	/**
	 * This method is only called if you've added a settings page via create_settings_page()
	 * and the user is currently on that page.
	 * @since 1.0
	 */
	public function on_settings_page()
	{
		$this->enqueue_scripts_and_styles( $this->settings_scripts(), $this->settings_styles() );
	}
	

	/**
	 * Creates an options page for this controller's model, this is the callback
	 * from the 'admin_menu' hook set in self::add_options_page()
	 * @param string $title The text for the page title and the menu link, defaults to the model title
	 * @param func $view_callback A callback function to render the form output
	 * @param string $filename The file name of a standard WordPress admin page
	 * @param string $capability The capability required for this menu to be displayed to the user.
	 * @param string $menu_slug The slug name to refer to this menu by (should be unique)  
	 * @since 1.0
	 */
	public function create_settings_page( $title = null, $page = null, $capability = 'manage_options' ) 
	{				
		$this->_submenu = array(
			'title' => $title ? $title : $this->_model->title,
			'page' => $page ? $page : $this->_admin_page,
			'capability' => $capability,
		);
		add_action( 'admin_menu', array($this, 'add_settings_page') );
		
		// add a filter that adds a "settings" link when viewing this plugin in the plugins list
		add_filter( 'plugin_action_links_' . $this->_plugin_file , array($this, 'add_settings_link' ) );		
	}
	
	
	/**
	 * Creates an options page for this controller's model using $this->_submnu
	 * this is the callback from the 'admin_menu' hook set in self::add_options_page() 
	 * @since 1.0
	 */
	public function add_settings_page()
	{
		extract( $this->_submenu );
		
		// add the settings page and store it in a variable
		$settings_page = add_submenu_page( $page, $title, $title, $capability, $this->_plugin_file, array($this, 'render_settings_page') );
				
		// add a hook to run only when we're on the settings page
		add_action( 'load-' . $settings_page, array($this, 'on_settings_page') );
		
	}
	
	/**
	 * Add settings link on plugin page. Called from add_filter('plugin_action_links_[...]') in self::create_settings_page()
	 * @return array The new list of links for the plugin on the plugins list page
	 * @since 1.0
	 */
	public function add_settings_link( $links )
	{
		$settings_link = '<a href="options-general.php?page=' . $this->_plugin_file .'">Settings</a>';
		array_unshift($links, $settings_link); 
		return $links; 
	}
		
	/**
	 * This default callback for add_submenu_page()
	 * Override this function in a subclass to change the default rendering functionality
	 * @since 1.0
	 */
	public function render_settings_page()
	{
		$file = $this->_view;
		
		// create some vars to pass to the view
		$vars = $this->_model ? array('model' => $this->_model ) : array();
		$vars['admin_page'] = $this->_admin_page;
		$vars['plugin_file'] = $this->_plugin_file;
		
		$this->render( $vars, $file );
	}
	
	
	/**
	 * Renders a view file.
	 * This method includes the view file as a PHP script
	 * and captures the display result if required.
	 * @param string $file The view file (defaults to this controller's view)
	 * @param array $vars An array variables to extract and pass to the view file
	 * @param boolean $output Set as false to return as a string
	 * @since 1.0
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
	 * Override to specify any styles that should appear on all public pages when this plugin is active
	 * @return array A list of arrays in the form array( $handle, $src, $deps, $ver, $media) {@link http://codex.wordpress.org/Function_Reference/wp_enqueue_style}
	 * @since 1.0
	 */
	protected function public_styles() {
		return array();
	}
	
	/**
	 * Override to specify any styles that should appear on all admin pages when this plugin is active
	 * @return array A list of arrays in the form array( $handle, $src, $deps, $ver, $media ) {@link http://codex.wordpress.org/Function_Reference/wp_enqueue_style}
	 * @since 1.0
	 */
	protected function admin_styles() {
		return array();
	}
	
	/**
	 * Override to specify any styles that should appear only on this plugin's settings page.
	 * defaults to the pw-form stylesheet
	 * @return array A list of arrays in the form array( $handle, $src, $deps, $ver, $media ) {@link http://codex.wordpress.org/Function_Reference/wp_enqueue_style}
	 * @since 1.0
	 */
	protected function settings_styles() {
		return array(
			array( 'pw-form', PW_FRAMEWORK_URL . '/css/pw-form.css' ),
		);
	}
	
	/**
	 * Override to specify any scripts that should be loaded on all public pages when this plugin is active
	 * @return array A list of arrays in the form array( $handle, $src, $deps, $ver, $in_footer ) {@link http://codex.wordpress.org/Function_Reference/wp_enqueue_script}
	 * @since 1.0
	 */
	protected function public_scripts() {
		return array();
	}
	
	/**
	 * Override to specify any scripts that should be loaded on all admin pages when this plugin is active
	 * @return array A list of arrays in the form array( $handle, $src, $deps, $ver, $in_footer ) {@link http://codex.wordpress.org/Function_Reference/wp_enqueue_script}
	 * @since 1.0
	 */
	protected function admin_scripts() {
		return array();
	}
	
	/**
	 * Override to specify any scripts that should be loaded only on this plugin's settings page.
	 * @return array A list of arrays in the form array( $handle, $src, $deps, $ver, $in_footer ) {@link http://codex.wordpress.org/Function_Reference/wp_enqueue_script}
	 * @since 1.0
	 */
	protected function settings_scripts() {
		return array();
	}
	
	/**
	 * Enqueues an array of scripts and styles
	 * @param array $scripts The scripts to enqueue
	 * @param array $styles The stylesheets to enqueue
	 * @since 1.0
	 */
	protected function enqueue_scripts_and_styles( $scripts = array(), $styles = array() )
	{
		foreach ($styles as $style)
		{
			call_user_func_array( 'wp_enqueue_style', $style );
		}
		
		$scripts = $this->settings_scripts();
		foreach ($scripts as $script)
		{
			call_user_func_array( 'wp_enqueue_script', $script );
		}
	}
}
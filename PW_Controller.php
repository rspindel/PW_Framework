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
 * @since 0.1
 */


class PW_Controller extends PW_Object
{
	/**
	 * @var PW_Model the currently loaded model instance.
	 * @since 0.1
	 */
	protected $_model;
	
	/**
	 * @var string The file path of this plugin's main script
	 * @since 0.1
	 */
	protected $_plugin_file;

	/**
	 * @var array An array of scripts to be added in the appropriate hook
	 * This array should start every page load empty. Scripts should be added based on conditions
	 * @since 0.1
	 */
	protected $_scripts;

	/**
	 * An array of styles that will be enqueued.
	 * Styles in this array are always loaded, regardless of what page your on (admin or public).
	 * Only add styles to this array after you've conditionally checked to ensure that they should
	 * be loaded on the current page.
	 * @var array An array of styles
	 * @since 0.1
	 */
	protected $_styles;

	/**
	 * @var array The submenu page data
	 * @since 0.1
	 */
	protected $_submenu;	

	/**
	 * @var string The currently loaded view file.
	 * @since 0.1
	 */
	protected $_view;
	
	/**
	 * @var array An array of additional data you want to pass to the view
	 * @since 0.1
	 */
	protected $_view_data;
	
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
		
		// register the public scripts and styles
		$this->_styles = array_merge( (array) $this->_styles, $this->public_styles() );
		$this->_scripts = array_merge( (array) $this->_scripts, $this->public_scripts() );
	}


	/**
	 * This method is called from the admin_init hook on any admin page
	 * @since 0.1
	 */
	public function on_admin_page()
	{
		add_action( 'admin_enqueue_scripts', array($this, 'enqueue_scripts') );
		add_action( 'admin_print_styles', array($this, 'print_styles') );
		
		// Add the hook for ajax validation.
		// This needs to be here because the settings page isn't created on ajax requests.
		add_action( 'wp_ajax_pw-ajax-validate', array($this->_model, 'validate' ) );
		
		// Register the admin_notices hook which calls PW_Alerts
		add_action( 'admin_notices', array('PW_Alerts', 'render') );		
				
		// Register the admin scripts and styles
		$this->_styles = array_merge( (array) $this->_styles, $this->admin_styles() );
		$this->_scripts = array_merge( (array) $this->_scripts, $this->admin_scripts() );
	}
	
	/**
	 * This method is only called if you've added a settings page via create_settings_page()
	 * and the user is currently on that page.
	 * @since 0.1
	 */
	public function on_settings_page()
	{
		// register the settings page scripts and styles
		$this->_styles = array_merge( (array) $this->_styles, $this->settings_styles() );
		$this->_scripts = array_merge( (array) $this->_scripts, $this->settings_scripts() );
	}
	

	/**
	 * Creates the action hook necessary to add a settings submenu page
	 * @since 0.1
	 */
	public function create_settings_page() 
	{				
		add_action( 'admin_menu', array($this, 'add_submenu_page') );
		
		// add a filter that adds a "settings" link when viewing this plugin in the plugins list
		add_filter( 'plugin_action_links_' . $this->_plugin_file , array($this, 'add_settings_link' ) );		
	}

	
	/**
	 * Creates an options page for this controller's model using $this->_submnu
	 * this is the callback from the 'admin_menu' hook set in self::add_options_page() 
	 * @since 0.1
	 */
	public function add_submenu_page()
	{		
		// add the settings page and store it in a variable
		$submenu = add_submenu_page( $this->_model->admin_page, $this->_model->title, $this->_model->title, $this->_model->capability, $this->_model->name, array($this, 'render_submenu_page') );
		
		// add contextual help to the settings page if it's specified in the model
		if ($this->model->help) {
			add_contextual_help( $submenu, $this->model->help );
		}
		
		// add a hook to run only when we're on the settings page
		add_action( 'load-' . $submenu, array($this, 'on_settings_page') );
	}
	
	/**
	 * Add settings link on plugin page. Called from add_filter('plugin_action_links_[...]') in self::create_settings_page()
	 * @return array The new list of links for the plugin on the plugins list page
	 * @since 0.1
	 */
	public function add_settings_link( $links )
	{		
		$settings_link = '<a href="options-general.php?page=' . $this->_model->name .'">Settings</a>';
		array_unshift($links, $settings_link); 
		return $links; 
	}
		
	/**
	 * This default callback for add_submenu_page()
	 * Override this function in a subclass to change the default rendering functionality
	 * @since 0.1
	 */
	public function render_submenu_page()
	{
		$file = $this->_view;
		
		// pass the model as $model to the view
		// all array values are passed to the view as $key
		$vars = array('model' => $this->_model );
		
		$this->render( $vars, $file );
	}
	
	
	/**
	 * Renders a view file.
	 * This method includes the view file as a PHP script
	 * and captures the display result if required.
	 * @param string $file The view file (defaults to this controller's view)
	 * @param array $vars An array variables to extract and pass to the view file
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
	 * Override to specify any styles that should appear on all public pages when this plugin is active
	 * @return array A list of arrays in the form array( $handle, $src, $deps, $ver, $media) {@link http://codex.wordpress.org/Function_Reference/wp_enqueue_style}
	 * @since 0.1
	 */
	protected function public_styles() {
		return array();
	}
	
	/**
	 * Override to specify any styles that should appear on all admin pages when this plugin is active
	 * @return array A list of arrays in the form array( $handle, $src, $deps, $ver, $media ) {@link http://codex.wordpress.org/Function_Reference/wp_enqueue_style}
	 * @since 0.1
	 */
	protected function admin_styles() {
		return array();
	}
	
	/**
	 * Override to specify any styles that should appear only on this plugin's settings page.
	 * defaults to the pw-form stylesheet
	 * @return array A list of arrays in the form array( $handle, $src, $deps, $ver, $media ) {@link http://codex.wordpress.org/Function_Reference/wp_enqueue_style}
	 * @since 0.1
	 */
	protected function settings_styles() {
		return array(
			array( 'pw-form', PW_FRAMEWORK_URL . '/css/pw-form.css' ),
		);
	}
	
	/**
	 * Override to specify any scripts that should be loaded on all public pages when this plugin is active
	 * @return array A list of arrays in the form array( $handle, $src, $deps, $ver, $in_footer ) {@link http://codex.wordpress.org/Function_Reference/wp_enqueue_script}
	 * @since 0.1
	 */
	protected function public_scripts()
	{
		return array();
	}
	
	/**
	 * Override to specify any scripts that should be loaded on all admin pages when this plugin is active
	 * @return array A list of arrays in the form array( $handle, $src, $deps, $ver, $in_footer ) {@link http://codex.wordpress.org/Function_Reference/wp_enqueue_script}
	 * @since 0.1
	 */
	protected function admin_scripts()
	{
		return array();
	}
	
	/**
	 * Override to specify any scripts that should be loaded only on this plugin's settings page.
	 * @return array A list of arrays in the form array( $handle, $src, $deps, $ver, $in_footer ) {@link http://codex.wordpress.org/Function_Reference/wp_enqueue_script}
	 * @since 0.1
	 */
	protected function settings_scripts() 
	{
		return array(
			array( 'pw-ajax-validation', PW_FRAMEWORK_URL . '/js/ajax-validation.js', array('jquery','json2'), false, true ),			
		);
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
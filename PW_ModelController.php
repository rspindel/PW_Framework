<?php
/**
 * PW_ModelController
 *
 * The controller class to facilitate interaction between a model and views
 *
 * This class primarily does these things:
 * - Adds the appropriate hooks to display an options page for the model
 * - Handles the GET/POST requests and updates the model accordingly
 *
 * @package PW_Framework
 * @since 0.1
 */


class PW_ModelController extends PW_Controller
{
	/**
	 * @var PW_Model the currently loaded model instance.
	 * @since 0.1
	 */
	protected $_model;	


	/**
	 * The controller constructor.
	 * @param string $plugin_file The plugin's main php file
	 * @since 0.1
	 */
	public function __construct($model = null)
	{					
		parent::__construct();
		
		// Creates the action hook necessary to add a settings page
		add_action( 'admin_menu', array($this, 'add_settings_page') );
		
		// Add a filter that adds a "settings" link when viewing this plugin in the plugins list
		add_filter( 'plugin_action_links_' . $this->_plugin_file , array($this, 'add_settings_link' ) );
		
		// If a model object was passed, set it and call the process_request method which handles CRUD functionality
		if ($model) {
			$this->_model = $model;			
			$this->process_request();
		} 
	}
	
	
	/**
	 * Sets the model if it hasn't already been set
	 * Then call the process_request method which handles CRUD functionality
	 * @param PW_Model The model object for this controller
	 * @since 0.1
	 */
	public function set_model($model)
	{
		// 
		if (!$this->_model) {
			$this->_model = $model;			
			$this->process_request();
		} 
	}
	
	
	/**
	 * Detect any GET/POST variables and determine what CRUD option to do based on that
	 * @since 0.1
	 */
	public function process_request()
	{		
		// If the nonce checks out, validate and save any submitted data
		if ( isset($_POST[$this->_model->name]) && check_admin_referer( $this->_model->name . '-options' ) ) {
			
			// get the options from $_POST
			$this->_model->input = stripslashes_deep($_POST[$this->_model->name]);
			
			// save the options
			$this->_model->save($this->_model->input);
			
		}
	}
	

	/**
	 * Creates an options page for this controller's model using $this->_submnu
	 * this is the callback from the 'admin_menu' hook set in self::add_options_page() 
	 * @since 0.1
	 */
	public function add_settings_page()
	{		
		// add the settings page and store it in a variable
		$submenu = add_submenu_page( $this->_model->admin_page, $this->_model->title, $this->_model->title, $this->_model->capability, $this->_model->name, array($this, 'render_settings_page') );
		
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
	public function render_settings_page()
	{
		$file = $this->_view;
		
		// pass the model as $model to the view
		// all array values are passed to the view as $key
		$vars = array('model' => $this->_model );
		
		$this->render( $vars, $file );
	}

	
	/**
	 * This method is only called if you've added a settings page via create_settings_page()
	 * and the user is currently on that page.
	 * @since 0.1
	 */
	public function on_settings_page()
	{
		// register the settings page scripts and styles
		$this->_scripts[] = array( 'pw-ajax-validation', PW_FRAMEWORK_URL . '/js/ajax-validation.js', array('jquery','json2'), false, true );
		$this->_styles[] = array( 'pw-form', PW_FRAMEWORK_URL . '/css/pw-form.css' );
	}
	
	
////////////////////////////////////////////////////////////////////////////////////////////////////
// PW_Controller
////////////////////////////////////////////////////////////////////////////////////////////////////

	
	/**
	 * This method is called from the admin_init hook on any admin page
	 * @since 0.1
	 */
	public function on_admin_page()
	{
		parent::on_admin_page();
		
		// Add the hook for ajax validation.
		// This needs to be here because the settings page isn't created on ajax requests.
		add_action( 'wp_ajax_pw-ajax-validate', array($this->_model, 'validate' ) );
	}

}
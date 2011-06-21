<?php


class PW_Framework
{
	/**
	 * @var string This version of the framework.
	 * Used to only load the latest version if multiple versions are found
	 * @since 0.1
	 */
	public static $version = "0.1";
	
	/**
	 * @var array A list of framework files. Make sure each classname
	 * @since 0.1
	 */
	public static $files = array(
		'PW' => 'PW.php',
		'PW_Object' => 'PW_Object.php',
		'PW_Alerts' => 'PW_Alerts.php',
		'PW_Validator' => 'PW_Validator.php',
		'PW_HTML' => 'PW_HTML.php',
		'PW_Model' => 'PW_Model.php',
		'PW_Multi_Model' => 'PW_Multi_Model.php',
		'PW_Form' => 'PW_Form.php',
		'PW_Multi_Model_Form' => 'PW_Multi_Model_Form.php',
		'PW_Controller' => 'PW_Controller.php',
		'ZC' => 'PW_Zen_Coder.php',		
	);
	
	/**
	 * Define constants for framework directory and URL paths
	 * Also includes all the framework classes
	 * @since 0.1
	 */
	public static function init()
	{	
		// define the path and url of the PW_Framework
		define( 'PW_FRAMEWORK_DIR', dirname(__FILE__) );
		define( 'PW_FRAMEWORK_URL', WP_CONTENT_URL . str_replace( WP_CONTENT_DIR, '', dirname(__FILE__) ) );
	
		foreach( self::$files as $class=>$path) {
			if ( !class_exists($class) ) {
				require($path);
			}
		}
		do_action( 'pw_framework_loaded' );
	}
}
<?php

// register this version of the framework
$GLOBALS['pw_framework_meta']['1.0'] = __FILE__;


if ( !function_exists('pw_framework_init') ) :

	/**
	 * Sort the $GLOBALS['pw_framework_meta'] variable for the latest registered
	 * version of the framework. Include the PW_Framework.php file and then call init()
	 */
	function pw_framework_init()
	{
		// get all the different versions registered with $GLOBALS['pw_framework_meta']
		$versions = $GLOBALS['pw_framework_meta'];
	
		// sort the versions
		uksort($versions, 'version_compare');
	
		// get the latest versions (the last in the array)
		$latest = end($versions);
	
		if ( !class_exists('PW_Framework') ) {
			require_once( dirname($latest) . '/PW_Framework.php' );
		}
		PW_Framework::init();
	}
	
	// register pw_framework_init() with the 'after_setup_theme' hook
	// This way we can ensure that all plugins or themes that might be using PW_Framework
	// have had a chance to register with the $GLOBALS['pw_framework_meta'] variable
	add_action( 'after_setup_theme ', 'pw_framework_init' );
	
endif;
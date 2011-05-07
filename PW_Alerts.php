<?php
/**
 * PW_Alerts
 *
 * An alert manager class.
 *
 * This class primarily does these things:
 * 1) Stores alert messages (errors, updates, info)
 * 2) Processes and displays alert messages
 *
 * @package PW_Framework
 * @since 1.0
 */

class PW_Alerts
{
	/**
	 * Stores the alerts to prevent constant database calls
	 * @var array
	 * @since 1.0
	 */
	private static $_alerts;
	
	
	/**
	 * Adds an alert
	 * @param string $name The key in the option array
	 * @return mixed property value
	 * @since 1.0
	 */
	public static function add( $type, $message, $priority = 10 )
	{
		if ( empty(self::$_alerts) ) {
			self::$_alerts = get_transient( 'PW_Alerts' );
		}

		self::$_alerts[] = array( 'type' => $type, 'message' => $message, 'priority' => $priority );
		set_transient( 'PW_Alerts', self::$_alerts, 10 );
	}
	
	
	/**
	 * Echos the list of alerts
	 * @since 1.0
	 */
	public static function render()
	{
		if ( empty(self::$_alerts) ) {
			self::$_alerts = get_transient( 'PW_Alerts' );
		}
		
		if ( self::$_alerts ) {
			foreach( self::$_alerts as $alert )
			{			
				ZC::e('div.' . $alert['type'], $alert['message']);
			}
			self::$_alerts = array();
			delete_transient( 'PW_Alerts' );
		}
	}	
}
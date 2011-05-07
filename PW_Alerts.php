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
		self::get_alerts();
		
		if ( !in_array( $new_alert = array('type'=>$type, 'message'=>$message, 'priority'=>$priority), self::$_alerts ) ) {
			self::$_alerts[] = array( 'type' => $type, 'message' => $message, 'priority' => $priority );
		}

		set_transient( 'PW_Alerts', self::$_alerts, 10 );
	}
	
	
	/**
	 * Echos the list of alerts
	 * @since 1.0
	 */
	public static function render()
	{
		if ( !self::get_alerts() ) {
			return false;
		}

		// sort the alerts by priority
		usort( self::$_alerts, array('PW_Alerts', 'compare') );
		
		if ( self::$_alerts ) {
			foreach( self::$_alerts as $alert )
			{			
				ZC::e('div.' . $alert['type'], $alert['message']);
			}
			self::$_alerts = array();
			delete_transient( 'PW_Alerts' );
		}
	}
	
	/**
	 * Gets the alerts from the database and stores them in self::$_alerts
	 * If alerts already exists, don't make unnecessary database requests
	 * @return int -1 if $a < $b, 0 if $a == $b, 1 if $a > $b
	 * @since 1.0
	 */
	private static function get_alerts() {
		if ( empty(self::$_alerts) ) {
			self::$_alerts = get_transient( 'PW_Alerts' ) ? get_transient( 'PW_Alerts' ) : array();
		}
		return self::$_alerts ? self::$_alerts : false;
	}
	
	/**
	 * Compares the priority of two alerts; used to sort them in self::render()
	 * @return int -1 if $a < $b, 0 if $a == $b, 1 if $a > $b
	 * @since 1.0
	 */
	private static function compare( $a, $b ) {
		if ( $a['priority'] == $b['priority'] ) {
			return 0;
		} else if ( $a['priority'] < $b['priority'] ) {
			return -1;
		} else if ( $a['priority'] > $b['priority'] ) {
			return 1;
		}
	}
}
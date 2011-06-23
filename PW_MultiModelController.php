<?php
/**
 * PW_MultiModelController
 *
 * The controller class for PW_MultiModel options
 *
 * @package PW_Framework
 * @since 0.1
 */


class PW_MultiModelController extends PW_ModelController
{	
	/**
	 * Detect any GET/POST variables and determine what CRUD option to do based on that
	 * @since 0.1
	 */
	public function process_request()
	{		
		// Set $this->_model->instance, the default is 0 which is the new instance form
		$this->_model->instance = isset($_GET['_instance']) ? (int) $_GET['_instance'] : 0;
		
		// if $this->_model->instance 
		if ( empty($this->_model->option[$this->_model->instance]) ) {
			wp_die( "Oops, this page doesn't exist.", "Page does not exist", array('response' => 403) );
		}
		
		// Check to see if the 'Delete' link was clicked
		if ( 
			isset($_GET['delete_instance'])
			&& isset($_GET['_instance'])
			&& check_admin_referer('delete_instance')
		) {
			PW_Alerts::add('updated', '<p><strong>' . $this->_model->singular_title . ' Instance Deleted</strong></p>' );				

			// make a copy of the option, then unsetting the index and update with the copy
			$option = $this->_model->option;		
			unset( $option[ (int) $_GET['_instance'] ] );
			update_option( $this->_model->name, $option );

			// redirect the page and remove _instance' and 'delete_instance' from the URL
			wp_redirect( remove_query_arg( array( '_instance', 'delete_instance'), wp_get_referer() ) );
			exit();
		}

		
		// If the POST data is set and the nonce checks out, validate and save any submitted data
		if ( isset($_POST[$this->_model->name]) && isset($_POST['_instance']) && check_admin_referer( $this->_model->name . '-options' ) ) {
			
			// get the options from $_POST
			$this->_model->input = stripslashes_deep($_POST[$this->_model->name]);
			
			// save the options
			if ( $this->_model->save($this->_model->input, $_POST['_instance']) ) {
				if ( $_POST['_instance'] == 0 ) {
					wp_redirect( add_query_arg( '_instance', $this->_model->option['auto_id'] - 1, wp_get_referer() ) );				
					exit();
				}
			}
		}
	}
	
	public function on_settings_page()
	{	
		parent::on_settings_page();
		$this->_ie_styles[] = array( 'condition'=>'lt IE 8', 'style'=>array( 'pw-form-ie7', PW_FRAMEWORK_URL . '/css/pw-form-ie7.css' ) );
	}
}
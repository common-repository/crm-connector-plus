<?php
/**
* CRM Connector Plus plugin file. 
*
* Copyright (C) 2010-2020, Smackcoders Inc - info@smackcoders.com 
*/

namespace Smackcoders\WPULB; 
 
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

class FormOptions
{
	protected static $instance = null;

	/**
	 * Form constructor.
	 */
	public function __construct()
	{
		$this->plugin = WPULBPlugin::getInstance();
	}

    /**
     * Form Instances
     * @return null|FormOptions
     */
	public static function getInstance()
	{
		if (null == self::$instance) {
			self::$instance = new self;
			self::$instance->doHooks();
		}
		return self::$instance;
	}

    /**
     * Hooks needed for form class
     */
	public function doHooks()
	{
		add_action('wp_ajax_wpulb_form_settings', array($this, 'formSettings'));
		add_action('wp_ajax_wpulb_form_log_options', array($this, 'formLogOptions'));
		add_action('wp_ajax_wpulb_get_configured_form_settings', array($this, 'getConfiguredformSettings'));
	}

	public static function formSettings(){

		$log = isset($_POST['log']) ? sanitize_text_field($_POST['log']) : '';
		$email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '' ;
		$enable_captcha = isset($_POST['captcha']) ? sanitize_text_field($_POST['captcha'])  : '';

		update_option('WPULB_DEFAULT_FORM_LOG', $log);
		update_option('WPULB_DEFAULT_FORM_MAIL', $email);

		if($enable_captcha == 'true'){
			update_option('WPULB_ENABLE_CAPTCHA' , 'on');

			$public_key = isset($_POST['public_key']) ? sanitize_text_field($_POST['public_key'])  : '';
			$private_key = isset($_POST['private_key']) ? sanitize_text_field($_POST['private_key'])  : ''; 

			update_option('WPULB_ENABLE_CAPTCHA_PUBLIC_KEY' , $public_key);
			update_option('WPULB_ENABLE_CAPTCHA_PRIVATE_KEY' , $private_key);

		}elseif($enable_captcha == 'false'){
			update_option('WPULB_ENABLE_CAPTCHA' , 'off');
		}

		echo wp_json_encode(['response' => '', 'message' => 'Form Settings Updated', 'status' => 200, 'success' => true]);
        wp_die();

	}

	public static function formLogOptions(){
		$supported_logs = array( 'None' , 'Success' , 'Failure' , 'Both' );
			
		$plugins = [];
		$info = [];
		foreach($supported_logs as $value){
			$plugins['label'] = $value;
			$plugins['value'] = $value;

			array_push($info , $plugins);
		}

		echo wp_json_encode(['response' => ['options' => $info], 'message' => 'Form Log Options', 'status' => 200, 'success' => true]);
		wp_die();
	}

	public static function getConfiguredformSettings(){
		$log = get_option('WPULB_DEFAULT_FORM_LOG');
		$email = get_option('WPULB_DEFAULT_FORM_MAIL');
		$captcha = get_option('WPULB_ENABLE_CAPTCHA');

		//if(!empty($log) || !empty($email) || !empty($captcha)){
		if(!empty($log) || !empty($email) || $captcha == 'on'){
			$configured = true;

			if(!empty($log) && $log != 'undefined'){
				$enabled_log = array(
					'label' => $log,
					'value' => $log
				);
			}else{
				$enabled_log = [];
			}
			
			if($captcha == 'on'){
				$enabled_captcha = true;
				$public_key = get_option('WPULB_ENABLE_CAPTCHA_PUBLIC_KEY');
				$private_key = get_option('WPULB_ENABLE_CAPTCHA_PRIVATE_KEY');

				echo wp_json_encode(['response' => ['is_configured' => $configured , 'log' => $enabled_log , 'email' => $email , 'captcha_details' => [ 'captcha' => $enabled_captcha , 'public_key' => $public_key , 'private_key' => $private_key ]], 'message' => 'Configured form settings', 'status' => 200, 'success' => true]);
			}else{
				// echo wp_json_encode(['response' => ['is_configured' => $configured , 'log' => $enabled_log , 'email' => $email , 'captcha_details' => []], 'message' => 'Configured form settings', 'status' => 200, 'success' => true]);
				echo wp_json_encode(['response' => ['is_configured' => $configured , 'log' => $enabled_log , 'email' => $email , 'captcha_details' => ['captcha' => false, 'public_key' => '' , 'private_key' => '']], 'message' => 'Configured form settings', 'status' => 200, 'success' => true]);
			}

		}else{
			$configured = false;
			echo wp_json_encode(['response' => ['is_configured' => $configured , 'log' => [] , 'email' => '', 'captcha_details' => []], 'message' => 'Not configured', 'status' => 200, 'success' => true]);
		}
		wp_die();	
	}
}	
<?php
/**
 * CRM Connector Plus.
 *
 * CRM Connector Plus plugin file.
 *
 * @package   Smackcoders\WPULB
 * @copyright Copyright (C) 2010-2020, Smackcoders Inc - info@smackcoders.com
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License, version 3 or higher
 *
 * @wordpress-plugin
 * Plugin Name:  CRM Connector Plus
 * Version:    	 0.1
 * Description:  Stores Webforms, Users and WooCommerce data in Data Buckets and Migrates these data to CRM automatically once it configured.Embed forms as Posts, Pages & Widgets.
 * Author:       Smackcoders
 * Author URI:   https://www.smackcoders.com/wordpress.html
 * Text Domain:  crm-connector-plus
 * Domain Path:  /languages
 * License:      GPL v3
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

namespace Smackcoders\WPULB;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

include_once(ABSPATH.'wp-admin/includes/plugin.php');

require_once __DIR__ . '/includes/Plugin.php';
require_once __DIR__ . '/includes/PluginTables.php';
require_once __DIR__ . '/includes/ManageFields.php';
require_once __DIR__ . '/includes/Admin.php';
require_once __DIR__ . '/languages/LangEN.php';
require_once __DIR__ . '/languages/LangFR.php';
require_once __DIR__ . '/languages/LangES.php';
require_once __DIR__ . '/languages/LangIT.php';
require_once __DIR__ . '/languages/LangGE.php';

register_activation_hook( __FILE__, array('Smackcoders\\WPULB\\WPULBPlugin','activate' ));
register_deactivation_hook( __FILE__, array('Smackcoders\\WPULB\\WPULBPlugin','deactivate' ));

/**
 * When plugin loads 
 */
add_action( 'plugins_loaded', 'Smackcoders\\WPULB\\pluginInit_WPULB' );

function pluginInit_WPULB() {
	WPULBPlugin::getInstance();
	WPULBAdmin::getInstance();
}

// Load plugin functionalities when it is activated
if(is_plugin_active( WPULBPlugin::$leads_builder_slug . '/' . WPULBPlugin::$leads_builder_slug . '.php')){
	include_once(__DIR__.'/includes/wp-lb-hooks.php');
	$plugin_pages = [ WPULBPlugin::$leads_builder_slug.'-admin-menu' ];
	global $wpulb_plugin_ajax_hooks;

	if(isset($_REQUEST['page'])){
		if(in_array($_REQUEST['page'], $plugin_pages)){
			include_pluginFiles();
		}
	}else if(isset($_REQUEST['action'])){
		if(in_array($_REQUEST['action'], $wpulb_plugin_ajax_hooks)){
			include_pluginFiles();
		}
	}	
}

//if(!function_exists('include_pluginFiles')){
	function include_pluginFiles(){
	
		$includesFile = glob( __DIR__ . '/includes/*.php');
		foreach ($includesFile as $includesFileValue) {
			require_once($includesFileValue);
		}
		
		require_once __DIR__ . '/controllers/installplugins/InstallPlugins.php';
		require_once __DIR__ . '/controllers/ThirdPartyForm.php';
		require_once __DIR__ . '/controllers/defaultformsync/FormOptions.php';
	}
//}

add_filter('cron_schedules', 'Smackcoders\\WPULB\\wpulb_cron_schedules');

 function wpulb_cron_schedules($schedules){
	if(!isset($schedules["5min"])){
		$schedules["5min"] = array(
			'interval' => 5*60,
			'display' => __('Once every 5 minutes'));
	}
	if(!isset($schedules["10min"])){
		$schedules["10min"] = array(
			'interval' => 10*60,
			'display' => __('Once every 10 minutes'));
	}
	if(!isset($schedules["30min"])){
		$schedules["30min"] = array(
			'interval' => 30*60,
			'display' => __('Once every 30 minutes'));
	}
	return $schedules;
}

function schedule_funtion(){

	if (defined('DISABLE_WP_CRON') && DISABLE_WP_CRON == true ) {
		return false;
	}
	
	require_once(plugin_dir_path(__FILE__). 'controllers/ThirdPartyForm.php');
	
	$third_party_instance = ThirdPartyForm::getInstance();
	
	global $wpdb;
	$default_column_names = array('id', 'source_name', 'source_from', 'form_name', 'form_id', 'crm_type', 'created_at', 'sync_status');

	$get_schedule_lists = $wpdb->get_results($wpdb->prepare( "SELECT id FROM {$wpdb->prefix}smack_ulb_databucket_meta WHERE sync_status = %s ORDER BY id ASC LIMIT 5", 'Schedule - Pending'));

	$info = [];
	
	$combine_array = [];
	$data_to_send = [];
	$i = 0;

	if(!empty($get_schedule_lists)){
		foreach($get_schedule_lists as $schedule_lists){
			$form_details = [];
			$field_id = $schedule_lists->id;
			$get_form_columns = $wpdb->get_results("SHOW COLUMNS FROM {$wpdb->prefix}smack_ulb_databucket_meta ");
			foreach($get_form_columns as $form_column){	
				if(!in_array($form_column->Field , $default_column_names)){   
					$get_field_value = $wpdb->get_var($wpdb->prepare( "SELECT $form_column->Field FROM {$wpdb->prefix}smack_ulb_databucket_meta WHERE id = %d AND $form_column->Field IS NOT NULL", $field_id ));	

					if(!empty($get_field_value)){
						$form_details[$form_column->Field] = $get_field_value;	
					}  
				}
			}
			array_push($info, $form_details);

			$get_form_details = $wpdb->get_results($wpdb->prepare( "SELECT * FROM {$wpdb->prefix}smack_ulb_databucket_info WHERE field_id = %d", $field_id));

			foreach($get_form_details as $form_details){
				$crm_mapping = unserialize($form_details->crm_mapping);
				$data_mapping = unserialize($form_details->data_mapping);

				$keys = array_values($data_mapping);	
				$values = array_values($crm_mapping);
				$combine_array = array_combine($keys , $values);

				$connected_addon = $form_details->connected_addon;
				$activated_crm = $form_details->activated_addon;
				$module = $form_details->module;
				$owner = $form_details->owner;
				$duplicate = $form_details->duplicate;
				$crm_users = unserialize($form_details->crm_users);
				$old_users = unserialize($form_details->old_users);
			}

			$data_to_send['post_array'] = $info[$i];
			$data_to_send['inserted_id'] = $field_id;
			$data_to_send['mapping_array'] = $combine_array;
			$data_to_send['module'] = $module;
			$data_to_send['owner'] = $owner;
			$data_to_send['duplicate'] = $duplicate;
			$data_to_send['crm_users'] = $crm_users;
			$data_to_send['old_users'] = $old_users;

			if($connected_addon == 'CRM'){
				$data_to_send['users_option_name'] = 'WPULB_CRM_USER_SYNC_LEAD_OWNER_OLD';
				$active_crm = get_option( 'WPULB_ACTIVE_CRM_ADDON' );
			}elseif($connected_addon == 'HELPDESK'){
				$data_to_send['users_option_name'] = 'WPULB_HELP_USER_SYNC_LEAD_OWNER_OLD';
				$active_crm = get_option( 'WPULB_ACTIVE_HELPDESK_ADDON' );
			}
			
			$third_party_instance->form_sync_during_submit($connected_addon, $active_crm, $activated_crm, $data_to_send);
			$i++;
		}
	}
}

add_action('leads_builder_schedule_hook' , 'Smackcoders\\WPULB\\schedule_funtion');
add_shortcode("smack-web-form", 'Smackcoders\\WPULB\\default_form_function');

function default_form_function($attr = null  , $thirdparty = null){
	global $post;
	preg_match_all("/\\[(.*?)\\]/", $post->post_content, $matches); 
	$shortcode = $matches[1][0];	
	$attr['name'] = substr($shortcode, strpos($shortcode, "=") + 1);
	return defaultFormPRO($attr['name']);
}

function defaultFormPRO($shortcode){
	
	global $wpdb;
	$captcha_error = false;
	$data_bucket_mapping = [];
	$inserted_id = '';

	require_once(plugin_dir_path(__FILE__). 'controllers/ThirdPartyForm.php');
	
	$third_party_instance = ThirdPartyForm::getInstance();

	if( !isset( $_SESSION["generated_forms"] ) )
	{
		$_SESSION["generated_forms"] = 1;
	}
	else
	{
		$_SESSION["generated_forms"]++;
	}

	$check_captcha_enabled = get_option('WPULB_ENABLE_CAPTCHA');
	if(!is_admin() && $check_captcha_enabled == 'on')
	{
		wp_register_script( 'google-captcha-js' , "https://www.google.com/recaptcha/api.js" );
		wp_enqueue_script( 'google-captcha-js' );
	}

	$get_default_form_settings = get_option("WPULB_DEFAULT_FORM_SETTINGS_{$shortcode}");

	/* Check if success message is enabled */
	if(!empty($get_default_form_settings['success_message'])){
		$success_message = $get_default_form_settings['success_message'];
	}
	elseif(empty($get_default_form_settings['success_message'])){
		$success_message = 'Form Submitted Successfully';
	}

	/* Check if failure message is enabled */
	if(!empty($get_default_form_settings['error_message'])){
		$error_message = $get_default_form_settings['error_message'];
	}
	elseif(empty($get_default_form_settings['error_message'])){
		$error_message = 'Form Submission Failed';
	}

	/* Check if url redirection is enabled */
	if(!empty($get_default_form_settings['redirection'])){
		$redirection = true;
	}
	else{
		$redirection = false;
	}

	if(!empty($get_default_form_settings['form_type']) && $get_default_form_settings['form_type'] != 'None'){
		$form_type = $get_default_form_settings['form_type'];
	}else{
		$form_type = 'Post';
	}

	$log_message = get_option('WPULB_DEFAULT_FORM_LOG');
	$email_enabled = get_option('WPULB_DEFAULT_FORM_MAIL');

	$native_form_info = get_option("WPULB_INFO_DEFAULT_FORM_{$shortcode}");
	$enabled_fields = $native_form_info['enabled_fields'];
	$mandatory_fields = $native_form_info['mandatory_fields'];

	if($form_type == 'Post'){
		$table = "<table border = '1'>";
		$table1 = '</table>';
		$tr = '<tr>';
		$td = '<td>';
		$tr1 = '</tr>';
		$td1 = '</td>';
	}
	elseif($form_type == 'Widget'){
		$table = '';
		$table1 = '<br>';
		$tr = '';
		$td = '<br>';
		$tr1 = '';
		$td1 = '';
	}

	$content = "<form id='default_forms' name='default_forms' method='post'> $table";
	$content1 = '';
	foreach($enabled_fields as $enabled_values){

		if(in_array($enabled_values , $mandatory_fields)){
			$mandatory = 'required';
			$M = ' *';
		}
		else{
			$mandatory = '';
			$M = '';
		}

		$get_field_details = $wpdb->get_results($wpdb->prepare( "SELECT field_label, field_type, id FROM {$wpdb->prefix}smack_ulb_manage_fields WHERE field_name = %s", $enabled_values));	

		foreach($get_field_details as $field_details){
			if($field_details->field_type == 'Text'){	
				$content1 .= $tr.$td .$field_details->field_label. $M . $td1 ."$td<input type = 'text' name = '$enabled_values' $mandatory>$td1 $tr1";
			}
			if($field_details->field_type == 'Integer'){
				$content1 .= $tr.$td .$field_details->field_label. $M . $td1 ."$td<input type = 'number' name = '$enabled_values' $mandatory>$td1 $tr1";
			}
			if($field_details->field_type == 'Email'){
				$content1 .= $tr.$td .$field_details->field_label. $M . $td1 ."$td<input type = 'email' name = '$enabled_values' $mandatory>$td1 $tr1";
			}
			if($field_details->field_type == 'Date'){	
				$content1 .= $tr.$td .$field_details->field_label. $M . $td1 ."$td<input type = 'date' name = '$enabled_values' $mandatory>$td1 $tr1";
			}
			if($field_details->field_type == 'DateTime'){
				$content1 .= $tr.$td .$field_details->field_label. $M . $td1 ."$td<input type = 'datetime-local' name = '$enabled_values' $mandatory>$td1 $tr1";
			}
			if($field_details->field_type == 'Decimal'){
				$content1 .= $tr.$td .$field_details->field_label. $M . $td1 ."$td<input type = 'number' step = 'any' name = '$enabled_values' $mandatory>$td1 $tr1";
			}
			if($field_details->field_type == 'File'){
				$content1 .= $tr.$td .$field_details->field_label. $M . $td1 ."$td<input type = 'file' name = '$enabled_values' $mandatory>$td1 $tr1";
			}
			if($field_details->field_type == 'Phone'){
				$content1 .= $tr.$td .$field_details->field_label. $M . $td1 ."$td<input type = 'tel' name = '$enabled_values' $mandatory>$td1 $tr1";
			}
			if($field_details->field_type == 'Currency'){
				$content1 .= $tr.$td .$field_details->field_label. $M . $td1 ."$td<input type = 'number' name = '$enabled_values' $mandatory>$td1 $tr1";
			}
			if($field_details->field_type == 'Hidden'){
				$content1 .= $tr.$td .$field_details->field_label. $M . $td1 ."$td<input type = 'hidden' name = '$enabled_values' $mandatory>$td1 $tr1";
			}
			if($field_details->field_type == 'Boolean'){
				$content1 .= $tr.$td .$field_details->field_label. $M . $td1 ."$td<input type = 'radio' name = '$enabled_values' value = 'true' checked> True<br>
																<input type = 'radio' name = '$enabled_values' value = 'false'> False$td1 $tr1";
			}
			if($field_details->field_type == 'Select'){
				$select_values = $wpdb->get_results($wpdb->prepare( "SELECT picklist_value FROM {$wpdb->prefix}smack_ulb_manage_fields_picklist_values WHERE field_id = %d", $field_details->id));
				if(!empty($select_values)){
					$content1 .= $tr.$td .$field_details->field_label. $M . $td1 ."$td<select name = '$enabled_values' $mandatory><br>";
					foreach($select_values as $pick_list){
						$content1 .= "<option value = '$pick_list->picklist_value' >$pick_list->picklist_value</option><br>";					
					}
					$content1 .="<br></select>$td1 $tr1";
				}
			}
			if($field_details->field_type == 'Multi-Select'){
				$select_values = $wpdb->get_results($wpdb->prepare( "SELECT picklist_value FROM {$wpdb->prefix}smack_ulb_manage_fields_picklist_values WHERE field_id = %d", $field_details->id));
				if(!empty($select_values)){

					$mul_value = "{$enabled_values}[]";
					$content1 .=  $tr.$td .$field_details->field_label. $M . $td1 ."$td<select name = '$mul_value' multiple><br>";
					foreach($select_values as $pick_list){
						$content1 .= "<option value = '$pick_list->picklist_value' >$pick_list->picklist_value</option><br>";					
					}
					$content1 .="<br></select>$td1 $tr1";
				}
			}
		}
	}

	if(isset($_POST['submit'])){	
		if($check_captcha_enabled == 'on' && $get_default_form_settings['captcha'] == 'true'){
			$privatekey = get_option('WPULB_ENABLE_CAPTCHA_PRIVATE_KEY');
		
			if( !isset( $_REQUEST['g-recaptcha-response'] ) || ( sanitize_text_field($_REQUEST['g-recaptcha-response']) == NULL ) || ( sanitize_text_field($_REQUEST['g-recaptcha-response']) == "" ) )
			{
				$captcha_error = true;
			}
			else
			{
				$botcheck_url = "https://www.google.com/recaptcha/api/siteverify?secret=$privatekey&response={$_REQUEST['g-recaptcha-response']}";
				$google_bot_check_result = wp_remote_retrieve_body( wp_remote_get($botcheck_url) );
				$decoded_result = json_decode($google_bot_check_result, true);
			
				if( $decoded_result['success'] )
				{
					$captcha_error = false;
				}
				else
				{
					$captcha_error = true;
				}
			}
		}
	}

	if($check_captcha_enabled == 'on' && $get_default_form_settings['captcha'] == 'true'){
		$publickey = get_option('WPULB_ENABLE_CAPTCHA_PUBLIC_KEY');
		if(isset($captcha_error) && ($captcha_error == true))
		{
			$content1 .="<div style='color:red' id='recaptcha_response_field_error{$_SESSION["generated_forms"]}'>Captcha Error</div>";
		}
		$content1 .= " $tr $td $td1 $td <div class='g-recaptcha' data-sitekey= '$publickey'></div> $td1 $tr1";	
	}

	$content1 .=  "$table1<input type = 'submit' name = 'submit' value = 'Submit'><br>";
	$content .= $content1;
	$content .= "<input type='hidden' value='module' name='' /></p></form>";
	
	if(isset($_POST['submit'])){	
		$post_data = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);
		
		$connected_addon = $native_form_info['configured_addon'];
		
		if($connected_addon == 'CRM' || $connected_addon == 'HELPDESK'){
			if($connected_addon == 'CRM'){
				$active_crm = get_option( 'WPULB_ACTIVE_CRM_ADDON' );
				$activated_crm = $native_form_info['connected_crm'];

				$mapped_array = get_option( "WPULB_DATA_MAPPING_DEFAULT_FORM_{$shortcode}");
				$addon_array = get_option("WPULB_CRM_MAPPING_DEFAULT_FORM_{$shortcode}");

			}elseif($connected_addon == 'HELPDESK'){
				$active_crm = get_option( 'WPULB_ACTIVE_HELPDESK_ADDON' );
				$activated_crm = $native_form_info['connected_help'];

				$mapped_array = get_option( "WPULB_HELP_DATA_MAPPING_DEFAULT_FORM_{$shortcode}");
				$addon_array = get_option("WPULB_HELP_MAPPING_DEFAULT_FORM_{$shortcode}");
			}

			if(!empty($mapped_array)){
				foreach($mapped_array as $data_key => $data_value){
					if(isset($addon_array[$data_key])){
						$data_bucket_mapping[$data_value] = $addon_array[$data_key];
					}	
				}
			}
		}elseif($connected_addon == 'DATA_BUCKET_ONLY'){
			$activated_crm = 'DataBucket';
			$data_bucket_mapping = 'yes';
		}
		
		//if($mapped_array){
			$data_to_store = [];
		
			if(!empty($data_bucket_mapping) && is_array($data_bucket_mapping)){
				foreach($data_bucket_mapping as $ninja_form_fieldname => $local_crm_fieldname){
					if(isset($post_data[$local_crm_fieldname]) && !empty($ninja_form_fieldname)){
						// $data_to_store[$ninja_form_fieldname] = wp_kses_post($_POST[$local_crm_fieldname]);
						$data_to_store[$ninja_form_fieldname] = $post_data[$local_crm_fieldname];
					}
				}
			}else{
				if(array_key_exists('g-recaptcha-response' , $post_data)){
					unset($post_data['g-recaptcha-response']);
				}	
				// $data_to_store =  wp_kses_post($_POST);
				$data_to_store =  $post_data;
			}
			
			$source_name = 'Default Form';
			$source_from = 'Posts';
	
			if(!empty($data_bucket_mapping)){
				if(!empty($data_to_store)){
					
					$wpdb->insert( 
						"{$wpdb->prefix}smack_ulb_databucket_meta", 
						array("source_name" => $source_name, "source_from" => $source_from, 'form_name' => $shortcode, "crm_type" => $activated_crm),
						array('%s', '%s', '%s', '%d', '%s')
					); 
					$inserted_id = $wpdb->insert_id;	
					
					foreach($data_to_store as $data_key => $data_value){
						if($data_key != 'submit' && $data_value != 'Submit'){
							// multi select values will be in array
							if(is_array($data_value)){
								$data_value = serialize($data_value);
							}
							
							if(!empty($data_key) && !empty($data_value)){
								$wpdb->update( 
									"{$wpdb->prefix}smack_ulb_databucket_meta",
									array("$data_key" => $data_value), 
									array('id' => $inserted_id), 
									array('%s'), 
									array('%d') 
								);
							}
						}
					}
				}
			}
			//$_POST  = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);
			$data_to_send = [];
			$data_to_send['post_array'] =$post_data ;
			$data_to_send['inserted_id'] = $inserted_id;
		
			if($connected_addon == 'CRM' || $connected_addon == 'HELPDESK'){

				if($connected_addon == 'CRM'){
					$data_to_send = $third_party_instance->get_crm_info('DEFAULT_FORM', $shortcode , $data_to_send);
				}
				elseif($connected_addon == 'HELPDESK'){
					$data_to_send = $third_party_instance->get_helpdesk_info('DEFAULT_FORM', $shortcode , $data_to_send);
				}

				$schedule_status = get_option("WPULB_SCHEDULE_STATUS");

				if($schedule_status == 'off'){
					if(!empty($data_bucket_mapping)){
						$third_party_instance->update_status_to_databucket_table($inserted_id , 'Pending');
					}
					$get_form_status = $third_party_instance->form_sync_during_submit($connected_addon, $active_crm, $activated_crm, $data_to_send);
				
					if( $get_form_status == 'Failed' ){
						$success_message = 'Form Submission Failed';
					}
					
				}elseif($schedule_status == 'on'){
					$third_party_instance->update_status_to_databucket_table($inserted_id , 'Schedule - Pending');
				
					$wpdb->insert( 
						"{$wpdb->prefix}smack_ulb_databucket_info", 
						array("data_mapping" => serialize($data_bucket_mapping), "crm_mapping" => serialize($data_to_send['mapping_array']), 'module' => $data_to_send['module'], 
							  "owner" => $data_to_send['owner'],  "duplicate" => $data_to_send['duplicate'],  "crm_users" => serialize($data_to_send['crm_users']),
							  "old_users" => $data_to_send['old_users'], "connected_addon" => $connected_addon, "activated_addon" => $activated_crm, "field_id" => $inserted_id),
						array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d')
					); 
				}
				
			}else{
				$third_party_instance->update_status_to_databucket_table($inserted_id , 'CRM/Helpdesk not configured');
			}

	}

	if(isset($_POST['submit'])){
		$contenttype = "\n";
		$post_data  = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);
		if(is_array($post_data)){
			foreach($post_data as $keys => $values){
				if(($keys != 'submit') && ($keys != "g-recaptcha-response")){
					$get_field_label = $wpdb->get_var($wpdb->prepare("SELECT field_label FROM {$wpdb->prefix}smack_ulb_manage_fields WHERE field_name = %s", $keys));	
				
					if(!empty($get_field_label)){
						$contenttype.= "{$get_field_label} : $values"."\n";
					}
					else{
						$contenttype.= "$keys : $values"."\n";
					}
				}
			}
		}

		$pageurl = curlPageURLPRO();
		if((!empty($email_enabled)) && isset($log_message) && (($log_message == 'Success') || ($log_message == 'Both'))){
			mailsendPRO( $email_enabled , $shortcode, $pageurl, "Success" , $contenttype );
		}

		if($redirection){	
			if(is_numeric($get_default_form_settings['redirection'])){
				$redirect_url = get_permalink($get_default_form_settings['redirection']);
			}
			else{
				$redirect_url = $get_default_form_settings['redirection'];
			}
			return "<script>location.href='$redirect_url'</script>";
		}

		if(isset($captcha_error) && ($captcha_error == true)){
			return $content;
		}else{
			if($success_message == 'Form Submission Failed'){
				$content_message ="<div style='color:red' id='form_failure_message'>$success_message</div>";
			}else{
				$content_message ="<div style='color:green' id='form_success_message'>$success_message</div>";
			}
			// return $content_message . $content;
			return $content_message;
		}
	}
	elseif(!isset($_POST)){
		$pageurl = curlPageURLPRO();
		if((!empty($email_enabled)) && isset($log_message) && (($log_message == 'Failure') || ($log_message == 'Both'))){
			mailsendPRO( $email_enabled , $shortcode, $pageurl, "Failure" , $contenttype );
		}

		if($redirection){	
			if(is_numeric($get_default_form_settings['redirection'])){
				$redirect_url = get_permalink($get_default_form_settings['redirection']);
			}
			else{
				$redirect_url = $get_default_form_settings['redirection'];
			}
			return "<script>location.href='$redirect_url'</script>";
		}

		$content_message ="<div style='color:red' id='form_failure_message'>$error_message</div>";
		// return $content_message . $content;
		return $content_message;
	}
	else{
		return $content;
	}
}

	function mailsendPRO( $config,$formtype, $pageurl,$data,$contenttype )
	{
		$subject = 'Form Details';
		//$message = "Shortcode : " . "[$activatedplugin-web-form type='$formtype']" ."\n" . "URL: " . $pageurl ."\n" . "Type:".$formtype ."\n". "Form Status:".$data . "\n" . "FormFields and Values:"."\n".$contenttype ."\n"."User IP:".self::getipPRO();
		$message = "Shortcode : " . "[smack-web-form name='$formtype']" ."\n" . "URL: " . $pageurl ."\n" . "Type:Post" ."\n". "Form Status:".$data . "\n" . "FormFields and Values:"."\n".$contenttype ."\n"."User IP:".getipPRO();
		$admin_email = get_option('admin_email');
		$headers = "From: Administrator <$admin_email>" . "\r\n\\";
		if(isset($config) && ($config == ""))
		{
			$to = "{$admin_email}";
		}
		else
		{
			$to = "{$config}";
		}
		wp_mail( $to, $subject, $message,$headers );	
	}

	function curlPageURLPRO() {
		$pageURL = 'http';
		if (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == "on") {$pageURL .= "s";}
		$pageURL .= "://";
		if ($_SERVER["SERVER_PORT"] != "80") {
			$pageURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];
		} else {
			$pageURL .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
		}
		return $pageURL;
	}

	function getipPRO()
	{
		$ip = $_SERVER['REMOTE_ADDR'];
		return $ip;
	}

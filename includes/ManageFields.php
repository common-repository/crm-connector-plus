<?php
/**
* CRM Connector Plus plugin file. 
*
* Copyright (C) 2010-2020, Smackcoders Inc - info@smackcoders.com 
*/

namespace Smackcoders\WPULB;

if ( ! defined( 'ABSPATH' ) )
	exit; // Exit if accessed directly

class ManageFields
{
	protected static $instance = null,$lbData;
	protected static $joforcecrm_instance = null;

	/**
	 * __construct
	 *
	 * @return void
	 */
	public function __construct(){
		//
	}

	/**
	 * getInstance
	 *
	 * @return void
	 */
	public static function getInstance(){

		if (self::$instance == null) {
			self::$instance=new self();
			self::$joforcecrm_instance = JoForceCrm::getInstance();
			self::$instance->doHooks();
		}

		return self::$instance;
	}

	/**
	 * doHooks
	 *
	 * @return void
	 */
	public function doHooks(){

		add_action('wp_ajax_wpulb_save_local_crm_field', array($this, 'createOrUpdateField'));
		add_action('wp_ajax_wpulb_change_status', array($this, 'changeStatus'));
		add_action('wp_ajax_wpulb_delete_field', array($this, 'deleteField'));
		add_action('wp_ajax_wpulb_get_local_crm_field_list', array($this, 'fetchAllFields'));
		add_action('wp_ajax_wpulb_create_local_crm_custom_field', array($this, 'showCreateFieldForm'));
	}

	/**
	 * changeStatus
	 *
	 * @return void
	 */
	public static function changeStatus(){

		$field_id = intval($_POST['field_id']);
		$status = sanitize_text_field($_POST['status']);

		if($status == 'true'){
			$is_active = 'Active';
		}else{
			$is_active = 'Inactive';
		}

		global $wpdb;
		$wpdb->update( 
			"{$wpdb->prefix}smack_ulb_manage_fields",
			array('is_active' => $is_active), 
			array('id' => $field_id), 
			array('%s'), 
			array('%d') 
		);

		if($status == 'true'){
			echo wp_json_encode(['response' => ['fields_by_grouping' => ManageFields::fetchAllFields('active-list')], 'message' => 'Field Activated', 'status' => 200, 'success' => true]);
		}else{
			echo wp_json_encode(['response' => ['fields_by_grouping' => ManageFields::fetchAllFields('active-list')], 'message' => 'Field Inactivated', 'status' => 200, 'success' => true]);
		}

		wp_die();
	}

	/**
	 * getDataBucketFields
	 *
	 * @return void
	 */
	public static function getDataBucketFields(){

		global $wpdb;
		$data_bucket_fields = [];
		$fields = $wpdb->get_results("SELECT field_name, field_label FROM {$wpdb->prefix}smack_ulb_manage_fields", ARRAY_A);
		foreach($fields as $field){
			$data_bucket_fields[$field['field_name']] = $field['field_label'];
		}
		return $data_bucket_fields;
	}

	/**
	 * getAddonFields
	 *
	 * @param  mixed $addon
	 * @param  mixed $module
	 *
	 * @return void
	 */
	public static function getAddonFields($addon, $module , $sync){

		if($sync == 'CRM'){
			switch( $addon )
			{
			case 'vtigercrm':
				$addon_slug = 'vtiger-pro-plus';
				$addon_name = 'Vtiger Pro Plus';
				$license_key_option = 'WPULB_VTIGERCRM_LICENSE_KEY';
				$license_response = self::$instance->verifyLicenseKey($addon_slug,$license_key_option);
				if($license_response['success'] == 'true'){
					$existConfig = self::$instance->get_crmfields_by_settings('vtigercrm',$module);
					if(!empty($existConfig))
					{
						return ManageFields::get_addon_fields($existConfig);
					}
					else{
						global $vtiger_addon_instance;
						$vtiger_addon_instance->wpulb_fetch_vtigercrm_fields();
					}
				}
				else{
					echo wp_json_encode([
						'response' => '',
						'message' => $addon_name .' - '.$license_response['message'], 
						'status' => 200, 
						'success' => false
					]);
					wp_die(); 
				}
				break;
			case 'zohocrm':
				$addon_slug = 'zoho-crm-pro-plus';
				$addon_name = 'Zoho CRM Pro Plus';
				$license_key_option = 'WPULB_ZOHOCRM_LICENSE_KEY';
				$license_response = self::$instance->verifyLicenseKey($addon_slug,$license_key_option);
				if($license_response['success'] == 'true'){
					if($module == 'SalesOrder'){
						$module = 'Sales_Orders';
					}
					global $wpdb;
					$existConfig = self::$instance->get_crmfields_by_settings('zohocrm',$module);
					if(!empty($existConfig))
					{
						return ManageFields::get_addon_fields($existConfig);
					}
					else{
						global $zoho_addon_instance;
						$zoho_addon_instance->wpulb_fetch_zohocrm_fields();
					}
				}
				else{
					echo wp_json_encode([
						'response' => '',
						'message' => $addon_name .' - '.$license_response['message'], 
						'status' => 200, 
						'success' => false
					]);
					wp_die(); 
				}

				break;
			case 'joforcecrm':
				
				$existConfig = self::$instance->get_crmfields_by_settings('joforcecrm',$module);
				if(!empty($existConfig))
				{
					return ManageFields::get_addon_fields($existConfig);
				}
				else{
					self::$joforcecrm_instance->wpulb_fetch_joforcecrm_fields();
				}
				break;
			case 'salesforcecrm':
				$addon_slug = 'salesforce-pro-plus';
				$addon_name = 'Salesforce Pro Plus';
				$license_key_option = 'WPULB_SALESFORCECRM_LICENSE_KEY';
				$license_response = self::$instance->verifyLicenseKey($addon_slug,$license_key_option);
				if($license_response['success'] == 'true'){
					$existConfig = self::$instance->get_crmfields_by_settings('salesforcecrm',$module);
					if(!empty($existConfig))
					{
						return ManageFields::get_addon_fields($existConfig);
					}
					else{
						global $salesforce_addon_instance;
						$salesforce_addon_instance->wpulb_fetch_salesforcecrm_fields();
					}
				}
				else{
					echo wp_json_encode([
						'response' => '',
						'message' => $addon_name .' - '.$license_response['message'], 
						'status' => 200, 
						'success' => false
					]);
					wp_die(); 
				}
				break;
			case 'freshsalescrm':
				$addon_slug = 'freshsales-pro-plus';
				$addon_name = 'Freshsales Pro Plus';
				$license_key_option = 'WPULB_FRESHSALES_LICENSE_KEY';
				$license_response = self::$instance->verifyLicenseKey($addon_slug,$license_key_option);
				if($license_response['success'] == 'true'){
					$existConfig = self::$instance->get_crmfields_by_settings('freshsalescrm',$module);
					if(!empty($existConfig))
					{
						return ManageFields::get_addon_fields($existConfig);
					}
					else{
						global $freshsales_addon_instance;
						$freshsales_addon_instance->wpulb_fetch_freshsalescrm_fields();
					}
				}
				else{
					echo wp_json_encode([
						'response' => '',
						'message' => $addon_name .' - '.$license_response['message'], 
						'status' => 200, 
						'success' => false
					]);
					wp_die(); 
				}
				break;
			case 'suitecrm':
				$addon_slug = 'suite-pro-plus';
				$addon_name = 'Suite Pro Plus';
				$license_key_option = 'WPULB_SUITECRM_LICENSE_KEY';
				$license_response = self::$instance->verifyLicenseKey($addon_slug,$license_key_option);
				if($license_response['success'] == 'true'){
					if($module == 'Products'){
						$module = 'AOS_Products';
					}

					$existConfig = self::$instance->get_crmfields_by_settings('suitecrm',$module);
					if(!empty($existConfig))
					{
						return ManageFields::get_addon_fields($existConfig);
					}
					else{
						global $suite_addon_instance;
						$suite_addon_instance->wpulb_fetch_suitecrm_fields();
					}
				}
				else{
					echo wp_json_encode([
						'response' => '',
						'message' => $addon_name .' - '.$license_response['message'], 
						'status' => 200, 
						'success' => false
					]);
					wp_die(); 
				}

				break;
			case 'sugarcrm':
				$addon_slug = 'sugar-pro-plus';
				$addon_name = 'Sugar Pro Plus';
				$license_key_option = 'WPULB_SUGARCRM_LICENSE_KEY';
				$license_response = self::$instance->verifyLicenseKey($addon_slug,$license_key_option);
				if($license_response['success'] == 'true'){
					if($module == 'Products'){
						$module = 'ProductTemplates';
					}
					$existConfig = self::$instance->get_crmfields_by_settings('sugarcrm',$module);
					if(!empty($existConfig))
					{
						return ManageFields::get_addon_fields($existConfig);
					}
					else{
						global $sugar_addon_instance;
						$sugar_addon_instance->wpulb_fetch_sugarcrm_fields();
					}
				}
				else{
					echo wp_json_encode([
						'response' => '',
						'message' => $addon_name .' - '.$license_response['message'], 
						'status' => 200, 
						'success' => false
					]);
					wp_die(); 
				}
				break;

			}
		}elseif($sync == 'HELPDESK'){
			switch($addon){
			case 'freshdesk':
				$addon_slug = 'freshdesk-pro-plus';
				$addon_name = 'Freshdesk Pro Plus';
				$license_key_option = 'WPULB_FRESHDESK_LICENSE_KEY';
				$license_response = self::$instance->verifyLicenseKey($addon_slug,$license_key_option);
				if($license_response['success'] == 'true'){
					$existConfig = self::$instance->get_crmfields_by_settings('freshdesk',$module);
					if(!empty($existConfig))
					{
						return ManageFields::get_addon_fields($existConfig);
					}
					else{
						global $freshdesk_addon_instance;
						$freshdesk_addon_instance->wpulb_fetch_freshdesk_fields();
					}
				}
				else{
					echo wp_json_encode([
						'response' => '',
						'message' => $addon_name .' - '.$license_response['message'], 
						'status' => 200, 
						'success' => false
					]);
					wp_die(); 
				}
				break;

			case 'vtigersupport':
				$addon_slug = 'vtiger-ticket-pro-plus';
				$addon_name = 'Vtiger Ticket Pro Plus';
				$license_key_option = 'WPULB_VTIGETTICKET_LICENSE_KEY';
				$license_response = self::$instance->verifyLicenseKey($addon_slug,$license_key_option);
				if($license_response['success'] == 'true'){
					$existConfig = self::$instance->get_crmfields_by_settings('vtigersupport',$module);
					if(!empty($existConfig))
					{
						return ManageFields::get_addon_fields($existConfig);
					}
					else{
						global $vtigersupport_addon_instance;
						$vtigersupport_addon_instance->wpulb_fetch_vtigersupport_fields();
					}
				}
				else{
					echo wp_json_encode([
						'response' => '',
						'message' => $addon_name .' - '.$license_response['message'], 
						'status' => 200, 
						'success' => false
					]);
					wp_die(); 
				}
				break;

			case 'zendesk':
				$addon_slug = 'zendesk-pro-plus';
				$addon_name = 'Zendesk Pro Plus';
				$license_key_option = 'WPULB_ZENDESK_LICENSE_KEY';
				$license_response = self::$instance->verifyLicenseKey($addon_slug,$license_key_option);
				if($license_response['success'] == 'true'){
					$existConfig = self::$instance->get_crmfields_by_settings('zendesk',$module);
					if(!empty($existConfig))
					{
						return ManageFields::get_addon_fields($existConfig);
					}
					else{
						global $zendesk_addon_instance;
						$zendesk_addon_instance->wpulb_fetch_zendesk_fields();
					}
				}
				else{
					echo wp_json_encode([
						'response' => '',
						'message' => $addon_name .' - '.$license_response['message'], 
						'status' => 200, 
						'success' => false
					]);
					wp_die(); 
				}
				break;

			case 'zohosupport':
				$addon_slug = 'zoho-desk-pro-plus';
				$addon_name = 'Zoho Desk Ticket Pro Plus';
				$license_key_option = 'WPULB_ZOHODESK_LICENSE_KEY';
				$license_response = self::$instance->verifyLicenseKey($addon_slug,$license_key_option);
				if($license_response['success'] == 'true'){
					$existConfig = self::$instance->get_crmfields_by_settings('zohosupport',$module);
					if(!empty($existConfig))
					{
						return ManageFields::get_addon_fields($existConfig);
					}
					else{
						global $zohosupport_addon_instance;
						$zohosupport_addon_instance->wpulb_fetch_zohosupport_fields();
					}
				}
				else{
					echo wp_json_encode([
						'response' => '',
						'message' => $addon_name .' - '.$license_response['message'], 
						'status' => 200, 
						'success' => false
					]);
					wp_die(); 
				}
				break;

			}
		}
		global $wpdb;
		$crm_fields = $wpdb->get_results( $wpdb->prepare( "SELECT field_name, field_label, field_mandatory FROM {$wpdb->prefix}smack_ulb_outcrm_field_manager WHERE crm_type = %s AND module_type = %s", $addon, $module));
		return ManageFields::get_addon_fields($crm_fields);  
	}

	public static function verifyLicenseKey($addon_slug,$license_key_option){
		$license_key = get_option($license_key_option);
		$urlparts = parse_url(home_url());
		$domain_name = $urlparts['host'];
		$url ='https://www.smackcoders.com/?rest_route=/licensemanager/v1/isvalid&product_slug='.$addon_slug.'&key='.$license_key.'&domain_url='.$domain_name;
		
		$headers = array( 'Content-Type' => 'application/json');
		$args = array(
				'method' => 'POST',
				'sslverify' => false,
				'headers' => $headers
				);

        $result = wp_remote_post($url, $args ) ;
		$result_value = wp_remote_retrieve_body($result);
		$result_array = json_decode($result_value, true);
		return $result_array;
	}

	public static function get_addon_fields($addon_array){
		$crm_fieldname_fieldlabel = [];
		$temp = 0;
		foreach($addon_array as $crm_field){
			$crm_fieldname_fieldlabel[$temp]['label'] = $crm_field->field_label;
			$crm_fieldname_fieldlabel[$temp]['value'] = $crm_field->field_name;

			if($crm_field->field_mandatory == 0){
				$crm_fieldname_fieldlabel[$temp]['mandatory'] = false;
			}
			elseif($crm_field->field_mandatory == 1){
				$crm_fieldname_fieldlabel[$temp]['mandatory'] = true;
			}

			$temp++;
		}
		return $crm_fieldname_fieldlabel; 
	}

	/**
	 * showCreateFieldForm
	 *
	 * @return void
	 */
	public static function showCreateFieldForm(){

		global $wpdb, $field_types;
		$groups = $wpdb->get_results("SELECT id, group_name FROM {$wpdb->prefix}smack_ulb_group_manager ORDER BY sequence DESC", ARRAY_A);
		$groups_label_and_value = [];
		$temp = 0;
		foreach($groups as $group){
			$groups_label_and_value[$temp]['label'] = $group['group_name'];
			$groups_label_and_value[$temp]['value'] = $group['id'];
			$temp++;
		}
		echo wp_json_encode(['response' => ['groups' => $groups_label_and_value, 'field_types' => $field_types], 'message' => 'Show Create Field Form', 'status' => 200, 'success' => true]);
		wp_die();
	}

	/**
	 * createField
	 *
	 * @param  mixed $field_name
	 * @param  mixed $field_label
	 * @param  mixed $field_type
	 * @param  mixed $is_mandatory
	 *
	 * @return void
	 */
	public static function createOrUpdateField(){
		global $wpdb;
		$field_label = isset($_POST['field_label']) ? sanitize_text_field($_POST['field_label']) : '';
		$field_name = preg_replace('/[^a-zA-Z0-9_.]/', '', $field_label);

		if($field_label == 'Select' || $field_label == 'select'){
			$field_name = $field_name .'1';
		}

		$field_type = sanitize_text_field($_POST['field_type']);
		$is_mandatory = sanitize_text_field($_POST['is_mandatory']);
		$field_id = isset($_POST['field_id']) ? intval($_POST['field_id']) : '';
		$group_id = isset($_POST['group_id']) ? intval($_POST['group_id']) : '';
		
		$has_already = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) as has_already FROM {$wpdb->prefix}smack_ulb_manage_fields WHERE field_name = %s", $field_name));

		if($has_already){
			echo wp_json_encode(['response' => '', 'message' => 'Field already exists', 'status' => 200, 'success' => true]);
			wp_die();
		}

		$sequence_in_group = ManageFields::getSequenceNo($group_id);

		if($is_mandatory == 'false'){
			$is_active = 'Inactive';
		}else if($is_mandatory == 'true'){
			$is_active = 'Active';
		}

		$update = false;

		if($field_id){
			$update = true;
			$wpdb->update( 
				"{$wpdb->prefix}smack_ulb_manage_fields",
				array('field_label' => $field_label, 'is_active' => $is_active, 'group_id' => $group_id), 
				array('id' => $field_id), 
				array('%s', '%s', '%d', '%s'), 
				array('%d') 
			);
		}else{
			$wpdb->insert( 
				"{$wpdb->prefix}smack_ulb_manage_fields", 
				array("field_label" => $field_label, "field_name" => $field_name, 'field_type' => $field_type, 'is_active' => $is_active, 'is_custom_field' => 'Yes', 'group_id' => $group_id, 'sequence_in_group' => $sequence_in_group),
				array('%s', '%s', '%s', '%s', '%s', '%d', '%d', '%s')
			);
		}

		if(in_array($field_type, ['Select', 'Multi-Select'])){
			$field_values = isset($_POST['field_values']) ? sanitize_text_field($_POST['field_values']) : '';
			if($field_id){
				self::createFieldOptionValue($field_values, $field_id, $field_name, $field_type);
			}else{
				self::createFieldOptionValue($field_values, $wpdb->insert_id, $field_name, $field_type);
			}
		}

		if($update){
			echo wp_json_encode(['response' => '', 'message' => 'Field updated successfully', 'status' => 200, 'success' => true]);
			wp_die();
		}


		ManageFields::insertFieldsToTable($field_name , $field_type);

		echo wp_json_encode(['response' => '', 'message' => 'Field added successfully', 'status' => 200, 'success' => true]);
		wp_die();
	}

	/**
	 * getSequenceNo
	 *
	 * @param  mixed $group_id
	 *
	 * @return void
	 */
	public static function getSequenceNo($group_id){

		global $wpdb;
		$sequence_in_group = $wpdb->get_var($wpdb->prepare( "SELECT sequence_in_group FROM {$wpdb->prefix}smack_ulb_manage_fields WHERE group_id = %d ORDER BY sequence_in_group DESC LIMIT 1", $group_id));

		if(!$sequence_in_group){
			return 1;
		}

		return intval($sequence_in_group) + 1;
	}

	/**
	 * deleteField
	 *
	 * @return void
	 */
	public static function deleteField(){
		// Delete is not allowed
		$field_id = intval($_POST['field_id']);
		global $wpdb;

		$get_custom_status = $wpdb->get_var($wpdb->prepare("SELECT is_custom_field FROM {$wpdb->prefix}smack_ulb_manage_fields WHERE id = %d", $field_id));
		$get_field_type = $wpdb->get_var($wpdb->prepare("SELECT field_type FROM {$wpdb->prefix}smack_ulb_manage_fields WHERE id = %d", $field_id));
		if($get_custom_status == 'Yes'){

			// delete from databucket table
			$field_name = $wpdb->get_var($wpdb->prepare("SELECT field_name FROM {$wpdb->prefix}smack_ulb_manage_fields WHERE id = %d", $field_id));
			
			$wpdb->delete( 
				"{$wpdb->prefix}smack_ulb_manage_fields",
				array('id' => $field_id),  
				array('%d') 
			);

			if($get_field_type == 'Select' || $get_field_type == 'Multi-Select'){
				$wpdb->delete( 
					"{$wpdb->prefix}smack_ulb_manage_fields_picklist_values",
					array('field_id' => $field_id),  
					array('%d') 
				);
			}

			echo wp_json_encode(['response' => '', 'message' => 'Field deleted successfully', 'status' => 200, 'success' => true]);
		}else{
			echo wp_json_encode(['response' => '', 'message' => 'Basic fields cannot be deleted', 'status' => 200, 'success' => false]);
		}    
		wp_die();
	}

	/**
	 * fetchAllFields
	 *
	 * @return void
	 */
	public static function fetchAllFields($return_as = false){

		global $wpdb;
		if($return_as == 'field-list'){
			return $wpdb->get_results( "SELECT fields.*, manager.group_name FROM {$wpdb->prefix}smack_ulb_manage_fields fields LEFT JOIN {$wpdb->prefix}smack_ulb_group_manager manager on manager.id = fields.group_id ORDER BY fields.updated_at DESC" );    
		}

		if($return_as == 'field-name-and-label'){
			$local_crm_fields = [];
			$fields = $wpdb->get_results( "SELECT field_name, field_label FROM {$wpdb->prefix}smack_ulb_manage_fields" );
			foreach($fields as $field){
				$local_crm_fields[$field->field_name] = $field->field_label;
			}
			return $local_crm_fields;
		}

		if($return_as == 'show-fields-for-mapping'){ // Return active fields only for mapping section
			$fields = $wpdb->get_results( "SELECT fields.*, manager.group_name FROM {$wpdb->prefix}smack_ulb_manage_fields fields LEFT JOIN {$wpdb->prefix}smack_ulb_group_manager manager on manager.id = fields.group_id WHERE fields.is_active = 'Active'", ARRAY_A); 
		}else{
			$fields = $wpdb->get_results( "SELECT fields.*, manager.group_name FROM {$wpdb->prefix}smack_ulb_manage_fields fields LEFT JOIN {$wpdb->prefix}smack_ulb_group_manager manager on manager.id = fields.group_id", ARRAY_A); 
		}
		$fields_by_grouping = [];

		foreach($fields as $field){

			$is_custom = $field['is_custom_field'];
			if($is_custom == 'No'){
				$field['can_delete'] = false;
			}else{
				$field['can_delete'] = true;
			}

			$temp_group_name = $field['group_id'].'-----'.$field['group_name'];
			$fields_by_grouping[$temp_group_name][] = $field;
		}

		$fields_group = [];
		foreach($fields_by_grouping as $temp_group_name => $grouping_values){
			list($group_id, $group_name) = explode('-----', $temp_group_name);
			$fields_group[] = ['group_name' => $group_name, 'group_id' => $group_id, 'field_group' => $grouping_values];
		}

		if($return_as == 'show-fields-for-mapping'){
			return $fields_group;
		}

		if($return_as == 'active-list'){
			return $fields_group;
		}

		echo wp_json_encode(['response' => ['fields_by_grouping' => $fields_group], 'message' => 'All Fields', 'status' => 200, 'success' => true]);
		wp_die();
	}

	/**
	 * createFieldOptionValue
	 *
	 * @param  mixed $field_values
	 * @param  mixed $field_id
	 *
	 * @return void
	 */
	public static function createFieldOptionValue($field_values, $field_id, $field_name, $field_type){

		global $wpdb;
		$field_request =  str_replace("\\" , '' , $field_values);  
		$field_values = json_decode($field_request, True);

		$wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->prefix}smack_ulb_manage_fields_picklist_values WHERE field_id = %d", array($field_id)));
		foreach($field_values as $field_value){
			$wpdb->insert( 
				"{$wpdb->prefix}smack_ulb_manage_fields_picklist_values", 
				array("field_id" => $field_id, "picklist_value" => $field_value),
				array('%d', '%s')
			);
		}

		ManageFields::insertFieldsToTable($field_name , $field_type);

		echo wp_json_encode(['response' => '', 'message' => 'Option value added successfully', 'status' => 200, 'success' => true]);
		wp_die();
	}

	/**
	 * createDefaultFields
	 *
	 * @return void
	 */
	public static function createDefaultFields(){

		global $wpdb;
	
		include( plugin_dir_path(__FILE__). 'leads-builder-default-fields.php' );
		global $default_fields;
		foreach($default_fields as $default_field){
			$wpdb->insert( 
				"{$wpdb->prefix}smack_ulb_manage_fields", 
				array("field_label" => $default_field['field_label'], "field_name" => $default_field['field_name'], 'field_type' => $default_field['field_type'], 'is_mandatory' => $default_field['is_mandatory'], 'is_custom_field' => $default_field['is_custom_field'], 'created_at' => current_time('mysql'), 'updated_at' => current_time('mysql')),
				array('%s', '%s', '%s', '%s', '%s', '%s', '%s')
			);
		}
	}


	public static function insertFieldsToTable($field_name , $field_type){
		global $wpdb;
	}

	public static function get_crmfields_by_settings($crmtype, $module)
	{
		global $wpdb;
		return $wpdb->get_results( $wpdb->prepare( "SELECT field_name, field_label, field_mandatory FROM {$wpdb->prefix}smack_ulb_outcrm_field_manager WHERE crm_type = %s AND module_type = %s", $crmtype, $module));
	}
}

global $leads_manage_fields_instance;
$leads_manage_fields_instance = new ManageFields();

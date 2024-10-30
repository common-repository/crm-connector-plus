<?php
/**
* CRM Connector Plus plugin file. 
*
* Copyright (C) 2010-2020, Smackcoders Inc - info@smackcoders.com 
*/

namespace Smackcoders\WPULB;

if ( ! defined( 'ABSPATH' ) )
	exit; // Exit if accessed directly

require_once(plugin_dir_path(__FILE__) .'JoForce.php');

class JoForceCrm
{
	protected static $instance = null;
	protected static $joforce_instance = null;

	/**
	 * __construct
	 *
	 * @return void
	 */
	public function __construct(){
		
	}

	/**
	 * getInstance
	 *
	 * @return void
	 */
	public static function getInstance(){
		if (self::$instance == null) {
			self::$instance=new self();
			self::$joforce_instance = Joforce::getInstance();
		}
		return self::$instance;
	}

    public function wpulb_capture_form_submission_joforce($post_params){
		if(get_option('WPULB_ACTIVE_CRM_ADDON') == 'joforcecrm'){
		
			$mapped_array = $post_params['post_array'];
			$inserted_id = $post_params['inserted_id'];
			$mapping_fields = $post_params['mapping_array'];
			$module_to_sync = $post_params['module'];
			$lead_owner = $post_params['owner'];
			$handle_duplicate_option = $post_params['duplicate'];
			$crm_users = $post_params['crm_users'];
			$old_user = $post_params['old_users'];
			$old_user_option_name = $post_params['users_option_name'];
		
			$sales_order_array = [];
			if(!empty($mapped_array['product_details'])){
				$sales_order_array = $mapped_array['product_details'];
			}

			if($mapping_fields){
				$data_to_store = [];
				foreach($mapping_fields as $local_crm_fieldname => $ninja_form_fieldname){
					if(isset($mapped_array[$ninja_form_fieldname])  && !empty($local_crm_fieldname)){
						$data_to_store[$local_crm_fieldname] = $mapped_array[$ninja_form_fieldname];
					}
				}
				
				if($lead_owner == 'RoundRobin'){
					$lead_owner = self::$joforce_instance->getRoundRobinBasedAssignedTo($crm_users , $old_user , $old_user_option_name);
				}
				$data_to_store['assigned_user_id'] = $lead_owner;	

				if($module_to_sync == 'SalesOrder'){
	
					if($mapped_array['order_status'] == 'wc-completed'){
						$product_status = 'Delivered';
					}
					elseif($mapped_array['order_status'] == 'wc-refunded' || $mapped_array['order_status'] == 'wc-failed'){
						$product_status = 'Cancelled';
					}
					elseif($mapped_array['order_status'] == 'wc-pending' || $mapped_array['order_status'] == 'wc-processing' || $mapped_array['order_status'] == 'wc-on-hold'){
						$product_status = 'Approved';
					}
					
					$data_to_store['sostatus'] = $product_status;
					$data_to_store['invoicestatus'] = $product_status;
					$data_to_store['product_details'] = $sales_order_array;
				}

				$CheckEmailResult = array();
				if($module_to_sync == 'Products'){
					if(isset($data_to_store['productname'])){
						$CheckEmailResult = self::$joforce_instance->checkProductPresent($module_to_sync, $data_to_store['productname'], 'whole_search');
					}
				}
				elseif($module_to_sync == 'SalesOrder'){
					if(isset($data_to_store['subject'])){
						$CheckEmailResult = self::$joforce_instance->checkOrderPresent($module_to_sync, $data_to_store['subject']);
					}
				}
				else{
					if(isset($data_to_store['email'])){
						if ($handle_duplicate_option == 'SKIP_BOTH') {
							$CheckEmailResult_Leads = self::$joforce_instance->checkEmailPresent('Leads', $data_to_store['email']);
							$CheckEmailResult_Contacts = self::$joforce_instance->checkEmailPresent('Contacts', $data_to_store['email']);
		
							if ($CheckEmailResult_Leads == 1 || $CheckEmailResult_Contacts == 1) {
								$CheckEmailResult = 1;
							}
							else{
								$CheckEmailResult = 2;
							}
		
						} else {
							$CheckEmailResult = self::$joforce_instance->checkEmailPresent($module_to_sync, $data_to_store['email']);
						}
					}
				}
				
				if(isset($data_to_store['emailoptout'])){
					if($data_to_store['emailoptout'] == 'yes' || $data_to_store['emailoptout'] == 'Yes' || $data_to_store['emailoptout'] == 'true'){
						$data_to_store['emailoptout'] = true;
					}
					elseif($data_to_store['emailoptout'] == 'no' || $data_to_store['emailoptout'] == 'No' || $data_to_store['emailoptout'] == 'false'){
						$data_to_store['emailoptout'] = false;
					}
				}

				if($handle_duplicate_option == 'UPDATE'){
					if($module_to_sync == 'Products'){
						$ids_present = end(self::$joforce_instance->result_pro_ids);
					}
					elseif($module_to_sync == 'SalesOrder'){
						$ids_present = end(self::$joforce_instance->result_order_ids);
					}
					else{
						$ids_present = end(self::$joforce_instance->result_ids);
					}
					
					if(!empty($ids_present) && $CheckEmailResult){	
						if($module_to_sync == 'Products'){
							$response = self::$joforce_instance->updateEcomRecord($module_to_sync, $data_to_store, $ids_present, $post_params['product_id']);
						}
						elseif($module_to_sync == 'SalesOrder'){
							$response = self::$joforce_instance->update_sales_order($post_params['product_id'], $data_to_store , $ids_present);
						}
						else{
							$response = self::$joforce_instance->updateRecord($module_to_sync, $data_to_store, $ids_present);
						}
					}else{
						$response = self::$instance->create_joforce_records($module_to_sync, $data_to_store, $post_params);
					}	
				}
				elseif($handle_duplicate_option == 'CREATE'){
					$response = self::$instance->create_joforce_records($module_to_sync, $data_to_store, $post_params);
				}
				elseif($handle_duplicate_option == 'SKIP' || $handle_duplicate_option == 'SKIP_BOTH'){
					if(($handle_duplicate_option == 'SKIP' && !$CheckEmailResult) || ($handle_duplicate_option == 'SKIP_BOTH' && $CheckEmailResult == 2)){
						$response = self::$instance->create_joforce_records($module_to_sync, $data_to_store, $post_params);
					}
					else{
						$response['result'] = 'Skipped';
						$response['method'] = 'Skipped';
					}
				}
				
				$status = $response['result'];
				if(!empty($inserted_id)){
					global $wpdb;
					$wpdb->update( 
						"{$wpdb->prefix}smack_ulb_databucket_meta",
						array('sync_status' => "$status"), 
						array('id' => $inserted_id), 
						array('%s'), 
						array('%d') 
					);
				}
				return $response['method'];
			}
		}
	}

    public function create_joforce_records($module_to_sync, $data_to_store, $post_params){
		if($module_to_sync == 'Products'){
			$response = self::$joforce_instance->createEcomRecord($module_to_sync, $data_to_store, $post_params['product_id']);
		}
		elseif($module_to_sync == 'SalesOrder'){
			$response = self::$joforce_instance->create_sales_order( $post_params['product_id'], $data_to_store );
		}
		else{
			$response = self::$joforce_instance->createRecord($module_to_sync, $data_to_store);
		}
		return $response;
	}

	public function wpulb_capture_woocom_checkout_submission_joforce($post_params = []){
		if(get_option('WPULB_ACTIVE_CRM_ADDON') == 'joforcecrm'){

			$mapped_array = $post_params['post_array'];
			$inserted_id = $post_params['inserted_id'];
			$mapping_fields = $post_params['mapping_array'];
			$module_to_sync = $post_params['module'];
			$lead_owner = $post_params['owner'];
			$handle_duplicate_option = $post_params['duplicate'];
			$crm_users = $post_params['crm_users'];
			$old_user = $post_params['old_users'];
			$old_user_option_name = $post_params['users_option_name'];
			$order_id = $post_params['order_id'];

			if($mapping_fields){	
				$data_to_store = [];
				
				foreach($mapping_fields as $local_crm_fieldname => $ninja_form_fieldname){
					if(isset($mapped_array[$ninja_form_fieldname])  && !empty($local_crm_fieldname)){
						$data_to_store[$local_crm_fieldname] = $mapped_array[$ninja_form_fieldname];
					}
				}
				
				if($lead_owner == 'RoundRobin'){
					$lead_owner = self::$joforce_instance->getRoundRobinBasedAssignedTo($crm_users , $old_user , $old_user_option_name);
				}
				$data_to_store['assigned_user_id'] = $lead_owner;	

				$CheckEmailResult = array();
				if(isset($data_to_store['email'])){
					if ($handle_duplicate_option == 'SKIP_BOTH') {
						$CheckEmailResult_Leads = self::$joforce_instance->checkEmailPresent('Leads', $data_to_store['email']);
						$CheckEmailResult_Contacts = self::$joforce_instance->checkEmailPresent('Contacts', $data_to_store['email']);
	
						if ($CheckEmailResult_Leads == 1 || $CheckEmailResult_Contacts == 1) {
							$CheckEmailResult = 1;
						}
						else{
							$CheckEmailResult = 2;
						}
	
					} else {
						$CheckEmailResult = self::$joforce_instance->checkEmailPresent($module_to_sync, $data_to_store['email']);
					}
				}
				
				if(isset($data_to_store['emailoptout'])){
					if($data_to_store['emailoptout'] == 'yes' || $data_to_store['emailoptout'] == 'Yes' || $data_to_store['emailoptout'] == 'true'){
						$data_to_store['emailoptout'] = true;
					}
					elseif($data_to_store['emailoptout'] == 'no' || $data_to_store['emailoptout'] == 'No' || $data_to_store['emailoptout'] == 'false'){
						$data_to_store['emailoptout'] = false;
					}
				}
				
				if($handle_duplicate_option == 'UPDATE'){
					$ids_present = end(self::$joforce_instance->result_ids);
					
					if(!empty($ids_present) && $CheckEmailResult){	
						$response = self::$joforce_instance->updateEcomRecord($module_to_sync, $data_to_store, $ids_present, $order_id);
					}else{
						$response = self::$joforce_instance->createEcomRecord($module_to_sync, $data_to_store, $order_id);
					}	
				}elseif($handle_duplicate_option == 'CREATE'){
					$response = self::$joforce_instance->createEcomRecord($module_to_sync, $data_to_store, $order_id);
				}
				elseif($handle_duplicate_option == 'SKIP' || $handle_duplicate_option == 'SKIP_BOTH'){
					if(($handle_duplicate_option == 'SKIP' && !$CheckEmailResult) || ($handle_duplicate_option == 'SKIP_BOTH' && $CheckEmailResult == 2)){
						$response = self::$joforce_instance->createEcomRecord($module_to_sync, $data_to_store, $order_id);
					}
					else{
						$response['result'] = 'Skipped';
						$response['method'] = 'Skipped';
					}
				}
		
				$status = $response['result'];
				if(!empty($inserted_id)){
					global $wpdb;
					$wpdb->update( 
						"{$wpdb->prefix}smack_ulb_databucket_meta",
						array('sync_status' => "$status"), 
						array('id' => $inserted_id), 
						array('%s'), 
						array('%d') 
					);
				}
				return $response['method'];
			}
		}
	}

	public function wpulb_capture_woocom_completed_submission_joforce($post_params = []){
		$module = $post_params['module'];
		$order_id = $post_params['order_id'];
		$crm_id = $post_params['crm_id'];
		$lead_no = $post_params['lead_no'];
		$contact_no = $post_params['contact_no'];

		if($module == 'Leads'){
			$response = self::$joforce_instance->convertLead( $module , $crm_id , $order_id , $lead_no , []);
			return $response['method'];
		}
	}

    public static function wpulb_verify_joforce_credentials($post_params){
		$config['app_url']=$post_params['api_endpoint_url'];
		$config['username']=$post_params['username'];
		$config['password']=$post_params['password'];
		$params = array(
				'username' => $post_params['username'],
				'password' => $post_params['password']
					);

		$joforce_auth_url = $post_params['api_endpoint_url'] . '/' . self::$joforce_instance->end_point . '/authorize';
		$response = self::$joforce_instance->call($joforce_auth_url, $params, 'POST');
	
		if (!empty($response) && $response['success'] == true)    {
			$config['access_token'] = $response['token'];
			update_option("WPULB_CONNECTED_JOFORCECRM_CREDENTIALS", $config);
			update_option('WPULB_ACTIVE_CRM_ADDON', sanitize_text_field($post_params['addon_slug'])); 
			$response = ['response' => '', 'message' => 'Credentials updated successfully', 'success' => true, 'status' => 200];
		} else {
			$response = ['response' => '', 'message' => 'Invalid credentials', 'success' => false, 'status' => 200];
		}
		echo  wp_json_encode($response);
		wp_die();
	}

    public static function wpulb_fetch_joforcecrm_users(){
		$crm_users = self::$joforce_instance->getUsersList();
		self::$instance->saveCRMUsers($crm_users);
	}

    public static function wpulb_fetch_joforcecrm_fields(){
		
		$allowed_modules = ['Leads', 'Contacts', 'Products', 'SalesOrder'];
		foreach($allowed_modules as $allowed_module){
			$module_fields = self::$joforce_instance->getCrmFields($allowed_module);
			self::$instance->saveCRMFields($module_fields, $allowed_module);
		}
	}

    public static function saveCRMFields($fields, $module){
		global $wpdb;
		$sequence = 1;
		$crm_type = 'joforcecrm';
		foreach($fields['fields'] as $field) {
	
			$field_type = isset($field['type']['name']) ? $field['type']['name'] : "";

			$field['field_values'] = null;
			if(! empty( $field['type']['picklistValues'] ) ) {
				$field['field_values'] = serialize($field['type']['picklistValues']);
			}
			
			if( isset($field['mandatory']) && $field['mandatory'] == 2 ){
				$field['mandatory'] = 1;
			}else{
				$field['mandatory'] = 0;
			}

			$field['base_model'] = null;
			if(isset($field['base_model'])){
				$field['base_model'] = $field['base_model'];
			}

			if($field['label']=='Date of Birth'){
				$field_type='date';
			}

			$has_already = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}smack_ulb_outcrm_field_manager WHERE field_name=%s AND module_type=%s AND crm_type=%s", $field['name'], $module, $crm_type ) );
			if(count($has_already) == 0  )
			{
				$wpdb->insert( "{$wpdb->prefix}smack_ulb_outcrm_field_manager" , array( 'field_name' => $field['name'], 'field_label' => $field['label'], 'field_type' => $field_type, 'field_values' => $field['field_values'], 'module_type' => $module, 'field_mandatory' => $field['mandatory'], 'crm_type' => $crm_type, 'field_sequence' => $sequence, 'base_model' => $field['base_model'], 'order_id'=> $field['order']) );
			}
			else {
				$wpdb->update( "{$wpdb->prefix}smack_ulb_outcrm_field_manager" , array( 'field_label' => $field['label'], 'field_type' => $field_type, 'field_values' => $field['field_values'], 'field_mandatory' => $field['mandatory'], 'field_sequence' => $sequence, 'base_model' => $field['base_model']) , array( 'field_name' => $field['name'] , 'module_type' => $module , 'crm_type' => $crm_type ) );
			}

			$sequence++;
		}
	}

	public static function saveCRMUsers($users){

		$crm_users = [];
		for($temp = 0; $temp < count($users['user_name']); $temp++){
			$crm_users[$temp]['user_name'] = $users['first_name'][$temp].' '.$users['last_name'][$temp];
			$crm_users[$temp]['user_id'] = $users['id'][$temp];
		}
		update_option('WPULB_USERS_CONNECTED', $crm_users);
	}
}
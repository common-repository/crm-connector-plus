<?php
/**
* CRM Connector Plus plugin file. 
*
* Copyright (C) 2010-2020, Smackcoders Inc - info@smackcoders.com 
*/

namespace Smackcoders\WPULB;

if ( ! defined( 'ABSPATH' ) )
exit; // Exit if accessed directly

class ThirdPartyForm{
	
	protected static $instance = null;
	protected static $joforcecrm_instance = null;

	/**
	 * __construct
	 *
	 * @return void
	 */
	public function __construct()
	{
		$this->plugin=WPULBPlugin::getInstance();
	}
	
	/**
	 * getInstance
	 *
	 * @return void
	 */
	public static function getInstance() {
		if ( null == self::$instance ) {
			self::$instance = new self;
			self::$joforcecrm_instance = JoForceCrm::getInstance();
		}
		return self::$instance;
	}

	public static function form_sync_during_submit($connected_addon, $active_crm, $activated_crm, $data_to_send){
		if($connected_addon == 'CRM'){
			
			if($active_crm == $activated_crm){
				switch($active_crm){
					case 'zohocrm':
						global $zoho_addon_instance;
                        $sync_result = $zoho_addon_instance->wpulb_capture_form_submission_zohocrm($data_to_send);
						break;
					
					case 'joforcecrm':
						//global $joforce_addon_instance;
                        $sync_result = self::$joforcecrm_instance->wpulb_capture_form_submission_joforce($data_to_send);
						break;

					case 'vtigercrm':
						global $vtiger_addon_instance;	
						$sync_result = $vtiger_addon_instance->wpulb_capture_form_submission_vtigercrm($data_to_send);
						break;

					case 'sugarcrm':
						global $sugar_addon_instance;
						$sync_result = $sugar_addon_instance->wpulb_capture_form_submission_sugarcrm($data_to_send);
						break;

					case 'freshsalescrm':
						global $freshsales_addon_instance;
                        $sync_result = $freshsales_addon_instance->wpulb_capture_form_submission_freshsalescrm($data_to_send);
						break;

					case 'salesforcecrm':
						global $salesforce_addon_instance;
                        $sync_result = $salesforce_addon_instance->wpulb_capture_form_submission_salesforcecrm($data_to_send);
						break;

					case 'suitecrm':
						global $suite_addon_instance;
						$sync_result = $suite_addon_instance->wpulb_capture_form_submission_suite($data_to_send);
						break;
				}
			}	
		}
		elseif($connected_addon == 'HELPDESK'){
			if($active_crm == $activated_crm){
				switch($active_crm){
					case 'freshdesk':
						global $freshdesk_addon_instance;
                        $sync_result = $freshdesk_addon_instance->wpulb_capture_form_submission_freshdesk($data_to_send);
						break;
						
					case 'vtigersupport':
						global $vtigersupport_addon_instance;
                        $sync_result = $vtigersupport_addon_instance->wpulb_capture_form_submission_vtigersupport($data_to_send);
						break;

					case 'zendesk':
						global $zendesk_addon_instance;
                        $sync_result = $zendesk_addon_instance->wpulb_capture_form_submission_zendesk($data_to_send);
						break;

					case 'zohosupport':
						global $zohosupport_addon_instance;
						$sync_result = $zohosupport_addon_instance->wpulb_capture_form_submission_zohosupport($data_to_send);
						break;
				}
			}
		}
		return $sync_result;
	}

	public static function get_crm_info($form_type , $form_id, $data_to_send){

		$other_form_info = get_option("WPULB_INFO_{$form_type}_{$form_id}");
		$crm_mapping = get_option("WPULB_CRM_MAPPING_{$form_type}_{$form_id}");
		$data_to_send['mapping_array'] = $crm_mapping;
		$data_to_send['module'] = $other_form_info['module'];
		$data_to_send['owner'] = $other_form_info['owner'];
		$data_to_send['duplicate'] = $other_form_info['duplicate_handle'];
		$data_to_send['crm_users'] = get_option('WPULB_USERS_CONNECTED');
		$data_to_send['old_users'] = get_option("WPULB_CRM_USER_SYNC_LEAD_OWNER_OLD");
		$data_to_send['users_option_name'] = 'WPULB_CRM_USER_SYNC_LEAD_OWNER_OLD';

		return $data_to_send;
	}

	public static function get_helpdesk_info($form_type , $form_id, $data_to_send){
		
		$other_form_info = get_option("WPULB_INFO_{$form_type}_{$form_id}");
		$help_mapping = get_option("WPULB_HELP_MAPPING_{$form_type}_{$form_id}");
		$data_to_send['mapping_array'] = $help_mapping;
		$data_to_send['module'] = $other_form_info['module'];
		$data_to_send['owner'] = $other_form_info['owner'];
		$data_to_send['duplicate'] = $other_form_info['duplicate_handle'];
		$data_to_send['crm_users'] = get_option('WPULB_HELP_USERS_CONNECTED');
		$data_to_send['old_users'] = get_option("WPULB_HELP_USER_SYNC_LEAD_OWNER_OLD");
		$data_to_send['users_option_name'] = 'WPULB_HELP_USER_SYNC_LEAD_OWNER_OLD';

		return $data_to_send;
	}

	public static function insert_to_databucket_table($source_name, $source_from, $shortcode_name, $form_id, $activated_crm, $data_to_store){
		global $wpdb;
		$wpdb->insert( 
			"{$wpdb->prefix}smack_ulb_databucket_meta", 
			array("source_name" => $source_name, "source_from" => $source_from, 'form_name' => $shortcode_name, 'form_id' => $form_id, "crm_type" => $activated_crm),
			array('%s', '%s', '%s', '%s', '%s')
		); 
		$inserted_id = $wpdb->insert_id;	
		foreach($data_to_store as $data_key => $data_value){
			if(!empty($data_key) && !empty($data_value)){
				$get_field_type = $wpdb->get_var($wpdb->prepare( "SELECT field_type FROM {$wpdb->prefix}smack_ulb_manage_fields WHERE field_name = %s", $data_key));
				
				if($get_field_type == 'Date'){
					$data_value = date("Y-m-d", strtotime($data_value));
				}

				if(is_array($data_value)){
					$data_value = serialize($data_value);
				}
				
				$wpdb->update( 
					"{$wpdb->prefix}smack_ulb_databucket_meta",
					array("$data_key" => $data_value), 
					array('id' => $inserted_id), 
					array('%s'), 
					array('%d') 
				);
			}
		}
		return $inserted_id;
	}

	public static function update_status_to_databucket_table($inserted_id , $status){
		global $wpdb;
		$wpdb->update( 
			"{$wpdb->prefix}smack_ulb_databucket_meta",
			array('sync_status' => "$status"), 
			array('id' => $inserted_id), 
			array('%s'), 
			array('%d') 
		);	
	}

	public static function get_user_woo_info($addon , $sync_to ,$data_to_send){
		
		if($sync_to == 'USER'){
			$get_info = get_option("WPULB_USER_INFO");
		}
		elseif($sync_to == 'WOOCOM'){
			$get_info = get_option("WPULB_WOOCOM_INFO");
		}

		$data_to_send['module'] = $get_info['module'];
		$data_to_send['owner'] = $get_info['owner'];
		$data_to_send['duplicate'] = $get_info['duplicate_handle'];

		$data_to_send['old_users'] = get_option("WPULB_{$sync_to}_SYNC_LEAD_OWNER_OLD_{$addon}");
		$data_to_send['users_option_name'] = "WPULB_{$sync_to}_SYNC_LEAD_OWNER_OLD_{$addon}";
		return $data_to_send;
	}

	public static function woocommerce_sync_during_submit($connected_addon, $active_crm, $data_to_send , $status){
		if($connected_addon == 'CRM'){	
			switch($active_crm){
				case 'zohocrm':
					global $zoho_addon_instance;	
					if($status == 'order_processing'){
						$zoho_addon_instance->wpulb_capture_woocom_checkout_submission_zohocrm($data_to_send);
					}
					elseif($status == 'completed'){
						$zoho_addon_instance->wpulb_capture_woocom_completed_submission_zohocrm($data_to_send);
					}	
					break;
				
				case 'joforcecrm':
					//global $joforce_addon_instance;
					if($status == 'order_processing'){	
						self::$joforcecrm_instance->wpulb_capture_woocom_checkout_submission_joforce($data_to_send);
					}
					elseif($status == 'completed'){
						self::$joforcecrm_instance->wpulb_capture_woocom_completed_submission_joforce($data_to_send);
					}	
					break;

				case 'vtigercrm':
					global $vtiger_addon_instance;
					if($status == 'order_processing'){	
						$vtiger_addon_instance->wpulb_capture_woocom_checkout_submission_vtigercrm($data_to_send);
					}
					elseif($status == 'completed'){
						$vtiger_addon_instance->wpulb_capture_woocom_completed_submission_vtigercrm($data_to_send);
					}	
					break;

				case 'sugarcrm':
					global $sugar_addon_instance;
					if($status == 'order_processing'){	
						$sugar_addon_instance->wpulb_capture_woocom_checkout_submission_sugarcrm($data_to_send);
					}
					elseif($status == 'completed'){
						$sugar_addon_instance->wpulb_capture_woocom_completed_submission_sugarcrm($data_to_send);
					}	
					break;

				case 'freshsalescrm':
					global $freshsales_addon_instance;
					if($status == 'order_processing'){
						$freshsales_addon_instance->wpulb_capture_woocom_checkout_submission_freshsalescrm($data_to_send);
					}
					elseif($status == 'completed'){
						$freshsales_addon_instance->wpulb_capture_woocom_completed_submission_freshsalescrm($data_to_send);
					}	
					break;

				case 'salesforcecrm':
					global $salesforce_addon_instance;
					if($status == 'order_processing'){
						$salesforce_addon_instance->wpulb_capture_woocom_checkout_submission_salesforcecrm($data_to_send);
					}
					elseif($status == 'completed'){
						$salesforce_addon_instance->wpulb_capture_woocom_completed_submission_salesforcecrm($data_to_send);
					}	
					break;

				case 'suitecrm':
					global $suite_addon_instance;
					if($status == 'order_processing'){
						$suite_addon_instance->wpulb_capture_woocom_checkout_submission_suite($data_to_send);
					}
					elseif($status == 'completed'){
						$suite_addon_instance->wpulb_capture_woocom_completed_submission_suite($data_to_send);
					}	
					break;
			}
		}
		elseif($connected_addon == 'HELPDESK'){
			switch($active_crm){
				case 'freshdesk':
					global $freshdesk_addon_instance;
					if($status == 'order_processing'){	
						$freshdesk_addon_instance->wpulb_capture_woocom_checkout_submission_freshdesk($data_to_send);
					}
					break;

				case 'vtigersupport':
					global $vtigersupport_addon_instance;
					if($status == 'order_processing'){	
						$vtigersupport_addon_instance->wpulb_capture_woocom_checkout_submission_vtigersupport($data_to_send);
					}
					break;

				case 'zendesk':
					global $zendesk_addon_instance;	
					if($status == 'order_processing'){	
						$zendesk_addon_instance->wpulb_capture_woocom_checkout_submission_zendesk($data_to_send);
					}	
					break;

				case 'zohosupport':
					global $zohosupport_addon_instance;
					if($status == 'order_processing'){
						$zohosupport_addon_instance->wpulb_capture_woocom_checkout_submission_zohosupport($data_to_send);
					}
					break;
			}
		}
	}

	public static function change_array_to_asso_array($get_array){
		$crm_fieldname_fieldlabel = [];
		$temp = 0;
		foreach($get_array as $get_name => $get_label){
			$crm_fieldname_fieldlabel[$temp]['label'] = $get_label;
			$crm_fieldname_fieldlabel[$temp]['value'] = $get_name;
			$crm_fieldname_fieldlabel[$temp]['mandatory'] = false;
			$temp++;
		}
		return $crm_fieldname_fieldlabel; 
	}

	public static function change_asso_array_to_array($get_asso_array){
		$crm_fieldname_fieldlabel = [];
        foreach($get_asso_array as $crm_field){
            $crm_fieldname_fieldlabel[$crm_field['value']] = $crm_field['label'];
        }
        return $crm_fieldname_fieldlabel; 
	}
}

global $leads_third_party_forms_instance;
$leads_third_party_forms_instance = new ThirdPartyForm();
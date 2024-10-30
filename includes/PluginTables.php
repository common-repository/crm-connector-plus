<?php
/**
* CRM Connector Plus plugin file. 
*
* Copyright (C) 2010-2020, Smackcoders Inc - info@smackcoders.com 
*/

namespace Smackcoders\WPULB;

if ( ! defined( 'ABSPATH' ) )
exit; // Exit if accessed directly

/**
  pluginTable Plugin Class
 **/
class PluginTables{
	protected static $instance, $manageFields = null;

	public function __construct()
	{
		$this->plugin = WPULBPlugin::getInstance();
	}

	/**
	  pluginTable - Instances
	 **/
	public static function getInstance()
	{
		if (null == self::$instance) {
			self::$instance = new self;
			ManageFields::getInstance();
			self::$instance->doHooks();
		}
		return self::$instance;
	}

	public function doHooks(){
		add_action('wp_ajax_wpulb_delete_crm_config_data',array($this,'wpulb_delete_crm_config_data'));
		add_action('wp_ajax_wpulb_delete_help_config_data',array($this,'wpulb_delete_help_config_data'));
	}

	/**
	 * To create plugin tables
	 */
	public static function createPluginTables()
	{
		global $wpdb;
		$prefix=$wpdb->prefix;
		$wpdb->query("CREATE TABLE IF NOT EXISTS `{$prefix}smack_ulb_shortcode_manager` (
			`shortcode_id` int(11) NOT NULL AUTO_INCREMENT,
			`shortcode_name` varchar(255) NOT NULL,
			`old_shortcode_name` varchar(255) DEFAULT NULL,
			`form_type` varchar(15) NOT NULL,
			`assigned_to` varchar(200) NOT NULL,
			`error_message` text NOT NULL,
			`success_message` text NOT NULL,
			`submit_count` int(10) NOT NULL DEFAULT '0',
			`success_count` int(10) NOT NULL DEFAULT '0',
			`failure_count` int(10) NOT NULL DEFAULT '0',
			`is_redirection` tinyint(1) NOT NULL,
			`url_redirection` varchar(255) NOT NULL,
			`duplicate_handling` varchar(10) NOT NULL DEFAULT 'none',
			`google_captcha` tinyint(1) NOT NULL,
			`module` varchar(25) NOT NULL,
			`Round_Robin` varchar(50) NOT NULL,
			`crm_type` varchar(25) NOT NULL,
			`status` enum('Draft','Created','Updated') DEFAULT 'Draft',
			`form_id` varchar(25) DEFAULT NULL,
			`created_at` timestamp NOT NULL,
			`updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (`shortcode_id`)
		  )");
			
		$wpdb->query("CREATE TABLE IF NOT EXISTS `{$prefix}smack_ulb_submitted_forms` (
					`id` int(11) NOT NULL AUTO_INCREMENT,
					`form_id` int(11) NOT NULL,
					`shortcode_name` varchar(10) NOT NULL,
					`created_at` datetime,
					`module` varchar(20) NOT NULL,
					PRIMARY KEY (`id`)
					)
				");
		$wpdb->query("CREATE TABLE IF NOT EXISTS `{$prefix}smack_ulb_ecom_info` (
			id int(6) unsigned NOT NULL AUTO_INCREMENT,
			   crmid varchar(100) DEFAULT NULL,
			   crm_name varchar(100) NOT NULL,
			   wp_user_id varchar(100) NOT NULL,
			   is_user int(30) NOT NULL,
			   lead_no varchar(100) DEFAULT NULL,
			   product_id varchar(100) DEFAULT NULL,
			   contact_no varchar(100) DEFAULT NULL,
			   order_id varchar(100) DEFAULT NULL,
			   sales_orderid varchar(100) DEFAULT NULL,		
			   PRIMARY KEY (`id`)
				   )
				");

		$wpdb->query("CREATE TABLE IF NOT EXISTS `{$prefix}smack_ulb_outcrm_field_manager` (
				`field_id` int(11) NOT NULL AUTO_INCREMENT,
				`field_name` varchar(50) NOT NULL,
				`field_label` varchar(50) NOT NULL,
				`field_type` varchar(20) NOT NULL,
				`field_values` longtext,
				`field_default` text,
				`module_type` varchar(20) NOT NULL,
				`field_mandatory` varchar(10) NOT NULL,
				`crm_type` varchar(25) NOT NULL,
				`field_sequence` int(10) NOT NULL,
				`base_model` varchar(20) DEFAULT NULL,
				`order_id` int(11) DEFAULT NULL,
				PRIMARY KEY (`field_id`)
				)
				");
				
		$wpdb->query("CREATE TABLE IF NOT EXISTS `{$prefix}smack_ulb_formrelation` (
			`id` int(6) unsigned NOT NULL AUTO_INCREMENT,
			`shortcode` varchar(30) NOT NULL,
			`thirdparty` varchar(30) NOT NULL,
			`thirdpartyid` int(50) DEFAULT NULL,
			PRIMARY KEY (`id`)
				)
				");

		//create table for form field relations
		$wpdb->query("CREATE TABLE IF NOT EXISTS `{$prefix}smack_ulb_thirdpartyform_fieldrelation` (
					`id` int(6) unsigned NOT NULL AUTO_INCREMENT,
					`smackshortcodename` varchar(30) NOT NULL,
					`smackfieldid` int(20) DEFAULT NULL,
					`smackfieldslable` varchar(30) NOT NULL,
					`thirdpartypluginname` varchar(30) NOT NULL,
					`thirdpartyformid` int(50) DEFAULT NULL,
					`thirdpartyfieldids` varchar(50) DEFAULT NULL,
					PRIMARY KEY (`id`)
					)
				");

		$wpdb->query("CREATE TABLE IF NOT EXISTS `{$prefix}smack_ulb_group_manager` (
					`id` int(11) NOT NULL AUTO_INCREMENT,
					`group_name` varchar(255) NOT NULL,
					`sequence` tinyint(3) NOT NULL,
					`created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
					`updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
					PRIMARY KEY (`id`),
					UNIQUE KEY unique_group_name (group_name)
				)
		  	");
		
		$wpdb->query("CREATE TABLE IF NOT EXISTS `{$prefix}smack_ulb_manage_fields` (
					`id` int(11) NOT NULL AUTO_INCREMENT,
					`field_label` varchar(255) NOT NULL,
					`field_name` varchar(255) NOT NULL,
					`field_type` enum('Text','Integer','Email','Select','Multi-Select','Date','DateTime','Decimal','Boolean','File','Phone','Currency','Hidden') NOT NULL,
					`is_mandatory` enum('Yes','No') NOT NULL DEFAULT 'No',
					`is_custom_field` enum('Yes','No') NOT NULL DEFAULT 'No',
					`field_default_value` varchar(255) DEFAULT NULL,
					`group_id` int(10) DEFAULT 1,
					`sequence_in_group` tinyint(3) NOT NULL,
					`is_active` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
					`created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
					`updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
					PRIMARY KEY (`id`),
					UNIQUE KEY unique_field_name (field_name),
					KEY `{$prefix}smack_ulb_group_manager_group_id_foreign` (`group_id`),
					CONSTRAINT `{$prefix}smack_ulb_group_manager_group_id_foreign` FOREIGN KEY (`group_id`) REFERENCES `{$prefix}smack_ulb_group_manager` (`id`) ON DELETE CASCADE
					) 
				");
		
		$wpdb->query("CREATE TABLE IF NOT EXISTS `{$prefix}smack_ulb_manage_fields_picklist_values` (
					`id` int(11) NOT NULL AUTO_INCREMENT,
					`field_id` int(11) NOT NULL,
					`picklist_value` varchar(255) NOT NULL,
					PRIMARY KEY (`id`),
					KEY `{$prefix}smack_ulb_manage_fields_picklist_values_field_id_foreign` (`field_id`),
					CONSTRAINT `{$prefix}smack_ulb_manage_fields_picklist_values_field_id_foreign` FOREIGN KEY (`field_id`) REFERENCES `{$prefix}smack_ulb_manage_fields` (`id`) ON DELETE CASCADE
				  ) 
				");

		$wpdb->query("CREATE TABLE IF NOT EXISTS `{$prefix}smack_ulb_local_crm_leads` (
						`id` int(11) NOT NULL AUTO_INCREMENT,
						`source_name` varchar(255) NOT NULL,
						`source_from` varchar(255) NOT NULL,
						`form_name` varchar(255) NOT NULL,
						`form_id` varchar(255) DEFAULT NULL,
						`created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
						PRIMARY KEY (`id`)
					)
				");
		
		$wpdb->query("CREATE TABLE IF NOT EXISTS `{$prefix}smack_ulb_addon_crm_leads` (
						`id` int(11) NOT NULL AUTO_INCREMENT,
						`local_crm_lead_id` int(11) NOT NULL,
						`sync_status` enum('Success','Failure') NOT NULL DEFAULT 'Success',
						`crm_name` enum('joforcecrm','vtigercrm','salesforcecrm','freshsalescrm','sugarcrm','suitecrm','zohocrm') NOT NULL,
						`lead_status` enum('Created','Updated','Skipped') NOT NULL DEFAULT 'Created',
						`initial_synced_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
						`last_attempted_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
						PRIMARY KEY (`id`),
						KEY `{$prefix}smack_ulb_addon_crm_leads_local_crm_lead_id_foreign` (`local_crm_lead_id`),
						CONSTRAINT `{$prefix}smack_ulb_addon_crm_leads_local_crm_lead_id_foreign` FOREIGN KEY (`local_crm_lead_id`) REFERENCES `{$prefix}smack_ulb_local_crm_leads` (`id`) ON DELETE CASCADE
					) 
				");

		$wpdb->query("CREATE TABLE IF NOT EXISTS `{$prefix}smack_ulb_addon_crm_lead_exception` (
						`id` int(11) NOT NULL AUTO_INCREMENT,
						`addon_crm_lead_id` int(11) NOT NULL,
						`exception_if_any` text NOT NULL,
						PRIMARY KEY (`id`),
						KEY `{$prefix}smack_ulb_addon_crm_lead_exception_addon_crm_lead_id_foreign` (`addon_crm_lead_id`),
						CONSTRAINT `{$prefix}smack_ulb_addon_crm_lead_exception_addon_crm_lead_id_foreign` FOREIGN KEY (`addon_crm_lead_id`) REFERENCES `{$prefix}smack_ulb_addon_crm_leads` (`id`) ON DELETE CASCADE
					)
				");

		$wpdb->query("CREATE TABLE IF NOT EXISTS `{$prefix}smack_ulb_databucket_meta` (
			`id` int(11) NOT NULL AUTO_INCREMENT,
			`source_name` varchar(255) NOT NULL,
			`source_from` varchar(255) NOT NULL,
			`form_name` TEXT NOT NULL,
			`form_id` varchar(255) DEFAULT NULL,
			`created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
			`crm_type` enum('DataBucket','JoforceCRM','SuiteCRM','ZohoCRM','SalesforceCRM','SugarCRM','VtigerCRM','FreshsalesCRM','Zendesk','Freshdesk','ZohoSupport','VtigerSupport') NOT NULL,
			`sync_status` varchar(255) NOT NULL,
			`first_name` TEXT DEFAULT NULL,
			`last_name` TEXT DEFAULT NULL,
			`email` TEXT DEFAULT NULL,
			`street` TEXT DEFAULT NULL,
			`city` TEXT DEFAULT NULL,
			`state` varchar(255) DEFAULT NULL,
			`country` varchar(255) DEFAULT NULL,
			`zipcode` varchar(255) DEFAULT NULL,
			PRIMARY KEY (`id`)
		)
		");

		$wpdb->query("CREATE TABLE IF NOT EXISTS `{$prefix}smack_ulb_databucket_info` (
			`id` int(11) NOT NULL AUTO_INCREMENT,
			`data_mapping` TEXT DEFAULT NULL,
			`crm_mapping` TEXT DEFAULT NULL,
			`module` TEXT DEFAULT NULL,
			`owner` TEXT DEFAULT NULL,
			`duplicate` TEXT DEFAULT NULL,
			`crm_users` TEXT DEFAULT NULL,
			`old_users` TEXT DEFAULT NULL,
			`connected_addon` varchar(255) DEFAULT NULL,
			`activated_addon` varchar(255) DEFAULT NULL,
			`field_id` int(11) NOT NULL,
			PRIMARY KEY (`id`)
		)
		");

		add_option( 'WPULB_ACTIVE_CRM_ADDON', 'wpulb_crm' );
		add_option( 'WPULB_ACTIVE_HELPDESK_ADDON', 'wpulb_crm' );
		add_option('WPULB_SCHEDULE_STATUS', 'off');
		add_option('WPULB_USER_ONE_TIME_MANUAL_SYNC', 'on');
		add_option('WPULB_WOOCOM_PRODUCT_ONE_TIME_MANUAL_SYNC', 'on');
		add_option('WPULB_ENABLE_CAPTCHA', 'off');

		if($activated_count = get_option('WPULB_ACTIVATED_COUNT')){
			add_option('WPULB_ACTIVATED_COUNT', $activated_count + 1);
		}else{
			add_option('WPULB_ACTIVATED_COUNT', 1);
		}

		if($deactivated_count = get_option('WPULB_DEACTIVATED_COUNT')){
			add_option('WPULB_DEACTIVATED_COUNT', $deactivated_count + 1);
		}else{
			add_option('WPULB_DEACTIVATED_COUNT', 1);
		}

		if(!$activated_count){
			$current_time = current_time('mysql');
			$wpdb->insert( 
				"{$prefix}smack_ulb_group_manager", 
				array("group_name" => 'Basic Information', 'created_at' => $current_time, 'updated_at' => $current_time),
				array('%s', '%s', '%s')
			);
			ManageFields::createDefaultFields();
		}

	}

	/**
	 * Reset Configuration
	 */
	public function wpulb_delete_crm_config_data(){
		
		global $wpdb;
		$recent_crm = get_option('WPULB_ACTIVE_CRM_ADDON');
		delete_option("WPULB_CONNECTED_{$recent_crm}_CREDENTIALS");

		update_option('WPULB_ACTIVE_CRM_ADDON', 'wpulb_crm');
		delete_option('WPULB_USERS_CONNECTED');
		delete_option('WPULB_CRM_USER_SYNC_LEAD_OWNER_OLD');

		/* delete woocommerce sync configuration */
		delete_option('WPULB_WOOCOM_CRM_MAPPING');
		delete_option("WPULB_WOOCOM_INFO");
		$woocom_update_info = [];
		$woocom_update_info['configured_addon'] = 'DATA_BUCKET_ONLY';
		update_option("WPULB_WOOCOM_INFO", $woocom_update_info);

		/* delete user sync configuration */
		delete_option('WPULB_USER_CRM_MAPPING');
		delete_option("WPULB_USER_INFO");
		$user_update_info = [];
		$user_update_info['configured_addon'] = 'DATA_BUCKET_ONLY';
		update_option("WPULB_USER_INFO", $user_update_info);
		
		$get_info = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}options WHERE option_name LIKE '%WPULB_INFO%' ");
		foreach($get_info as $value){
			$get_option_name = $wpdb->get_var($wpdb->prepare("SELECT option_name FROM {$wpdb->prefix}options WHERE option_id = %d", $value->option_id));
			
			$in_value = unserialize($value->option_value);

			$in_value['connected_crm'] = 'wpulb_crm';
			$in_value['configured_addon'] = 'DATA_BUCKET_ONLY';

			if(isset($in_value['module']) || isset($in_value['owner']) || isset($in_value['duplicate_handle'])){
				unset($in_value['module']);
				unset($in_value['owner']);
				unset($in_value['duplicate_handle']);
			}
			update_option($get_option_name , $in_value);
		}

		$wpdb->query("DELETE FROM {$wpdb->prefix}options WHERE option_name LIKE '%WPULB_CRM_MAPPING%' ");
		
		$get_crm_type = $wpdb->get_results("SELECT crm_type, shortcode_id FROM {$wpdb->prefix}smack_ulb_shortcode_manager ");
		$crm_array = array('vtigercrm', 'sugarcrm', 'salesforcecrm', 'joforcecrm', 'freshsalescrm', 'zohocrm', 'suitecrm');
		
		foreach($get_crm_type as $get_crm){
			if(in_array($get_crm->crm_type , $crm_array)){
				$wpdb->update( $wpdb->prefix . 'smack_ulb_shortcode_manager' , 
					array( 
						'crm_type' => 'wpulb_crm',
						'module' => ''
					) , 
					array( 'shortcode_id' => $get_crm->shortcode_id 
					) 
				);
			}
		}

		echo wp_json_encode(['response' => '', 'message' => 'CRM Configuration Resetted Successfully', 'status' => 200, 'success' => true]);  
    wp_die();	
	}

	public function wpulb_delete_help_config_data(){
		global $wpdb;
		$recent_help = get_option('WPULB_ACTIVE_HELPDESK_ADDON');
		delete_option("WPULB_CONNECTED_{$recent_help}_CREDENTIALS");

		update_option('WPULB_ACTIVE_HELPDESK_ADDON', 'wpulb_crm');
		delete_option('WPULB_HELP_USERS_CONNECTED');
		delete_option('WPULB_HELP_USER_SYNC_LEAD_OWNER_OLD');

		/* delete woocommerce sync configuration */
		delete_option('WPULB_WOOCOM_HELP_MAPPING');
		delete_option("WPULB_WOOCOM_INFO");
		$woocom_update_info = [];
		$woocom_update_info['configured_addon'] = 'DATA_BUCKET_ONLY';
		update_option("WPULB_WOOCOM_INFO", $woocom_update_info);

		/* delete user sync configuration */
		delete_option('WPULB_USER_HELP_MAPPING');
		delete_option("WPULB_USER_INFO");
		$user_update_info = [];
		$user_update_info['configured_addon'] = 'DATA_BUCKET_ONLY';
		update_option("WPULB_USER_INFO", $user_update_info);
		
		$get_info = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}options WHERE option_name LIKE '%WPULB_INFO%' ");
		foreach($get_info as $value){
			$get_option_name = $wpdb->get_var($wpdb->prepare( "SELECT option_name FROM {$wpdb->prefix}options WHERE option_id = %d", $value->option_id));
			
			$in_value = unserialize($value->option_value);

			$in_value['connected_help'] = 'wpulb_crm';
			$in_value['configured_addon'] = 'DATA_BUCKET_ONLY';

			if(isset($in_value['module']) || isset($in_value['owner']) || isset($in_value['duplicate_handle'])){
				unset($in_value['module']);
				unset($in_value['owner']);
				unset($in_value['duplicate_handle']);
			}
			update_option($get_option_name , $in_value);
		}

		$wpdb->query("DELETE FROM {$wpdb->prefix}options WHERE option_name LIKE '%WPULB_HELP_MAPPING%' ");
		
		$get_crm_type = $wpdb->get_results("SELECT crm_type, shortcode_id FROM {$wpdb->prefix}smack_ulb_shortcode_manager ");	
		$help_array = array('vtigersupport', 'zendesk', 'freshdesk', 'zohosupport');
		
		foreach($get_crm_type as $get_crm){
			if(in_array($get_crm->crm_type ,$help_array)){
				$wpdb->update( $wpdb->prefix . 'smack_ulb_shortcode_manager' , 
					array( 
						'crm_type' => 'wpulb_crm',
						'module' => ''
					) , 
					array( 'shortcode_id' => $get_crm->shortcode_id 
					) 
				);
			}
		}

		echo wp_json_encode(['response' => '', 'message' => 'Helpdesk Configuration Resetted Successfully', 'status' => 200, 'success' => true]);  
    wp_die();	
	}
}

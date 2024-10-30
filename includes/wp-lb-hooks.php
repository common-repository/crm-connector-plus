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

global $wpulb_plugin_ajax_hooks;

$wpulb_plugin_ajax_hooks = [
		'wpulb_document_ready', // Init call
		'wpulb_active_addons',
		'wpulb_addon_list',
		'wpulb_list_group',
		'wpulb_show_create_form_step2',
		'wpulb_show_create_form_step1',
		'wpulb_save_native_form_mapping',
		'wpulb_save_group',
		'wpulb_delete_group',
		'wpulb_show_mapping_section',
		'wpulb_get_group', 
		'wpulb_get_active_addons_for_config',
		'wpulb_save_thirdparty_form_mapping',
		'wpulb_save_configuration',
		'wpulb_change_status',
		'wpulb_delete_field',
		'wpulb_get_crm_fields',
		'wpulb_get_local_crm_field_list',
		'wpulb_create_local_crm_custom_field',
		'wpulb_save_local_crm_field',
		'wpulb_get_form_list',
		'wpulb_delete_crm_config_data',
		'wpulb_user_mapping',
		'wpulb_save_user_mapping',
		'wpulb_zohoauth',
		'wpulb_get_submitted_form_list',
		'wpulb_get_submitted_form_details',
		'wpulb_get_active_help_addons_for_config',
		'wpulb_reorder_native_form_mapping',
		'wpulb_reorder_default_with_crm',
		'wpulb_get_configured_crm',
		'wpulb_edit_form',
		'wpulb_edit_form_sorting',
		'wpulb_edit_form_mapping',
		'wpulb_salesauth',
		'wpulb_list_filters',
		'wpulb_filter_details',
		'wpulb_delete_form',
		'wpulb_save_help_configuration',
		'wpulb_get_configured_helpdesk',
		'wpulb_delete_help_config_data',
		'wpulb_zohosupport_auth',
		'wpulb_user_display',
		'wpulb_user_addon_display',
		'wpulb_get_configured_user',
		'wpulb_get_manual_sync',
		'wpulb_woocommerce_addon_display',
		'wpulb_woocommerce_mapping',
		'wpulb_save_woocommerce_mapping',
		'wpulb_get_configured_woocommerce',
		'wpulb_one_time_manual_sync',
		'wpulb_form_settings',
		'wpulb_form_log_options',
		'wpulb_get_configured_form_settings',
		'wpulb_default_form_settings',
		'wpulb_get_schedule_status',
		'wpulb_get_schedule_configuration',
		'wpulb_install_plugins',
		'wpulb_get_submitted_form_list_1',
		'wpulb_get_submitted_form_list_2',
		'wpulb_get_callback_url',
		'wpulb_create_new_list',
		'wpulb_display_lists',
		'wpulb_save_or_update_list',
		'wpulb_edit_list',
		'wpulb_delete_list',
		'wpulb_display_view',
		'wpulb_woocom_product_sync',
		'wpulb_woocom_product_one_time_manual_sync',
		'wpulb_buynow_click',
		'wpulb_send_licensekey',
		'wpulb_license_tab',
		'wpulb_verify_license',
		'wpulb_send_billing_details',
		'wpulb_get_user_details_checkout',
		'wpulb_available_databucket_forms',
		'wpulb_installed_addons',
		'wpulb_migrate_existing_forms',
		'wpulb_check_woocommerce_active',
		'wpulb_get_configured_migration',
		'wpulb_get_licensekey_details',
		'wpulb_licensekey_details_tab'
	];

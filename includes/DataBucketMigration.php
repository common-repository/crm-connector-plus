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

/**
 * Class DataBucketMigration
 * @package Smackcoders\WPULB
 */
class DataBucketMigration{
    protected static $instance = null;
    protected static $third_party_instance = null;
    
    /**
     * DataBucketMigration constructor.
     */
	public function __construct()
	{
		$this->plugin = WPULBPlugin::getInstance();
	}

    /**
     * DataBucketMigration Instances.
     * @return null|Migration
     */
	public static function getInstance() {
		if ( null == self::$instance ) {
            self::$instance = new self;
            self::$instance->doHooks();
            self::$third_party_instance = ThirdPartyForm::getInstance(); 
		}
		return self::$instance;
    }

    public function doHooks(){
        add_action('wp_ajax_wpulb_available_databucket_forms', array($this, 'availableDatabucketForms'));
        add_action('wp_ajax_wpulb_migrate_existing_forms', array($this, 'migrateExistingForms'));
        add_action('wp_ajax_wpulb_get_configured_migration', array($this, 'getConfiguredMigration'));
    }

    public static function getConfiguredMigration(){
        $is_migration = false;
        $get_migration_status = get_option("WPULB_DATABUCKET_MIGRATION");
        if($get_migration_status == 'on'){
            $is_migration = true;
        }
        echo wp_json_encode(['response' => ['migration_active' => $is_migration], 'message' => '', 'status' => 200, 'success' => true]);
		wp_die();
    }

    public static function availableDatabucketForms(){
        global $wpdb;
        global $form_type_array;       
        $need_migration = isset($_POST['migration']) ? sanitize_text_field($_POST['migration']) : '';

        if($need_migration == 'true'){
            update_option("WPULB_DATABUCKET_MIGRATION" , 'on');

            $get_databucket_forms = $wpdb->get_results($wpdb->prepare( "SELECT distinct(form_name), source_name, form_id FROM {$wpdb->prefix}smack_ulb_databucket_meta WHERE sync_status = %s AND form_name != %s", 'CRM/Helpdesk not configured', 'WooCommerce'), ARRAY_A);        
          
            $databucket_forms = [];
            $temp = 0;
            foreach($get_databucket_forms as $value){
                $form_name = $value['form_name'];
                $form_id = $value['form_id'];
                
                if(array_key_exists($value['source_name'] , $form_type_array)){
                    $form_type = $form_type_array[$value['source_name']];
                    if($form_type == 'DEFAULT_FORM'){
                        $form_id = $form_name;
                    }
                }
                
                $databucket_forms[$temp] = ['label' => $form_name, 'value' => $form_id, 'type' => $form_type];
                $temp++;
            }
            echo wp_json_encode(['response' => ['databucket_forms' => $databucket_forms ], 'message' => 'All DataBucket Forms', 'status' => 200, 'success' => true]);
        }
        elseif($need_migration == 'false'){
            update_option("WPULB_DATABUCKET_MIGRATION" , 'off');
            echo wp_json_encode(['response' => ['databucket_forms' => [] ], 'message' => 'All DataBucket Forms', 'status' => 200, 'success' => true]);
        }

        wp_die();
    }

    public static function migrateExistingForms(){
        global $wpdb;
        global $form_type_array;
        $available_forms = wp_unslash(sanitize_text_field($_POST['forms']) ); 
        //$available_forms = str_replace("\\" , '' , $_POST['forms']);
        $available_forms = json_decode($available_forms, True);

        if(count($available_forms) == 0){
            echo wp_json_encode([
                'response' => '',
                'message' => "Please select any form before Sync", 
                'status' => 200, 
                'success' => false
            ]);
            wp_die();
        }
       
        $not_mapped = '';
        $get_form_ids = '';
        $get_total_count = 0;
        
        for($i = 0 ; $i < count($available_forms) ; $i++){	
			$form_name = $available_forms[$i]['form_name'];
			$form_id = $available_forms[$i]['form_id'];
            $form_type = $available_forms[$i]['type'];
            
            $get_form_ids .= "'".$form_name."'" . ',';
            
            if($form_type == 'DFEAULT_FORM'){
                $form_id = $form_name;
            }
          
            $get_crm_mapping = get_option("WPULB_CRM_MAPPING_{$form_type}_{$form_id}");
            if(empty($get_crm_mapping)){
                $not_mapped .= $form_name . ', ';
            }
           
            $form_types = array_search($form_type , $form_type_array);
            $get_total_count += $wpdb->get_var($wpdb->prepare( "SELECT count(*) FROM {$wpdb->prefix}smack_ulb_databucket_meta WHERE source_name = %s AND form_name = %s AND sync_status = %s", $form_types, $form_name, 'CRM/Helpdesk not configured'));
        }

        $not_mapped = rtrim($not_mapped , ', ');
        if(!empty($not_mapped)){
            
            echo wp_json_encode([
                'response' => '',
                'message' => "Please configure mapping for following forms -> $not_mapped", 
                'status' => 200, 
                'success' => false
            ]);
            wp_die();

        }else{
            $wp_start = intval($_POST['wp_start']) ;
            $wp_offset = intval($_POST['wp_offset']);
            $forms_synced_count = intval( $_POST['synced_count']);

            $get_form_ids = rtrim($get_form_ids , ',');
            $get_id = $wpdb->get_results("SELECT id FROM {$wpdb->prefix}smack_ulb_databucket_meta WHERE form_name IN ($get_form_ids) limit $wp_start, $wp_offset ", ARRAY_A);    
            $get_ids = array_column($get_id , 'id');
            $last_form_id = end($get_ids);
            // $wp_forms_count = count($available_forms);

            $forms_within_limit = count( $get_ids );

            $default_column_names = array('id', 'source_name', 'source_from', 'form_name', 'form_id', 'crm_type', 'created_at', 'sync_status');
            $info = [];
            $i = 0;

            $created_count = 0;
            $updated_count = 0;
            $skipped_count = 0;
            $failed_count = 0;

            $created_message = '';
            $updated_message = '';
            $skipped_message = '';
            $failed_message = '';
                
            if(!empty($get_ids)){
                foreach($get_ids as $id){
                    $form_type = $wpdb->get_var($wpdb->prepare("SELECT source_name FROM {$wpdb->prefix}smack_ulb_databucket_meta WHERE id = %d", $id));
                    $form_id = $wpdb->get_var($wpdb->prepare( "SELECT form_id FROM {$wpdb->prefix}smack_ulb_databucket_meta WHERE id = %d", $id));
                    
                    if(empty($form_id)){
                        $form_id = $wpdb->get_var($wpdb->prepare( "SELECT form_name FROM {$wpdb->prefix}smack_ulb_databucket_meta WHERE id = %d", $id));
                    }

                    $form_type = $form_type_array[$form_type];

                    $form_info = get_option("WPULB_INFO_{$form_type}_{$form_id}");
                    $connected_addon = $form_info['configured_addon'];
                 
                    if($connected_addon == 'CRM' || $connected_addon == 'HELPDESK'){
                        if($connected_addon == 'CRM'){
                            $active_crm = get_option('WPULB_ACTIVE_CRM_ADDON');
                            $crm_mapping = get_option("WPULB_CRM_MAPPING_{$form_type}_{$form_id}");    
                            $data_mapping = get_option("WPULB_DATA_MAPPING_{$form_type}_{$form_id}");    
                        }
                        elseif($connected_addon == 'HELPDESK'){
                            $active_crm = get_option('WPULB_ACTIVE_HELPDESK_ADDON');
                            $crm_mapping = get_option("WPULB_HELP_MAPPING_{$form_type}_{$form_id}");    
                            $data_mapping = get_option("WPULB_HELP_DATA_MAPPING_{$form_type}_{$form_id}");   
                        }
                        
                        if(!empty($data_mapping)){
                            foreach($data_mapping as $data_key => $data_value){
                                if(isset($crm_mapping[$data_key])){
                                    $data_bucket_mapping[$data_value] = $crm_mapping[$data_key];
                                }	
                            }
                        }
                    }
                    elseif($connected_addon == 'DATA_BUCKET_ONLY'){
                        $crm_mapping = get_option("WPULB_DATA_MAPPING_{$form_type}_{$form_id}");
                        $data_bucket_mapping = $crm_mapping;
                        $active_crm = 'DataBucket';
                    }

                    $get_form_columns = $wpdb->get_results("SHOW COLUMNS FROM {$wpdb->prefix}smack_ulb_databucket_meta ");
                    foreach($get_form_columns as $form_column){	
                        if(!in_array($form_column->Field , $default_column_names)){   
                            $get_field_value = $wpdb->get_var($wpdb->prepare( "SELECT $form_column->Field FROM {$wpdb->prefix}smack_ulb_databucket_meta WHERE id = %d AND $form_column->Field IS NOT NULL", $id));		
                            if(!empty($get_field_value)){
                                $form_details[$form_column->Field] = $get_field_value;	
                            }  
                        }
                    }
                    array_push($info, $form_details);

                    $data_to_send = [];
                    $data_to_send['post_array'] = $info[$i];
                    $data_to_send['inserted_id'] = $id;
                        
                    if($connected_addon == 'CRM' || $connected_addon == 'HELPDESK'){
                        if($connected_addon == 'CRM'){	
                            $data_to_send = self::$third_party_instance->get_crm_info($form_type , $form_id, $data_to_send);
                            unset($data_to_send['mapping_array']);
                            $data_to_send['mapping_array'] = get_option("WPULB_DATA_MAPPING_{$form_type}_{$form_id}");
                        }
                        elseif($connected_addon == 'HELPDESK'){	
                            $data_to_send = self::$third_party_instance->get_helpdesk_info($form_type , $form_id, $data_to_send);
                            unset($data_to_send['mapping_array']);
                            $data_to_send['mapping_array'] = get_option("WPULB_HELP_DATA_MAPPING_{$form_type}_{$form_id}");
                        }  
                        $bucket_sync_result = self::$third_party_instance->form_sync_during_submit($connected_addon, $active_crm, $active_crm, $data_to_send);
                    
                        if($bucket_sync_result == 'Created'){
                            $created_count = $created_count + 1;
                        }
                        elseif($bucket_sync_result == 'Updated'){
                            $updated_count = $updated_count + 1;
                        }
                        elseif($bucket_sync_result == 'Skipped'){
                            $skipped_count = $skipped_count + 1;
                        }
                        else{
                            $failed_count = $failed_count + 1;
                        }
                    }
                    $i++;
                }
            }
            
            if($created_count > 0){
                $created_message = "$created_count Created"; 
            }
            if($updated_count > 0){
                $updated_message = " $updated_count Updated"; 
            }
            if($skipped_count > 0){
                $skipped_message = " $skipped_count Skipped"; 
            }
            if($failed_count > 0){
                $failed_message = " $failed_count Not Synced"; 
            }
            $message = $created_message.$updated_message.$skipped_message.$failed_message;

            if($wp_start != 0){
                $get_total_count = $get_total_count + 10;
            }
           
            $forms_synced_count = $forms_synced_count + $wp_offset;
            $wp_start = $wp_offset + $wp_start;
            $form_sync_array['start'] = $wp_start;
            $form_sync_array['offset'] = intval($wp_offset);
            $form_sync_array['total_count'] = $get_total_count;
            $form_sync_array['last_form_id'] = $last_form_id;
            $form_sync_array['forms_within_limit'] = $forms_within_limit;
            $form_sync_array['synced_count'] = $forms_synced_count;

            echo wp_json_encode([
                'response' => [ 
                    'sync_status' => $form_sync_array,
                ], 
                'message' => $message, 
                'status' => 200, 
                'success' => true
            ]);
        
            wp_die();
        }
    }
}
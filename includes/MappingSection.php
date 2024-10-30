<?php
/**
* CRM Connector Plus plugin file. 
*
* Copyright (C) 2010-2020, Smackcoders Inc - info@smackcoders.com 
*/

namespace Smackcoders\WPULB;

if ( ! defined( 'ABSPATH' ) )
exit; // Exit if accessed directly

class MappingSection
{
    protected static $instance = null , $manage_form_instance;
    protected static $third_party_instance = null;

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
            self::$instance->doHooks();
            ManageFields::getInstance();

            self::$manage_form_instance = ManageForms::getInstance();
            self::$third_party_instance = ThirdPartyForm::getInstance();
        }

        return self::$instance;
    }

    /**
     * doHooks
     *
     * @return void
     */
    public function doHooks(){
        add_action('wp_ajax_wpulb_save_native_form_mapping',array($this,'saveOrUpdateNativeFormMapping'));
        add_action('wp_ajax_wpulb_save_thirdparty_form_mapping',array($this,'saveOrUpdateThirdPartyFormMapping'));
        add_action('wp_ajax_wpulb_fetch_thirdparty_form_mapping',array($this,'fetchThirdPartyFormMapping'));
        
        add_action('wp_ajax_wpulb_edit_form_sorting',array($this,'editFormSorting'));
        add_action('wp_ajax_wpulb_edit_form_mapping',array($this,'editFormMapping'));
    }

    /**
     * saveOrUpdateNativeFormMapping
     *
     * @param  mixed $post_params
     *
     * @return void
     */
    public static function saveOrUpdateNativeFormMapping(){
        global $wpdb;
        $crm_type = 'wpulb_crm';
       
        $generate_shortcode = sanitize_text_field($_POST['shortcode']);
        $generate_shortcode = MappingSection::retrieve_shortcode($generate_shortcode);
        $is_edit =  sanitize_text_field($_POST['edit']);
       
        // Request should be like below
        $enabled_fields = [];
        $req_enabled_fields = wp_unslash(sanitize_text_field($_POST['enabled_fields']) ); 
        $req_enabled_fields = json_decode($req_enabled_fields, True);
        $enabled_fields = array_keys($req_enabled_fields);
        $native_form_info = get_option("WPULB_INFO_DEFAULT_FORM_{$generate_shortcode}");
        $native_form_info['enabled_fields'] = $enabled_fields;
        $native_form_info['configured_addon'] = 'DATA_BUCKET_ONLY';
        update_option("WPULB_INFO_DEFAULT_FORM_{$generate_shortcode}" , $native_form_info);
       
        /* Converting default form to choosen third party form */
        $get_form_settings = get_option("WPULB_DEFAULT_FORM_SETTINGS_{$generate_shortcode}");
        if(!empty($get_form_settings['form']) && !empty($get_form_settings['title']) && $get_form_settings['form'] != 'None'){
            $mandatory_fields = $native_form_info['mandatory_fields'];
            self::$manage_form_instance->manageThirdPartyForms($get_form_settings['form'] , $get_form_settings['title'] , $generate_shortcode , $enabled_fields , $mandatory_fields);
        }
        
        if($is_edit == 'true'){
            $exist_id = $wpdb->get_var($wpdb->prepare( "SELECT shortcode_id FROM {$wpdb->prefix}smack_ulb_shortcode_manager WHERE shortcode_name = %s AND form_type = %s", $generate_shortcode, 'DEFAULT_FORM'));
                
            $wpdb->update( 
                "{$wpdb->prefix}smack_ulb_shortcode_manager", 
                array('status' => 'Updated', 'crm_type' => $crm_type), 
                array('shortcode_id' => $exist_id), 
                array('%s'), 
                array('%d') 
            );
            echo wp_json_encode(['response' => ['shortcode' =>  sanitize_text_field($_POST['shortcode'])], 'message' => 'Form Updated Successfully', 'status' => 200, 'success' => true]); 
        }elseif($is_edit == 'false'){
            $wpdb->insert( 
                "{$wpdb->prefix}smack_ulb_shortcode_manager", 
                array('form_type' => 'DEFAULT_FORM', 'crm_type' => $crm_type , 'shortcode_name' => $generate_shortcode, 'module' => ' ', 'Round_Robin' => ' ', 'status' => 'Created'),  
                array('%s', '%s' ,'%s')
            ); 
            echo wp_json_encode(['response' => ['shortcode' =>  sanitize_text_field($_POST['shortcode'])], 'message' => 'Form Saved Successfully', 'status' => 200, 'success' => true]);   
        }

        wp_die();  
    }

    /**
     * saveOrUpdateThirdPartyFormMapping
     *
     * @return void
     */
    public function saveOrUpdateThirdPartyFormMapping(){
     
        global $wpdb;
        $mappingresult = sanitize_text_field($_POST['mappingResult']);
        $mapping_request =  wp_unslash($mappingresult) ;
        $mapping_request = json_decode($mapping_request, True);
        $mapped_array = [];
        if(isset($mapping_request['databucket_mapping'])){
            $mapped_array['databucket_mapping'] = self::getMappedArray($mapping_request['databucket_mapping']);
        }
        if(isset($mapping_request['form_mapping'])){
            $mapped_array['form_mapping'] = self::getMappedArray($mapping_request['form_mapping']);
        }
     
        $form_type = isset($_POST['form_type']) ? sanitize_text_field($_POST['form_type']) : '';
        $sync_to = isset($_POST['sync_to']) ? sanitize_text_field($_POST['sync_to']) : '';
        
        if($form_type == 'DEFAULT_FORM'){

            $shortcode_name = sanitize_text_field($_POST['shortcode']);
            $shortcode_name = MappingSection::retrieve_shortcode($shortcode_name);
            $form_id = NULL;
            $native_form_info = get_option("WPULB_INFO_DEFAULT_FORM_{$shortcode_name}");

            $get_form_settings = get_option("WPULB_DEFAULT_FORM_SETTINGS_{$shortcode_name}");
            if(!empty($get_form_settings['form']) && !empty($get_form_settings['title']) && $get_form_settings['form'] != 'None'){
                $form_types = $get_form_settings['form'];
                $enabled_fields = $native_form_info['enabled_fields'];
                $mandatory_fields = $native_form_info['mandatory_fields'];
                $new_id = self::$manage_form_instance->manageThirdPartyForms($get_form_settings['form'] , $get_form_settings['title'] , $shortcode_name , $enabled_fields , $mandatory_fields);
            }

            if($sync_to == 'CRM'){

                $module = $native_form_info['module'];
                $crm_type = $native_form_info['connected_crm'];

                if(isset($mapped_array['databucket_mapping'])){
                    update_option( "WPULB_DATA_MAPPING_{$form_type}_{$shortcode_name}", $mapped_array['databucket_mapping'] );
                }else{
                    delete_option("WPULB_DATA_MAPPING_{$form_type}_{$shortcode_name}");
                }
                update_option( "WPULB_CRM_MAPPING_{$form_type}_{$shortcode_name}", $mapped_array['form_mapping'] );

                /* if third party form is selected , update mapping info for that form*/
                if(!empty($get_form_settings['form']) && !empty($get_form_settings['title'])){
                    if(isset($mapped_array['databucket_mapping'])){
                        update_option( "WPULB_DATA_MAPPING_{$form_types}_{$new_id}", $mapped_array['databucket_mapping'] );
                    }

                    $converted_third_mapping = MappingSection::convert_third_party_mapping($mapping_request['form_mapping'] , $form_types , $new_id);
                    if($converted_third_mapping == 'Not a valid key' || $converted_third_mapping == 'Activate the domain in your my account page'){
                        echo wp_json_encode(['response' => '', 'message' => $converted_third_mapping .' Form Addon' , 'status' => 200, 'success' => false]); 
                        wp_die();
                    }
                    update_option( "WPULB_CRM_MAPPING_{$form_types}_{$new_id}", $converted_third_mapping );
                }
            
            }elseif($sync_to == 'HELPDESK'){

                $module = $native_form_info['module'];
                $crm_type = $native_form_info['connected_help'];
                
                if(isset($mapped_array['databucket_mapping'])){
                    update_option( "WPULB_HELP_DATA_MAPPING_{$form_type}_{$shortcode_name}", $mapped_array['databucket_mapping'] );
                }else{
                    delete_option("WPULB_HELP_DATA_MAPPING_{$form_type}_{$shortcode_name}");
                }
                update_option( "WPULB_HELP_MAPPING_{$form_type}_{$shortcode_name}", $mapped_array['form_mapping'] );

                /* if third party form is selected , update mapping info for that form*/
                if(!empty($get_form_settings['form']) && !empty($get_form_settings['title'])){
                    if(isset($mapped_array['databucket_mapping'])){
                        update_option( "WPULB_HELP_DATA_MAPPING_{$form_types}_{$new_id}", $mapped_array['databucket_mapping'] );
                    }
                    $converted_third_mapping = MappingSection::convert_third_party_mapping($mapping_request['form_mapping'] , $form_types , $new_id);
                    if($converted_third_mapping == 'Not a valid key' || $converted_third_mapping == 'Activate the domain in your my account page'){
                        echo wp_json_encode(['response' => '', 'message' => $converted_third_mapping.' Form Addon', 'status' => 200, 'success' => false]); 
                        wp_die();
                    }
                    update_option( "WPULB_HELP_MAPPING_{$form_types}_{$new_id}", $converted_third_mapping );
                }
            }

            $native_form_info['configured_addon'] = $sync_to;
            update_option("WPULB_INFO_{$form_type}_{$shortcode_name}", $native_form_info);

            /* if third party form is selected , update mapping info for that form*/
            if(!empty($get_form_settings['form']) && !empty($get_form_settings['title'])){
                update_option("WPULB_INFO_{$form_types}_{$new_id}", $native_form_info);
            }

            $has_already = $wpdb->get_var($wpdb->prepare( "SELECT COUNT(*) as has_already FROM {$wpdb->prefix}smack_ulb_shortcode_manager WHERE shortcode_name = %s AND form_type = %s", $shortcode_name, $form_type));

            if($has_already){
                $exist_id = $wpdb->get_var($wpdb->prepare( "SELECT shortcode_id FROM {$wpdb->prefix}smack_ulb_shortcode_manager WHERE shortcode_name = %s AND form_type = %s", $shortcode_name, $form_type)); 

                $wpdb->update( 
                    "{$wpdb->prefix}smack_ulb_shortcode_manager", 
                    array('status' => 'Updated', 'module' => $module, 'crm_type' => $crm_type), 
                    array('shortcode_id' => $exist_id), 
                    array('%s', '%s'), 
                    array('%d') 
                );
                echo wp_json_encode(['response' => '', 'message' => 'Form updated successfully', 'status' => 200, 'success' => true]); 
            }else{
                $wpdb->insert( 
                    "{$wpdb->prefix}smack_ulb_shortcode_manager", 
                    array('form_type' => $form_type , 'crm_type' => $crm_type , 'shortcode_name' => $shortcode_name, 'module' => $module, 'Round_Robin' => ' ', 'status' => 'Created'),  
                    array('%s', '%s', '%s' ,'%s', '%d')
                );
                echo wp_json_encode(['response' => '', 'message' => 'Form saved successfully', 'status' => 200, 'success' => true]); 
            }

        }else{
            $form_id = intval($_POST['form_id']);
            $other_form_info = get_option("WPULB_INFO_{$form_type}_{$form_id}");

            if($sync_to == 'CRM'){

                $module = $other_form_info['module'];
                $crm_type = $other_form_info['connected_crm'];

                if(isset($mapped_array['databucket_mapping'])){
                    update_option( "WPULB_DATA_MAPPING_{$form_type}_{$form_id}", $mapped_array['databucket_mapping'] );
                }else{
                    delete_option("WPULB_DATA_MAPPING_{$form_type}_{$form_id}");
                }
                update_option( "WPULB_CRM_MAPPING_{$form_type}_{$form_id}", $mapped_array['form_mapping'] );

            }elseif($sync_to == 'HELPDESK'){

                $module = $other_form_info['module'];
                $crm_type = $other_form_info['connected_help'];

                if(isset($mapped_array['databucket_mapping'])){
                    update_option( "WPULB_HELP_DATA_MAPPING_{$form_type}_{$form_id}", $mapped_array['databucket_mapping'] );
                }else{
                    delete_option("WPULB_HELP_DATA_MAPPING_{$form_type}_{$form_id}");
                }
                update_option( "WPULB_HELP_MAPPING_{$form_type}_{$form_id}", $mapped_array['form_mapping'] );

            }elseif($sync_to == 'DATA_BUCKET_ONLY'){
                $module = '';
                $crm_type = 'wpulb_crm';
                update_option( "WPULB_DATA_MAPPING_{$form_type}_{$form_id}", $mapped_array['form_mapping'] );
            }
            
            if($form_type == 'NINJA_FORM'){
                $shortcode_name = $wpdb->get_var($wpdb->prepare("SELECT title FROM {$wpdb->prefix}nf3_forms WHERE id = %d", $form_id));      
            }
            elseif($form_type == 'CONTACT_FORM'){
                $shortcode_name = $wpdb->get_var($wpdb->prepare("SELECT post_title FROM {$wpdb->prefix}posts WHERE ID = %d AND post_type = %s", $form_id, 'wpcf7_contact_form'));   
            }
            elseif($form_type == 'GRAVITY_FORM'){
                $shortcode_name = $wpdb->get_var($wpdb->prepare("SELECT title FROM {$wpdb->prefix}gf_form WHERE id = %d", $form_id)); 
            }
            elseif($form_type == 'QU_FORM'){
                $shortcode_name = $wpdb->get_var($wpdb->prepare("SELECT name FROM {$wpdb->prefix}quform_forms WHERE id = %d", $form_id));
            }
            elseif($form_type == 'CALDERA_FORM'){
                $get_caldera_array = $wpdb->get_var( $wpdb->prepare("SELECT config FROM {$wpdb->prefix}cf_forms WHERE form_id = %s AND type = %s ", $form_id, 'primary' ));
                $get_caldera_config = unserialize($get_caldera_array);
                $shortcode_name = $get_caldera_config['name'];
            }
            elseif($form_type == 'WP_FORM'){
                $shortcode_name = $wpdb->get_var($wpdb->prepare("SELECT post_title FROM {$wpdb->prefix}posts WHERE ID = %d AND post_type = %s", $form_id, 'wpforms'));
            }
            
            $other_form_info['configured_addon'] = $sync_to;
            update_option("WPULB_INFO_{$form_type}_{$form_id}", $other_form_info);
          
            $has_already = $wpdb->get_var($wpdb->prepare( "SELECT COUNT(*) as has_already FROM {$wpdb->prefix}smack_ulb_shortcode_manager WHERE shortcode_name = %s AND form_type = %s AND form_id = %s", $shortcode_name, $form_type, $form_id));
            
            if($has_already){  
                $exist_id = $wpdb->get_var($wpdb->prepare( "SELECT shortcode_id FROM {$wpdb->prefix}smack_ulb_shortcode_manager WHERE shortcode_name = %s AND form_type = %s AND form_id = %s", $shortcode_name, $form_type, $form_id));    

                $wpdb->update( 
                    "{$wpdb->prefix}smack_ulb_shortcode_manager", 
                    array('status' => 'Updated', 'module' => $module, 'crm_type' => $crm_type), 
                    array('shortcode_id' => $exist_id), 
                    array('%s', '%s'), 
                    array('%d') 
                );
                echo wp_json_encode(['response' => '', 'message' => $shortcode_name . ' Form updated successfully', 'status' => 200, 'success' => true]); 
            }else{
                $wpdb->insert( 
                    "{$wpdb->prefix}smack_ulb_shortcode_manager", 
                    array('form_type' => $form_type , 'crm_type' => $crm_type , 'shortcode_name' => $shortcode_name, 'module' => $module, 'Round_Robin' => ' ', 'status' => 'Created', 'form_id' => $form_id),  
                    array('%s', '%s', '%s' ,'%s', '%d')
                );
                echo wp_json_encode(['response' => '', 'message' => 'Form saved successfully', 'status' => 200, 'success' => true]); 
            }
        }

        wp_die();
    }

    /**
     * getMappedArray
     *
     * @param  mixed $mapping_request
     *
     * @return void
     */
    public static function getMappedArray($mapping_request){
        
        $mapped_array = [];
        foreach($mapping_request as $form_fieldname => $mapped_value){
            $mapped_array[$form_fieldname] = $mapped_value['value'];
        }
        return $mapped_array;
    }

    public function fetchThirdPartyFormMapping(){
        
        $shortcode_id = sanitize_text_field($_REQUEST['shortcode_id']);
        $thirdparty_formname = sanitize_text_field($_REQUEST['thirdparty_formname']);
        $mapping_fields = get_option( "WPULB_{$thirdparty_formname}_{$shortcode_id}", true );
        echo wp_json_encode(['response' => $mapping_fields, 'message' => 'Mapping info saved successfully', 'status' => 200, 'success' => true]); 
        wp_die();
    }

    public function fetchThirdPartyFormMappingForSync($shortcode_name, $thirdparty_formname){
        
        return get_option( "WPULB_{$thirdparty_formname}_{$shortcode_name}", true );
    }

    public static function editFormSorting(){
        global $wpdb;
        $forms = [];
        $info = [];

        $form_type = sanitize_text_field($_POST['form_type']);
        $shortcode = sanitize_text_field($_POST['shortcode']);
        $addon = sanitize_text_field($_POST['sync_to']);

        $shortcode = MappingSection::retrieve_shortcode($shortcode);
        $req_mandatory_fields =  wp_unslash(sanitize_text_field($_POST['mandatory_fields']) );
        $req_mandatory_fields = json_decode($req_mandatory_fields, True);
        $req_enabled_fields =  wp_unslash(sanitize_text_field( $_POST['enabled_fields']));
        $req_enabled_fields = json_decode($req_enabled_fields, True);

        $required_enabled_fields = array_keys($req_enabled_fields);
        $required_mandatory_fields = array_keys($req_mandatory_fields);

        $native_form_info = get_option("WPULB_INFO_DEFAULT_FORM_{$shortcode}");
       
        if(empty($native_form_info)){
            $native_form_fields = [];
            $native_form_fields['enabled_fields'] = $required_enabled_fields;
            $native_form_fields['connected_crm'] = 'wpulb_crm';
            update_option("WPULB_INFO_DEFAULT_FORM_{$shortcode}" , $native_form_fields);
        }
 
        $native_form_info = get_option("WPULB_INFO_DEFAULT_FORM_{$shortcode}");
      
        $get_enabled_fields = $native_form_info['enabled_fields'];
       
        $array_diff = array_diff($required_enabled_fields ,$get_enabled_fields);
 
        foreach($get_enabled_fields as $enabled_fields){
            if(in_array($enabled_fields , $required_enabled_fields)){

                $field_type = $wpdb->get_var($wpdb->prepare("SELECT field_type FROM {$wpdb->prefix}smack_ulb_manage_fields WHERE field_name = %s", $enabled_fields));
                $field_label = $wpdb->get_var($wpdb->prepare("SELECT field_label FROM {$wpdb->prefix}smack_ulb_manage_fields WHERE field_name = %s", $enabled_fields));
    
                $forms['label'] = $field_label;
                $forms['value'] = $enabled_fields;
                $forms['type'] = $field_type; 

                if(in_array($enabled_fields , $required_mandatory_fields)){
                    $forms['mandatory'] = true;
                }else{
                    $forms['mandatory'] = false;
                }
                array_push($info, $forms);
            }
        }

        if(!empty($array_diff)){
            foreach($array_diff as $diff){
  
                $field_type = $wpdb->get_var($wpdb->prepare("SELECT field_type FROM {$wpdb->prefix}smack_ulb_manage_fields WHERE field_name = %s", $diff));
                $field_label = $wpdb->get_var($wpdb->prepare("SELECT field_label FROM {$wpdb->prefix}smack_ulb_manage_fields WHERE field_name = %s", $diff));
                
                $forms['label'] = $field_label;
                $forms['value'] = $diff;
                $forms['type'] = $field_type; 

                if(in_array($diff , $required_mandatory_fields)){
                    $forms['mandatory'] = true;
                }else{
                    $forms['mandatory'] = false; 
                }
                array_push($info, $forms);
            }
        }

        $native_form_info['mandatory_fields'] = $required_mandatory_fields;
       
        if($addon == 'DATA_BUCKET_ONLY'){
            $native_form_info['connected_crm'] = 'wpulb_crm';
        }
        elseif($addon == 'CRM' || $addon == 'HELPDESK'){

            $duplicate_handling = sanitize_text_field($_POST['duplicate_handling']);
            $record_owner = sanitize_text_field($_POST['record_owner']);
            $module = sanitize_text_field($_POST['module']);
            $native_form_info['module'] = $module;
            $native_form_info['owner'] = $record_owner;
            $native_form_info['duplicate_handle'] = $duplicate_handling;

            if($addon == 'CRM'){
                $crm = get_option('WPULB_ACTIVE_CRM_ADDON');
                $native_form_info['connected_crm'] = $crm;

            }
            elseif($addon == 'HELPDESK'){
                $crm = get_option('WPULB_ACTIVE_HELPDESK_ADDON');
                $native_form_info['connected_help'] = $crm;
            }
        }

        
        $native_form_info['before_sorting'] = $info;
        update_option( "WPULB_INFO_DEFAULT_FORM_{$shortcode}", $native_form_info );

        $widget_options = array('None' , 'Post' , 'Widget');
        $form_type = [];
        $temp = 0;
        foreach($widget_options as $values){
            $form_type[$temp]['label'] = $values;
            $form_type[$temp]['value'] = $values;
            $temp++;
        }

        global $supported_thirdparty_forms;
        $allowed_forms = [];

        $allowed_forms[0]['label'] = 'None';
        $allowed_forms[0]['value'] = 'None';

        $allowed_forms[1]['label'] = 'Contact Form';
        $allowed_forms[1]['value'] = 'CONTACT_FORM';

        $temp = 2;
        foreach($supported_thirdparty_forms as $supported_thirdparty_form){
            if(($supported_thirdparty_form['plugin_label'] != 'Qu Form') && ($supported_thirdparty_form['plugin_label'] != 'Contact Form')  && ($supported_thirdparty_form['plugin_label'] != 'Caldera Form')  && ($supported_thirdparty_form['plugin_label'] != 'WP Form')){
               
                if( (is_plugin_active( $supported_thirdparty_form['plugin_slug'] . '/' . $supported_thirdparty_form['plugin_filename'])) && (is_plugin_active( $supported_thirdparty_form['leads_form_slug'] . '/' . $supported_thirdparty_form['leads_form_filename'])) ) {
                    $allowed_forms[$temp]['label'] = $supported_thirdparty_form['plugin_label'];
                    $allowed_forms[$temp]['value'] = $supported_thirdparty_form['plugin_wpulb_uniquename'];
                    $temp++;
                }
            }
        }

        $already_configured_settings = get_option("WPULB_DEFAULT_FORM_SETTINGS_{$shortcode}");

        if(isset($already_configured_settings['form_type'])){
            $already_configured_settings['form_type'] = array(
                                                    'label' => $already_configured_settings['form_type'],
                                                    'value' => $already_configured_settings['form_type']
                                                );
        }
        elseif(empty($already_configured_settings['form_type'])){
            $already_configured_settings['form_type'] = [];
        }

        if(isset($already_configured_settings['form'])){
            foreach($supported_thirdparty_forms as $supported_forms){
                if($supported_forms['plugin_wpulb_uniquename'] == $already_configured_settings['form']){
                    $already_configured_settings['form'] = array(
                                                    'label' => $supported_forms['plugin_label'],
                                                    'value' => $supported_forms['plugin_wpulb_uniquename']
                                                );                              
                }

                elseif($already_configured_settings['form'] == 'None'){
                    $already_configured_settings['form'] = array(
                        'label' => 'None',
                        'value' => 'None'
                    );   
                }
            }
        }

        if(isset($already_configured_settings['captcha'])){
            if($already_configured_settings['captcha'] == 'true'){
                $already_configured_settings['captcha'] = true;
            }
            elseif($already_configured_settings['captcha'] == 'false'){
                $already_configured_settings['captcha'] = false;
            }
        }

        $check_google_captcha_enabled = get_option("WPULB_ENABLE_CAPTCHA");
        if($check_google_captcha_enabled == 'on'){
            $google_captcha = true;
        }else{
            $google_captcha = false;
        }
            
        echo wp_json_encode(['response' => ['form_type' => $form_type, 'third_party_forms' => $allowed_forms, 'already_configured' => $already_configured_settings, 'captcha_enabled' => $google_captcha ], 'message' => 'Default form settings', 'status' => 200, 'success' => true]); 
        wp_die();  
    }

    public static function editFormMapping(){
        $form_type = sanitize_text_field($_GET['form_type']);
        $shortcode = sanitize_text_field($_GET['shortcode']);
        $sync_to = sanitize_text_field($_GET['sync_to']);

        global $wpdb;
        $data_fields = [];
        $form_fields = [];

        if($form_type == 'DEFAULT_FORM'){ 
            $shortcode = MappingSection::retrieve_shortcode($shortcode);
            $native_form_info = get_option("WPULB_INFO_DEFAULT_FORM_{$shortcode}");

            $enabled_fields = [];
            $req_enabled_fields =  str_replace("\\" , '' , sanitize_text_field($_GET['enabled_fields']));
            $req_enabled_fields = json_decode($req_enabled_fields, True);

            $enabled_fields = array_keys($req_enabled_fields);
            $native_form_info['enabled_fields'] = $enabled_fields;
            update_option("WPULB_INFO_DEFAULT_FORM_{$shortcode}" , $native_form_info);
            
            if($sync_to == 'CRM'){
                $module = $native_form_info['module'];
            }elseif($sync_to == 'HELPDESK'){
                $module = $native_form_info['module'];
            }

            $connected_crm = $native_form_info['connected_crm'];
           
            $fields_name_and_label = ManageFields::fetchAllFields('field-name-and-label');
            $thirdparty_form_fields = [];
          
            $default_form_fields = $enabled_fields;
            foreach($default_form_fields as $field_name){
                $field_label = $wpdb->get_var( $wpdb->prepare("SELECT field_label FROM {$wpdb->prefix}smack_ulb_manage_fields WHERE field_name = %s", $field_name));
                $thirdparty_form_fields[$field_name] = $field_label;   
            }

            if($sync_to == 'CRM'){
                $addon_fields = ManageFields::getAddonFields(get_option('WPULB_ACTIVE_CRM_ADDON'), $module , $sync_to);
                $addon_fields_arr = self::$third_party_instance->change_asso_array_to_array($addon_fields);
                
                $crm = $native_form_info['connected_crm'];
                $form_mapping = get_option("WPULB_CRM_MAPPING_{$form_type}_{$shortcode}"); 
                $databucket_mapping = get_option("WPULB_DATA_MAPPING_{$form_type}_{$shortcode}"); 
            }else if($sync_to == 'HELPDESK'){
                $addon_fields = ManageFields::getAddonFields(get_option('WPULB_ACTIVE_HELPDESK_ADDON'), $module, $sync_to);
                $addon_fields_arr = self::$third_party_instance->change_asso_array_to_array($addon_fields);

                $crm = $native_form_info['connected_help'];
                $form_mapping = get_option("WPULB_HELP_MAPPING_{$form_type}_{$shortcode}"); 
                $databucket_mapping = get_option("WPULB_HELP_DATA_MAPPING_{$form_type}_{$shortcode}"); 
            }
    
            if(!empty($form_mapping)){
                $form_fields = MappingSection::get_mapping_fields($addon_fields_arr, $form_mapping, $thirdparty_form_fields, $form_type);
            }
   
            if(!empty($databucket_mapping)){
                $data_fields = MappingSection::get_mapping_fields($addon_fields_arr , $databucket_mapping ,$fields_name_and_label, $form_type);
            }

        }else{

            if($form_type == 'CALDERA_FORM'){
                $form_id = intval($_GET['form_id']);
            }else{
                $form_id = intval($_GET['form_id']);
            }

            $other_form_info = get_option("WPULB_INFO_{$form_type}_{$form_id}");

            $thirdparty_form_fields = ManageForms::getThirdpartyFormFields($form_type, $form_id);
            if($thirdparty_form_fields == 'Not a valid key' || $thirdparty_form_fields == 'Activate the domain in your my account page'){
                echo wp_json_encode([
                'response' => '',
                'message' => $thirdparty_form_fields .'Form Addon', 
                'status' => 200, 
                'success' => false
            ]); 
            wp_die();
            }
            if($sync_to == 'DATA_BUCKET_ONLY'){
                $databucket_mapping = get_option("WPULB_DATA_MAPPING_{$form_type}_{$form_id}");

                $databucket_fields = ManageFields::fetchAllFields('field-name-and-label');
                $addon_fields = self::$third_party_instance->change_array_to_asso_array($databucket_fields);
                $fields_name_and_label = [];
            
                $other_form_info['connected_crm'] = 'wpulb_crm';

                if(!empty($databucket_mapping)){
                    $form_fields = MappingSection::get_mapping_fields($databucket_fields , $databucket_mapping , $thirdparty_form_fields, $form_type);
                }
            }elseif($sync_to == 'CRM' || $sync_to == 'HELPDESK'){

                $duplicate_handling = sanitize_text_field($_GET['duplicate_handling']);
                $record_owner = sanitize_text_field($_GET['record_owner']);
                $module = sanitize_text_field($_GET['module']);

                $other_form_info['module'] = $module;
                $other_form_info['owner'] = $record_owner;
                $other_form_info['duplicate_handle'] = $duplicate_handling;

                $fields_name_and_label = ManageFields::fetchAllFields('field-name-and-label');

                if($sync_to == 'CRM'){
                    $crm_type = get_option('WPULB_ACTIVE_CRM_ADDON');
                    $other_form_info['connected_crm'] = $crm_type;

                    $addon_fields = ManageFields::getAddonFields(get_option('WPULB_ACTIVE_CRM_ADDON'), $module , $sync_to);
                    $addon_fields_arr = self::$third_party_instance->change_asso_array_to_array($addon_fields);

                    $form_mapping = get_option("WPULB_CRM_MAPPING_{$form_type}_{$form_id}"); 
                    $databucket_mapping = get_option("WPULB_DATA_MAPPING_{$form_type}_{$form_id}");

                }elseif($sync_to == 'HELPDESK'){

                    $crm_type = get_option('WPULB_ACTIVE_HELPDESK_ADDON');
                    $other_form_info['connected_help'] = $crm_type;

                    $addon_fields = ManageFields::getAddonFields(get_option('WPULB_ACTIVE_HELPDESK_ADDON'), $module , $sync_to);
                    $addon_fields_arr = self::$third_party_instance->change_asso_array_to_array($addon_fields);

                    $form_mapping = get_option("WPULB_HELP_MAPPING_{$form_type}_{$form_id}"); 
                    $databucket_mapping = get_option("WPULB_HELP_DATA_MAPPING_{$form_type}_{$form_id}");

                }

                if(!empty($form_mapping)){
                    $form_fields = MappingSection::get_mapping_fields($addon_fields_arr, $form_mapping, $thirdparty_form_fields, $form_type);
                }

                if(!empty($databucket_mapping)){
                    $data_fields = MappingSection::get_mapping_fields($addon_fields_arr, $databucket_mapping, $fields_name_and_label, $form_type);
                }
            }
            update_option("WPULB_INFO_{$form_type}_{$form_id}" , $other_form_info);
        }

        echo wp_json_encode([
            'response' => [
                'shortcode' => $shortcode, 
                'form_type' => $form_type, 
                'addon_fields' => $addon_fields,
                'data_bucket_fields' => ManageForms::returnResultTORequestedFormat($fields_name_and_label), 
                'form_fields' => ManageForms::returnResultTORequestedFormat($thirdparty_form_fields),

                'mapping_fields' => [
                    'form_mapping' => $form_fields,
                    'databucket_mapping' => $data_fields
                ]   
            ], 
            'message' => 'Form mapping section', 
            'status' => 200, 
            'success' => true
        ]);
                    
        wp_die();
    }

    public static function get_mapping_fields($form_fields , $databucket_mapping , $fields_name_and_label, $form_type){
        $data_fields = [];
        $data_mapped_fields = [];
    
        foreach($form_fields as $form_key => $form_values){
            if(array_key_exists($form_key , $databucket_mapping)){
                $data_value = $databucket_mapping[$form_key];

                if($form_type == 'WP_FORM'){
                    if(isset($fields_name_and_label[$data_value])){
                        $data_mapped_fields['label'] = $fields_name_and_label[$data_value];
                        $data_mapped_fields['value'] = $data_value; 
                    }else{
                        $data_mapped_fields['label'] = "";
                        $data_mapped_fields['value'] = "";
                    }
                }
                else{
                    if(!empty($data_value) && isset($fields_name_and_label[$data_value])){
                        $data_mapped_fields['label'] = $fields_name_and_label[$data_value];
                        $data_mapped_fields['value'] = $data_value; 
                    }else{
                        $data_mapped_fields['label'] = "";
                        $data_mapped_fields['value'] = "";
                    }
                }    
            }else{
                $data_mapped_fields['label'] = "";
                $data_mapped_fields['value'] = "";
            }
            array_push($data_fields , $data_mapped_fields);
        }
        return $data_fields;
    }

    public static function retrieve_shortcode($shortcode){
        if (strpos($shortcode, 'smack-web-form') !== false) {
            preg_match_all("/\\[(.*?)\\]/", $shortcode, $matches); 
            $shortcode = $matches[1][0];	
            $shortcode = substr($shortcode, strpos($shortcode, "=") + 1);
            return $shortcode;
        }else{
            return $shortcode;
        }
    }

    public static function convert_third_party_mapping($mapping_request , $form_type , $form_id){
        $thirdparty_form_fields = self::$manage_form_instance->getThirdpartyFormFields($form_type, $form_id);

        $mapped_array = [];
        foreach($mapping_request as $form_fieldname => $mapped_value){

            if($form_type == 'CONTACT_FORM'){
                $label = $mapped_value['value'];
                if(array_key_exists($label , $thirdparty_form_fields)){
                    $value = $label;
                }
                $mapped_array[$form_fieldname] = $value;
            }else{
                $label = $mapped_value['label'];
                if(in_array($label , $thirdparty_form_fields)){
                    $value = array_search($label , $thirdparty_form_fields);
                }
                $mapped_array[$form_fieldname] = $value;
            }    
        }
        return $mapped_array;
    }
}

global $leads_mapping_section_instance;
$leads_mapping_section_instance = new MappingSection();
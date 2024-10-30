<?php
/**
* CRM Connector Plus plugin file. 
*
* Copyright (C) 2010-2020, Smackcoders Inc - info@smackcoders.com 
*/

namespace Smackcoders\WPULB;

if ( ! defined( 'ABSPATH' ) )
exit; // Exit if accessed directly

class ManageGroups
{
    protected static $instance = null;
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

        add_action('wp_ajax_wpulb_save_group', array($this, 'createOrUpdateGroup'));
        add_action('wp_ajax_wpulb_list_group', array($this, 'fetchGroups'));
        add_action('wp_ajax_wpulb_get_group', array($this, 'fetchGroup'));
        add_action('wp_ajax_wpulb_delete_group', array($this, 'deleteGroup'));
    }

    /**
     * createOrUpdateGroup
     *
     * @return void
     */
    public static function createOrUpdateGroup(){
        $group_name = isset($_POST['group_name']) ? sanitize_text_field($_POST['group_name']) : '';
        $group_id = isset($_POST['group_id']) ? intval($_POST['group_id']) : '';
        $sequence = isset($_POST['sequence']) ? intval($_POST['sequence']) : '';
        
        global $wpdb;
        $has_already = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) as has_already FROM {$wpdb->prefix}smack_ulb_group_manager WHERE group_name = %s", $group_name));

        if(empty($group_name)){
            echo wp_json_encode(['response' => '', 'message' => 'Please provide Group name', 'status' => 200, 'success' => false]);
		    wp_die();
        }

        if($has_already){
            echo wp_json_encode(['response' => '', 'message' => 'Group already exists', 'status' => 200, 'success' => false]);
		    wp_die();
        }

        if(!$sequence){
            $sequence = ManageGroups::getSequenceNo();
        }
        $update = false;

        if($group_id){
            $update = true;
            $wpdb->update( 
                "{$wpdb->prefix}smack_ulb_group_manager", 
                array('group_name' => $group_name, 'sequence' => $sequence), 
                array('id' => $group_id), 
                array('%s', '%d'), 
                array('%d') 
            );
        }else{
            $wpdb->insert( 
                "{$wpdb->prefix}smack_ulb_group_manager", 
                array('group_name' => $group_name, 'sequence' => $sequence), 
                array('%s', '%d')
            );
        }

        if($update){
            echo wp_json_encode(['response' => '', 'message' => 'Group updated successfully', 'status' => 200, 'success' => true]);
		    wp_die();
        }

        echo wp_json_encode(['response' => '', 'message' => 'Group added successfully', 'status' => 200, 'success' => true]);
		wp_die();
    }

    /**
     * getSequenceNo
     *
     * @return void
     */
    public static function getSequenceNo(){

        global $wpdb;
        $sequence = $wpdb->get_var("SELECT sequence FROM {$wpdb->prefix}smack_ulb_group_manager ORDER BY sequence DESC LIMIT 1");
        if(!$sequence){
            return 1;
        }

        return intval($sequence) + 1;
    }

    /**
     * fetchGroups
     *
     * @return void
     */
    public static function fetchGroups(){
       
        global $wpdb;
        $groups = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}smack_ulb_group_manager ORDER BY updated_at DESC", ARRAY_A);
        
        foreach($groups as $key => $group){
            $id = $group['id'];
            $has_already = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}smack_ulb_manage_fields WHERE group_id = %d ", $id ) );
            $total_fields = count($has_already);
            $groups[$key]['count'] = $total_fields;
        }

        echo wp_json_encode(['response' => $groups, 'message' => 'List of Group', 'status' => 200, 'success' => true]);
		wp_die();
    }

    /**
     * fetchGroup
     *
     * @return void
     */
    public static function fetchGroup(){
        
        $group_id = intval($_GET['group_id']);
        global $wpdb;
        $group = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}smack_ulb_group_manager WHERE id = $group_id", ARRAY_A);
        echo wp_json_encode(['response' => $group, 'message' => 'Group Information', 'status' => 200, 'success' => true]);
		wp_die();
    }


    public static function deleteGroup(){
        global $wpdb;
        $group_id = isset($_POST['group_id']) ? intval($_POST['group_id']) : '';
       
        if($group_id == 1){
            echo wp_json_encode(['response' => '', 'message' => 'Basic Group cannot be deleted', 'status' => 200, 'success' => false]);
        }else{
        
            $get_field_type = $wpdb->get_results($wpdb->prepare( "SELECT id , field_type , field_name FROM {$wpdb->prefix}smack_ulb_manage_fields WHERE group_id = %d", $group_id));
            foreach($get_field_type as $field_type){
                if($field_type->field_type == 'Select' || $field_type->field_type == 'Multi-Select'){
                    $wpdb->delete( 
                        "{$wpdb->prefix}smack_ulb_manage_fields_picklist_values",
                        array('field_id' => $field_type->id),  
                        array('%d') 
                    );
                }
        
            }

            $wpdb->delete( 
                "{$wpdb->prefix}smack_ulb_manage_fields",
                array('group_id' => $group_id),  
                array('%d') 
            );

            $wpdb->delete( 
                "{$wpdb->prefix}smack_ulb_group_manager",
                array('id' => $group_id),  
                array('%d') 
            );  

            echo wp_json_encode(['response' => '', 'message' => 'Group deleted successfully', 'status' => 200, 'success' => true]);
        }
        wp_die();
    }
}
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
 * Class DataBuckets
 * @package Smackcoders\WPULB
 */
class DataBuckets{
	
    protected static $instance = null;
    
    /**
     * DataBuckets constructor.
     */
	public function __construct()
	{
		$this->plugin = WPULBPlugin::getInstance();
	}

    /**
     * DataBuckets Instances.
     * @return null|DataBuckets
     */
	public static function getInstance() {
		if ( null == self::$instance ) {
            self::$instance = new self;
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
        add_action('wp_ajax_wpulb_get_submitted_form_list', array($this, 'getSubmittedFormList'));
        add_action('wp_ajax_wpulb_get_submitted_form_details', array($this, 'getSubmittedFormDetails'));
    
        add_action('wp_ajax_wpulb_get_submitted_form_list_1', array($this, 'getSubmittedFormList1'));
        add_action('wp_ajax_wpulb_get_submitted_form_list_2', array($this, 'getSubmittedFormList2'));

        add_action('wp_ajax_wpulb_display_lists', array($this, 'displayLists'));
        add_action('wp_ajax_wpulb_create_new_list', array($this, 'createNewList'));
        add_action('wp_ajax_wpulb_save_or_update_list', array($this, 'saveOrUpdateList'));
        add_action('wp_ajax_wpulb_edit_list', array($this, 'editList'));
        add_action('wp_ajax_wpulb_delete_list', array($this, 'deleteList'));
        add_action('wp_ajax_wpulb_display_view', array($this, 'displayView'));
    }

    public static function getSubmittedFormList(){
        global $wpdb;
        $form_details = [];
        $info = [];
       
        $query  = "SELECT id, source_name, source_from, form_name, created_at, sync_status FROM {$wpdb->prefix}smack_ulb_databucket_meta";
        $total_query     = "SELECT COUNT(1) FROM (${query}) AS combined_table";
        $total             = $wpdb->get_var( $total_query );
        
        // Records per Page
        $items_per_page = get_option('posts_per_page');
        $page  = isset( $_REQUEST['cpage'] ) ? abs( (int) $_REQUEST['cpage'] ) : 1;
        
        $offset = ( $page * $items_per_page ) - $items_per_page;
        $get_submitted_forms  = $wpdb->get_results( $query . " ORDER BY id DESC LIMIT ${offset}, ${items_per_page}" );
        $totalPage = ceil($total / $items_per_page);

        //$get_submitted_forms = $wpdb->get_results("SELECT id, source_name, source_from, form_name, created_at FROM {$wpdb->prefix}smack_ulb_databucket_meta ORDER BY id DESC ");
        foreach($get_submitted_forms as $submitted_details){
            $date_format = substr($submitted_details->created_at, 0, 10);
            $date = date("M jS, Y", strtotime($date_format)); 
            $time = date('h:i A', strtotime($submitted_details->created_at));
            $submitted_date = $date .' '. $time;
            $form_details['shortcode'] = $submitted_details->form_name;
            $form_details['form_type'] = $submitted_details->source_name;
            $form_details['date'] = $submitted_date;
            $form_details['id'] = $submitted_details->id;
            $form_details['synced'] = $submitted_details->sync_status;   
            array_push($info, $form_details);
        }
    
        echo wp_json_encode(['response' => ['forms' => $info , 'total_page' => $totalPage], 'message' => 'All Submitted Forms', 'status' => 200, 'success' => true]); 
		wp_die();
    }

    public static function getSubmittedFormDetails(){
        global $wpdb;
        $form_id = intval($_POST['id']);
        $form_details = [];
        $info = [];

        $default_column_names = array('id', 'source_name', 'source_from', 'form_name', 'form_id', 'crm_type');
        $get_form_columns = $wpdb->get_results("SHOW COLUMNS FROM {$wpdb->prefix}smack_ulb_databucket_meta ");
        foreach($get_form_columns as $form_column){
            if(!in_array($form_column->Field , $default_column_names)){   
            
                $get_field_label = $wpdb->get_var($wpdb->prepare( "SELECT field_label FROM {$wpdb->prefix}smack_ulb_manage_fields WHERE field_name = %s", $form_column->Field));
                $get_field_value = $wpdb->get_var($wpdb->prepare( "SELECT $form_column->Field FROM {$wpdb->prefix}smack_ulb_databucket_meta WHERE id = %d AND $form_column->Field IS NOT NULL", $form_id));
                
                if(empty($get_field_label)){
                    $get_field_label = $form_column->Field;
                }
                if(!empty($get_field_value)){
                    if(is_serialized($get_field_value)){
                        $get_field_value = unserialize($get_field_value);

                        $data_values = '';
						foreach($get_field_value as $data_val){
							$data_values .= $data_val . ",";
						}
						$get_field_value = rtrim($data_values , ',');
                    }

                    if($get_field_label == 'created_at'){
                        $get_field_label = 'submitted_date';
                        $date_format = substr($get_field_value, 0, 10);
                        $date = date("M jS, Y", strtotime($date_format)); 
                        $time = date('h:i A', strtotime($get_field_value));
                        $submitted_date = $date .' '. $time;
                        $get_field_value = $submitted_date;
                    }

                    $form_details['label'] = $get_field_label;
                    $form_details['name'] = $get_field_value;
                    array_push($info, $form_details);
                }  
            }
        }
    
        echo wp_json_encode(['response' => ['forms' => $info], 'message' => 'Submitted Form Details', 'status' => 200, 'success' => true]); 
		wp_die();
    } 

    public static function getSubmittedFormList1(){
        global $wpdb;
        $form_details = [];
        $info = [];
       
        $query  = "SELECT distinct(form_name), source_name, form_id FROM {$wpdb->prefix}smack_ulb_databucket_meta";
        $total_query     = "SELECT COUNT(1) FROM (${query}) AS combined_table";
        $total             = $wpdb->get_var( $total_query );
        
        // Records per Page
        $items_per_page = get_option('posts_per_page');
        $page  = isset( $_REQUEST['cpage'] ) ? abs( (int) $_REQUEST['cpage'] ) : 1;
        
        $offset = ( $page * $items_per_page ) - $items_per_page;
        $get_submitted_forms  = $wpdb->get_results( $query . " ORDER BY source_name DESC LIMIT ${offset}, ${items_per_page}" );
        $totalPage = ceil($total / $items_per_page);

        foreach($get_submitted_forms as $submitted_details){
            $form_details['shortcode'] = $submitted_details->form_name;
            $form_details['form_type'] = $submitted_details->source_name;
            $form_details['form_id'] = $submitted_details->form_id;

            $total_submissions = $wpdb->get_var($wpdb->prepare("SELECT count(*) FROM {$wpdb->prefix}smack_ulb_databucket_meta WHERE form_name = %s AND source_name = %s", $submitted_details->form_name, $submitted_details->source_name));

            $form_details['total_submissions'] = $total_submissions;  
            array_push($info, $form_details);   
        }
    
        echo wp_json_encode(['response' => ['forms' => $info , 'total_page' => $totalPage], 'message' => 'All Submitted Forms', 'status' => 200, 'success' => true]); 
		wp_die();
    }

    public static function getSubmittedFormList2(){
        global $wpdb;
        $form_type = sanitize_text_field($_POST['form_type']);
        $form_name = sanitize_text_field($_POST['form_name']);
        $form_id = intval($_POST['form_id']);

        if($form_id == 'null'){
            $form_id = $form_name;
        }

        $form_details = [];
        $info = [];  
        
        $default_column_names = array( 'source_name', 'source_from', 'form_name', 'form_id', 'crm_type');
       // $default_column_names = array( 'first_name', 'last_name', 'email', 'country', 'created_at', 'sync_status', 'id');

        $query  = "SELECT * FROM {$wpdb->prefix}smack_ulb_databucket_meta WHERE source_name = '$form_type' AND form_name = '$form_name' ";
        $total_query     = "SELECT COUNT(1) FROM (${query}) AS combined_table";
        $total             = $wpdb->get_var( $total_query );
        
        // Records per Page
        $items_per_page = get_option('posts_per_page');
        $page  = isset( $_REQUEST['cpage'] ) ? abs( (int) $_REQUEST['cpage'] ) : 1;
        
        $offset = ( $page * $items_per_page ) - $items_per_page;
        $get_submitted_forms  = $wpdb->get_results( $query . " ORDER BY id DESC LIMIT ${offset}, ${items_per_page}", ARRAY_A);
        $totalPage = ceil($total / $items_per_page);

        foreach($get_submitted_forms as $form_keys => $form_values){
            foreach($form_values as $keys => $values){

                if(!in_array($keys , $default_column_names)){
                    $get_field_label = $wpdb->get_var($wpdb->prepare( "SELECT field_label FROM {$wpdb->prefix}smack_ulb_manage_fields WHERE field_name = %s", $keys));
                    
                    if(empty($get_field_label)){
                        $get_field_label = $keys;
                    }
                    if($values == null){
                        $values = '';
                    }

                    if(is_serialized($values)){
                        $get_field_value = unserialize($values);
                        $data_values = '';
                        foreach($get_field_value as $data_val){
                            $data_values .= $data_val . ",";
                        }
                        $values = rtrim($data_values , ',');
                    }

                    if($keys == 'created_at'){
                        $get_field_label = 'submitted_date';
                        $date_format = substr($values, 0, 10);
                        $date = date("M jS, Y", strtotime($date_format)); 
                        $time = date('h:i A', strtotime($values));
                        $submitted_date = $date .' '. $time;
                        $values = $submitted_date;
                    }
                    $form_details[$get_field_label] = $values;
                }
            }

            $form_details = self::$instance->move_to_bottom($form_details , 'submitted_date');
            $form_details = self::$instance->move_to_bottom($form_details , 'sync_status');
            array_push($info , $form_details);
        }
        $list_view = array_keys($info[0]);
        
        echo wp_json_encode(['response' => ['forms' => $info , 'total_page' => $totalPage , 'list_view' => $list_view], 'message' => 'All Submitted Forms', 'status' => 200, 'success' => true]); 
		wp_die();
    }

    public function move_to_bottom(&$array, $key) {
        $value = $array[$key];
        unset($array[$key]);
        $array[$key] = $value;
        return $array;
    }

    public static function displayLists(){
        global $form_type_array;

        $form_type = sanitize_text_field($_POST['form_type']); 
        $form_name = sanitize_text_field($_POST['form_name']);
        $form_id = intval($_POST['form_id']);

        $form_types = $form_type_array[$form_type];

        if($form_id == 'null'){
            $form_id = $form_name;
        }

        $get_lists = get_option("WPULB_{$form_types}_{$form_id}_LISTS");
        if(empty($get_lists)){
            echo wp_json_encode(['response' => ['lists' => []], 'message' => 'Lists not created yet!', 'status' => 200, 'success' => true]); 
        }
        else{
            $lists = array_keys($get_lists);
            echo wp_json_encode(['response' => ['lists' => $lists], 'message' => 'All Lists', 'status' => 200, 'success' => true]); 
        }
        wp_die();
    }

    public static function createNewList(){
        global $wpdb;
        $existing_column_names = array( 'First Name', 'Last Name', 'Email', 'Country', 'submitted_date', 'sync_status');

        $get_all_fields = $wpdb->get_results("SELECT field_label FROM {$wpdb->prefix}smack_ulb_manage_fields ", ARRAY_A);
        $all_column_names = array_column($get_all_fields , 'field_label');
        array_push($all_column_names , 'submitted_date');
        array_push($all_column_names , 'sync_status');
        
        echo wp_json_encode(['response' => ['existing_lists' => $existing_column_names , 'all_lists' => $all_column_names], 'message' => 'All Lists', 'status' => 200, 'success' => true]); 
        wp_die();
    }

    public static function saveOrUpdateList(){
        global $form_type_array;
        
        $list_name = sanitize_text_field($_POST['list_name']);
        $form_type = sanitize_text_field($_POST['form_type']);
        $form_name = sanitize_text_field($_POST['form_name']);
        $form_id = intval($_POST['form_id']);
        $is_edit = sanitize_text_field($_POST['is_edit']);

        $headers =  str_replace("\\" , '' ,sanitize_text_field( $_POST['header']));	
        $headers = json_decode($headers, True);	

        $form_types = $form_type_array[$form_type];
        if($form_id == 'null'){
            $form_id = $form_name;
        }

        if($is_edit == 'false'){
            $get_all_lists = get_option("WPULB_{$form_types}_{$form_id}_LISTS");
            if(!empty($get_all_lists)){
                $get_lists_name = array_keys($get_all_lists);
                if(in_array($list_name , $get_lists_name)){
                    echo wp_json_encode(['response' => '', 'message' => 'List name already exists', 'status' => 200, 'success' => false]); 
                    wp_die();
                }
            } 
            if(!empty($get_all_lists)){
                $get_all_lists[$list_name] = $headers;
                update_option("WPULB_{$form_types}_{$form_id}_LISTS" , $get_all_lists);
            }
            else{
                $list_array = [];
                $list_array[$list_name] = $headers;
                update_option("WPULB_{$form_types}_{$form_id}_LISTS" , $list_array);
            }

            $get_lists = get_option("WPULB_{$form_types}_{$form_id}_LISTS");
            $lists = array_keys($get_lists);

            echo wp_json_encode(['response' => ['lists' => $lists], 'message' => 'List details saved successfully', 'status' => 200, 'success' => true]); 
        }

        elseif($is_edit == 'true'){
            $get_all_lists = get_option("WPULB_{$form_types}_{$form_id}_LISTS");
            $existing_list_name = sanitize_text_field($_POST['old_list_name']);

            if(array_key_exists($existing_list_name , $get_all_lists)){
                unset($get_all_lists[$existing_list_name]);
                $get_all_lists[$list_name] = $headers;
                update_option("WPULB_{$form_types}_{$form_id}_LISTS" , $get_all_lists);
            }

            $get_lists = get_option("WPULB_{$form_types}_{$form_id}_LISTS");
            $lists = array_keys($get_lists);

            echo wp_json_encode(['response' => ['lists' => $lists], 'message' => 'List details updated successfully', 'status' => 200, 'success' => true]); 
        }

        wp_die();
    }

    public static function editList(){
        global $wpdb;
        global $form_type_array;
        $list_name = sanitize_text_field($_POST['list_name']);
        $form_type = sanitize_text_field($_POST['form_type']);
        $form_name = sanitize_text_field($_POST['form_name']);
        $form_id = intval($_POST['form_id']);

        $form_types = $form_type_array[$form_type];
        if($form_id == 'null'){
            $form_id = $form_name;
        }

        $get_list_views = get_option("WPULB_{$form_types}_{$form_id}_LISTS");
        $get_list_names = array_keys($get_list_views);
       
        if(in_array($list_name , $get_list_names)){
            $existing_column_names = $get_list_views[$list_name];
        }
    
        $get_all_fields = $wpdb->get_results("SELECT field_label FROM {$wpdb->prefix}smack_ulb_manage_fields ", ARRAY_A);
        $all_column_names = array_column($get_all_fields , 'field_label');
        array_push($all_column_names , 'submitted_date');
        array_push($all_column_names , 'sync_status');
        
        echo wp_json_encode(['response' => ['existing_lists' => $existing_column_names , 'all_lists' => $all_column_names], 'message' => 'All Lists', 'status' => 200, 'success' => true]); 
        wp_die();
    }

    public static function deleteList(){
        global $form_type_array;
        $list_name = sanitize_text_field($_POST['list_name']);
        $form_type = sanitize_text_field($_POST['form_type']);
        $form_name = sanitize_text_field($_POST['form_name']);
        $form_id = intval($_POST['form_id']);

        $form_types = $form_type_array[$form_type];
        if($form_id == 'null'){
            $form_id = $form_name;
        }

        $get_list_details = get_option("WPULB_{$form_types}_{$form_id}_LISTS");
        unset($get_list_details[$list_name]);
        update_option("WPULB_{$form_types}_{$form_id}_LISTS", $get_list_details);

        $get_lists = get_option("WPULB_{$form_types}_{$form_id}_LISTS");
        $lists = array_keys($get_lists);
         
        echo wp_json_encode(['response' => ['lists' => $lists], 'message' => 'List deleted successfully', 'status' => 200, 'success' => true]); 
        wp_die();
    }

    public static function displayView(){
        global $wpdb;
        global $form_type_array;
        $form_details = [];
        $info = []; 
        $list_name = sanitize_text_field($_POST['list_name']);
        $form_type = sanitize_text_field($_POST['form_type']);
        $form_name = sanitize_text_field($_POST['form_name']);
        $form_id = intval($_POST['form_id']);

        $form_types = $form_type_array[$form_type];
        if($form_id == 'null'){
            $form_id = $form_name;
        }

        $get_list_details = get_option("WPULB_{$form_types}_{$form_id}_LISTS");
        $get_list_headers = $get_list_details[$list_name];

        foreach($get_list_headers as $list_values){
            if($list_values == 'sync_status'){
                $get_field_name[] = 'sync_status';
            }
            elseif($list_values == 'submitted_date'){
                $get_field_name[] =  'created_at';
            }else{
                $get_field_name[] = $wpdb->get_var($wpdb->prepare("SELECT field_name FROM {$wpdb->prefix}smack_ulb_manage_fields WHERE field_label = %s", $list_values ));
            }
        }  
        $list_headers = implode(', ' , $get_field_name);

        $query  = "SELECT $list_headers FROM {$wpdb->prefix}smack_ulb_databucket_meta WHERE source_name = '$form_type' AND form_name = '$form_name' ";
        $total_query     = "SELECT COUNT(1) FROM (${query}) AS combined_table";
        $total             = $wpdb->get_var( $total_query );
        
        // Records per Page
        $items_per_page = get_option('posts_per_page');
        $page  = isset( $_REQUEST['cpage'] ) ? abs( (int) $_REQUEST['cpage'] ) : 1;
        
        $offset = ( $page * $items_per_page ) - $items_per_page;
        $get_submitted_forms  = $wpdb->get_results( $query . " ORDER BY id DESC LIMIT ${offset}, ${items_per_page}", ARRAY_A);
        $totalPage = ceil($total / $items_per_page);      
        foreach($get_submitted_forms as $form_keys => $form_values){
            foreach($form_values as $keys => $values){
                $get_field_label = $wpdb->get_var($wpdb->prepare("SELECT field_label FROM {$wpdb->prefix}smack_ulb_manage_fields WHERE field_name = %s", $keys));
                
                if(empty($get_field_label)){
                    $get_field_label = $keys;
                }
                if($values == null){
                    $values = '';
                }

                if(is_serialized($values)){
                    $get_field_value = unserialize($values);
                    $data_values = '';
                    foreach($get_field_value as $data_val){
                        $data_values .= $data_val . ",";
                    }
                    $values = rtrim($data_values , ',');
                }

                if($keys == 'created_at'){
                    $get_field_label = 'submitted_date';
                    $date_format = substr($values, 0, 10);
                    $date = date("M jS, Y", strtotime($date_format)); 
                    $time = date('h:i A', strtotime($values));
                    $submitted_date = $date .' '. $time;
                    $values = $submitted_date;
                }
                $form_details[$get_field_label] = $values;
            }

            if(array_key_exists('submitted_date' , $form_details)){
                $form_details = self::$instance->move_to_bottom($form_details , 'submitted_date');
            }
            if(array_key_exists('sync_status' , $form_details)){
                $form_details = self::$instance->move_to_bottom($form_details , 'sync_status');
            }
            array_push($info , $form_details);
        }
        $list_view = array_keys($info[0]);
        
        echo wp_json_encode(['response' => ['forms' => $info , 'total_page' => $totalPage , 'list_view' => $list_view], 'message' => 'All Submitted Forms', 'status' => 200, 'success' => true]); 
		wp_die();
    }
}
<?php
/**
* CRM Connector Plus plugin file. 
*
* Copyright (C) 2010-2020, Smackcoders Inc - info@smackcoders.com 
*/

namespace Smackcoders\WPULB;

if ( ! defined( 'ABSPATH' ) ){
    exit; // Exit if accessed directly
}

class ContactFormsSupport
{
	protected static $instance = null;
	protected static $third_party_instance = null;
   
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
			self::$third_party_instance = ThirdPartyForm::getInstance();
        }
        return self::$instance;
    }

    public static function manage_converted_contact_forms($form_type , $form_title , $shortcode , $enabled_fields , $mandatory_fields){
		self::$instance->update_formtitle( $shortcode , $form_title , $form_type );
        $new_id = self::$instance->formatContactFields($form_type , $form_title, $shortcode , $enabled_fields , $mandatory_fields);
		return $new_id;
	}

    public function update_formtitle( $shortcode , $tp_title , $tp_formtype ) 
	{
		global $wpdb;
		$get_checkid = $wpdb->get_results( $wpdb->prepare( "SELECT thirdpartyid FROM {$wpdb->prefix}smack_ulb_formrelation WHERE  shortcode=%s AND thirdparty = %s", $shortcode, 'CONTACT_FORM'));
		if(isset($get_checkid[0])) {
			$checkid = $get_checkid[0]->thirdpartyid;
		} else {
			$checkid = "";
		}
		if( !empty( $checkid ))
		{	
			$wpdb->update( $wpdb->posts , array('post_title' => $tp_title ) , array( 'ID' => $checkid ) );	
		}
		return;
    }
    
    public function formatContactFields($thirdparty_form, $title, $shortcode, $enabled_fields , $mandatory_fields){
		global $wpdb;	
		$checkid = $wpdb->get_var( $wpdb->prepare( "SELECT thirdpartyid FROM {$wpdb->prefix}smack_ulb_formrelation WHERE shortcode =%s AND thirdparty=%s" , $shortcode , 'CONTACT_FORM' ) );
	
		$contact_array = '';
		foreach($enabled_fields as $enabled_values){
			$get_field_details = $wpdb->get_results( $wpdb->prepare( "SELECT field_label, field_type, id FROM {$wpdb->prefix}smack_ulb_manage_fields WHERE field_name = %s", $enabled_values ));	
			foreach($get_field_details as $mainvalue){

				if(in_array($enabled_values , $mandatory_fields)){
					$mandatory = 1;
				}
				else{
					$mandatory = 0;
				}

				$type = $mainvalue->field_type;
				$labl = $mainvalue->field_label;
				// $label = preg_replace('/[^a-zA-Z]+/','_',$labl);
				// $label = ltrim($label,'_');

				$label = $labl;
				$value = $enabled_values;
				
				$cont_array = array();
				$cont_array = $wpdb->get_results( $wpdb->prepare( "SELECT picklist_value FROM {$wpdb->prefix}smack_ulb_manage_fields_picklist_values WHERE field_id = %d", $mainvalue->id));

				$string ="";
				if( !empty( $cont_array ) )
				{
					foreach($cont_array as $val) {
						$string .= "\"{$val->picklist_value}\" ";
					}
				}
				$str = rtrim($string,',');
				if($mandatory == 0)
				{
					$man = "";
					$required = '';
				}
				else
				{
					$man = "*";
					$required = '(required)';
				}
				switch($type)
				{
					case 'Phone':
					case 'Currency':
					case 'Text':
					case 'Integer':
					case 'Decimal':
						$contact_array .= "<p>".  $label ."".$required. "<br />[text".$man." ".  $value."] </p>" ;
						break;
					case 'Email':
						$contact_array .= "<p>".  $label ."".$required. "<br />[email".$man." ". $value."] </p>" ;
						break;
					// case 'url':
					// 	$contact_array .= "<p>".  $label ."".$man. "<br />[url".$man." ". $label."] </p>" ;
					// 	break;
					case 'Select':
					case 'Multi-Select':
						$contact_array .= "<p>".  $label ."".$required. "<br />[select".$man." ". $value." " .$str."] </p>" ;
						$str ="";
						break;
					case 'Boolean':
						$contact_array .= "<p>[checkbox".$man." ". $label." "."label_first "."\" $label\""."] </p>" ;
						break;
					case 'Date':
					case 'DateTime':
						$contact_array .= "<p>".  $label ."".$required. "<br />[date".$man." ". $value." min:1950-01-01 max:2050-12-31 placeholder \"YYYY-MM-DD\"] </p>" ;
						break;
					case '':
						$contact_array .= "<p>".  $label ."".$required. "<br />[text".$man." ".  $value."] </p>" ;
						break;
					default:
						break;
				}
			}
		}
		$contact_array .= "<p><br /> [submit "." \"Submit\""."]</p>";
		$meta = $contact_array;
		//$checkid = $wpdb->get_var( $wpdb->prepare( "select thirdpartyid from wp_smack_ulb_formrelation where shortcode =%s and thirdparty=%s" , $shortcode , 'contactform' ) );
		$checkid = $wpdb->get_var( $wpdb->prepare( "select thirdpartyid from {$wpdb->prefix}smack_ulb_formrelation inner join {$wpdb->prefix}posts on {$wpdb->prefix}posts.ID = {$wpdb->prefix}smack_ulb_formrelation.thirdpartyid and {$wpdb->prefix}posts.post_status='publish' where shortcode =%s and thirdparty=%s" , $shortcode , 'CONTACT_FORM'  ) );

		if(empty($checkid))
		{
			$contform = array (
					'post_title'  => $title,
					'post_content'=> $contact_array,
					'post_type'   => 'wpcf7_contact_form',
					'post_status' => 'publish',
					'post_name'   => $shortcode
					);
			$id = wp_insert_post($contform);
	
			$post_id = $id;
			$meta_key ='_form';
			$meta_value = $meta;
			update_post_meta($post_id,$meta_key,$meta_value);
			
			$wpdb->update( $wpdb->prefix . 'smack_ulb_formrelation' , 
				array( 
					'thirdpartyid' => $id,
				) , 
				array( 'thirdparty' => 'CONTACT_FORM' ,
					'shortcode' => $shortcode
				) 
			);
		}
		else
		{
			$wpdb->update( $wpdb->posts , array( 'post_content' => $contact_array , 'post_title' => $title ) , array( 'ID' => $checkid ) );
			$wpdb->update( $wpdb->postmeta , array( 'meta_value' => $meta ) , array( 'post_id' => $checkid , 'meta_key' => '_form'));
			$id = $checkid;
		}
		$thirdPartyPlugin = $thirdparty_form;
		self::$instance->contactFormRelation($shortcode, $id, $thirdPartyPlugin, $enabled_fields);
		return $id;
    }
    
    /**
     * Contact form relation
     * @param $shortcode
     * @param $id
     * @param $thirdparty
     * @param $enablefields
     */
	public function contactFormRelation($shortcode,$id,$thirdparty,$enablefields)
	{
		global $wpdb;
		//TODO update tables
		$checkid = $wpdb->get_var( $wpdb->prepare( "select thirdpartyid from {$wpdb->prefix}smack_ulb_formrelation where shortcode =%s" , $shortcode ) );
		if(empty($checkid))
		{
			$wpdb->insert( "{$wpdb->prefix}smack_ulb_formrelation" , array( 'shortcode' => $shortcode, 'thirdparty' => $thirdparty , 'thirdpartyid' => $id ) );
		}

		foreach($enablefields as $enabled_values){
			$get_field_details = $wpdb->get_results( $wpdb->prepare( "SELECT field_label, field_type, id FROM {$wpdb->prefix}smack_ulb_manage_fields WHERE field_name = %s", $enabled_values));	

			foreach($get_field_details as $mainvalue){
				$labl = $mainvalue->field_label;
				$labid = preg_replace('/[^a-zA-Z]+/','_',$labl);
				$labid = ltrim($labid,'_');
				//$wpdb->insert( "{$wpdb->prefix}smack_ulb_thirdpartyform_fieldrelation" , array( 'smackshortcodename' => $shortcode , 'smackfieldid' => $value->rel_id , 'smackfieldslable' => $value->display_label , 'thirdpartypluginname' => $thirdparty , 'thirdpartyformid' => $id , 'thirdpartyfieldids' => $labid ) );
			}
		}
	}
	
	public static function all_contact_forms($available_forms){
		global $wpdb;
		$forms = $wpdb->get_results($wpdb->prepare( "SELECT ID, post_title FROM {$wpdb->prefix}posts WHERE post_type = %s AND post_status = %s", 'wpcf7_contact_form', 'publish'), ARRAY_A);
		foreach($forms as $form){
			$available_forms[$form['ID']] = $form['post_title'];
		}
		return $available_forms;
	}
    
    public static function all_contact_fields_types($form_id){
		global $wpdb;
        $contact_post_content = $wpdb->get_row( $wpdb->prepare( "select ID,post_content from $wpdb->posts where ID=%d" , $form_id ) );
        $fields = self::$instance->getTextBetweenBrackets( $contact_post_content->post_content );		
        $contact_form_fieldtype = [];
    
        foreach( $fields as $temp => $field )
        { 
            if( preg_match( '/\s/' , $field ) )
            {
				$field_split_array = explode( ' ' , $field );

				$fieldname = rtrim( $field_split_array[1] , ']' );	
				$field_label = ltrim($field_split_array[0] , '[');
                $contact_form_fieldtype[$fieldname] = $field_label;
            }
        }	
        return $contact_form_fieldtype;
	}
	
	public static function getTextBetweenBrackets($post_content) {

        $data_type_array = array( 'text' , 'email' , 'date' , 'checkbox' , 'select' , 'url' , 'number' , 'textarea' , 'radio' , 'quiz' , 'file', 'acceptance','hidden', 'tel' , 'dynamichidden' );
        $contact_labels = array();
       
		preg_match_all("/\[[^\]]*\]/", $post_content, $matches);
			
		if( !empty( $matches[0] ))
		{
			$contact_labels[] = $matches[0];	
		}
		
		$merge_array = array();
		foreach( $contact_labels as $cf7key => $cf7value )
		{	
			foreach( $cf7value as $cf_get_key => $cf_get_fields )
			{
				if($cf_get_fields != '[submit "Send"]'){
					$merge_array[] = $cf_get_fields;
				}  
			} 	
		}		
		return $merge_array;
	}

	public static function all_contact_fields($form_id){
	
		global $wpdb;
        $contact_post_content = $wpdb->get_row( $wpdb->prepare( "select ID,post_content from $wpdb->posts where ID=%d" , $form_id ) );
        $fields = self::$instance->getTextBetweenBrackets( $contact_post_content->post_content );		
        $contact_form_fields = [];
    
        foreach( $fields as $temp => $field )
        { 
            if( preg_match( '/\s/' , $field ) )
            {
				$field_split_array = explode( ' ' , $field );
                $fieldname = rtrim( $field_split_array[1] , ']' );	
                $contact_form_fields[$fieldname] = ucwords(str_replace('-', ' ', $fieldname));
            }
        }	
        return $contact_form_fields;
	}
    
    /**
	 * contact_form_submission
	 *
	 * @return void
	 */
	function contact_form_submission($arr)
	{
		global $wpdb, $attachments;
		$inserted_id = '';
		$data_bucket_mapping = [];
		$post_id = intval($_POST['_wpcf7']);

		$contact_form_info = get_option("WPULB_INFO_CONTACT_FORM_{$post_id}");
		$connected_addon = $contact_form_info['configured_addon'];
		
		if($connected_addon == 'CRM' || $connected_addon == 'HELPDESK'){
			if($connected_addon == 'CRM'){
				$active_crm = get_option( 'WPULB_ACTIVE_CRM_ADDON' );
				$activated_crm = $contact_form_info['connected_crm'];

				$mapped_array = get_option( "WPULB_DATA_MAPPING_CONTACT_FORM_{$post_id}");
				$addon_array = get_option("WPULB_CRM_MAPPING_CONTACT_FORM_{$post_id}");

			}elseif($connected_addon == 'HELPDESK'){
				$active_crm = get_option( 'WPULB_ACTIVE_HELPDESK_ADDON' );
				$activated_crm = $contact_form_info['connected_help'];

				$mapped_array = get_option( "WPULB_HELP_DATA_MAPPING_CONTACT_FORM_{$post_id}");
				$addon_array = get_option("WPULB_HELP_MAPPING_CONTACT_FORM_{$post_id}");
			}

			if(!empty($mapped_array)){
				// $values = array_values($addon_array);
				// $keys = array_values($mapped_array);
				// $count = min(count($keys), count($values));
				// $data_bucket_mapping =  array_combine(array_slice($keys, 0, $count), array_slice($values, 0, $count));
			
				foreach($mapped_array as $data_key => $data_value){
					if(isset($addon_array[$data_key])){
						$data_bucket_mapping[$data_value] = $addon_array[$data_key];
					}	
				}
			}

		}elseif($connected_addon == 'DATA_BUCKET_ONLY'){
			$activated_crm = 'DataBucket';
			$mapped_array = get_option( "WPULB_DATA_MAPPING_CONTACT_FORM_{$post_id}");
			// $data_bucket_mapping = array_flip($mapped_array);
			$data_bucket_mapping = $mapped_array;
		}
		// $_POST = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);
		// $all_fields = $_POST;
		$all_fields = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);
	
		if(is_array($all_fields)){
			foreach($all_fields as $key=>$value)    {
				// Ignore the fields which are having the prefix as _wpcf7 in the post params
				if(preg_match('/^_wpcf7/',$key)){
					unset($all_fields[$key]);
				}
			}
		}
	
		if(!empty($data_bucket_mapping)){
			$fields_to_store = [];
			foreach($data_bucket_mapping as $form_fieldname => $local_crm_fieldname){
				if(isset($all_fields[$local_crm_fieldname])){
					$fields_to_store[$form_fieldname] = $all_fields[$local_crm_fieldname];
				}
			}
			
			//Attachment field
			if($attachments){
				$fields_to_store['attachments'] = $attachments;
			}
		
			$shortcode_name = $wpdb->get_var($wpdb->prepare( "SELECT post_title FROM {$wpdb->prefix}posts WHERE ID = %d AND post_type = %s", $post_id,'wpcf7_contact_form'));
			$source_name = 'Contact Form';
			$source_from = 'Posts'; // TODO - Get dynamic value

			if(!empty($fields_to_store)){
				$inserted_id = self::$third_party_instance->insert_to_databucket_table($source_name, $source_from, $shortcode_name, $post_id, $activated_crm, $fields_to_store);
			}
		}

		$field_types = self::$instance->all_contact_fields_types($post_id);

		$data_to_send = [];
		$data_to_send['post_array'] = $all_fields;
		$data_to_send['inserted_id'] = $inserted_id;
		$data_to_send['field_types'] = $field_types;
		$data_to_send['form_type'] = 'CONTACT_FORM';

		if($connected_addon == 'CRM' || $connected_addon == 'HELPDESK'){

			if($connected_addon == 'CRM'){
				$data_to_send = self::$third_party_instance->get_crm_info('CONTACT_FORM', $post_id, $data_to_send);
			}
			elseif($connected_addon == 'HELPDESK'){
				$data_to_send = self::$third_party_instance->get_helpdesk_info('CONTACT_FORM', $post_id, $data_to_send);
			}

			$schedule_status = get_option("WPULB_SCHEDULE_STATUS");

			if($schedule_status == 'off'){
				if(!empty($data_bucket_mapping)){
					self::$third_party_instance->update_status_to_databucket_table($inserted_id , 'Pending');
				}
				self::$third_party_instance->form_sync_during_submit($connected_addon, $active_crm, $activated_crm, $data_to_send);
			}elseif($schedule_status == 'on'){
				self::$third_party_instance->update_status_to_databucket_table($inserted_id , 'Schedule - Pending');
			
				$wpdb->insert( 
					"{$wpdb->prefix}smack_ulb_databucket_info", 
					array("data_mapping" => serialize($data_bucket_mapping), "crm_mapping" => serialize($data_to_send['mapping_array']), 'module' => $data_to_send['module'], 
							"owner" => $data_to_send['owner'],  "duplicate" => $data_to_send['duplicate'],  "crm_users" => serialize($data_to_send['crm_users']),
							"old_users" => $data_to_send['old_users'], "connected_addon" => $connected_addon, "activated_addon" => $activated_crm, "field_id" => $inserted_id),
					array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d')
				); 
			}	
		}else{
			self::$third_party_instance->update_status_to_databucket_table($inserted_id , 'CRM/Helpdesk not configured');
		}
	}

}
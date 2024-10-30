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

class Joforce
{
	/**
	 * Joforce Username
	 */
	public $username;

	/**
	 * Joforce Password
	 */
	public $password;

	/**
	 * Joforce app url
	 */
	public $url;

	/**
	 * Joforce API end point
	 */
	public $end_point = 'api/v1';

	protected static $instance = null;

	/**
	 * Joforce auth token
	 */
	public $token;

	public $result_emails;

	public $result_ids;

	public $result_pro_ids;

	public $result_order_ids;

	public $result_products;

	public static function getInstance()
	{
		if (null == self::$instance) {
			self::$instance = new self;
		}
		return self::$instance;
	}

	/**
	 * Joforce Constructor
	 */
	public function __construct()
	{
		$crm_credentials = get_option('WPULB_CONNECTED_JOFORCECRM_CREDENTIALS');
		$this->username = isset($crm_credentials['username']) ? $crm_credentials['username'] :'';
		$this->password = isset($crm_credentials['password']) ? $crm_credentials['password'] :'';
		$this->url = isset($crm_credentials['app_url']) ? $crm_credentials['app_url'] :'';
		$this->token = isset($crm_credentials['access_token']) ? $crm_credentials['access_token'] :'';
	}

	/**
	 * Login to Joforce
	 * 
	 * @return array $response
	 */
	public function login()
	{
		$params = array('username' => $this->username, 'password' => $this->password);
		$url = $this->url . '/' . $this->end_point . '/authorize';
		$response = $this->call($url, $params, 'POST');
		
		// Update the token in the configuration array
		if($response['success']){
			$crm_credentials = get_option('WPULB_CONNECTED_JOFORCECRM_CREDENTIALS');
			$crm_credentials['access_token'] = $response['token'];
			$this->token = $crm_credentials['access_token'];
			update_option('WPULB_CONNECTED_JOFORCECRM_CREDENTIALS', $crm_credentials);
		}
		
		return $response;
	}

	/**
	 * Return Joforce module fields
	 * 
	 * @return array $config_fields
	 */
	public function getCrmFields($module)
	{
		global $wpdb;
	
		$check_modulefields_exists = $wpdb->get_results($wpdb->prepare( "SELECT * FROM {$wpdb->prefix}smack_ulb_outcrm_field_manager WHERE crm_type = %s AND module_type = '%s'", 'joforcecrm', $module ));
		
		if(empty($check_modulefields_exists)){

			$url = $this->url . '/' . $this->end_point . '/' .$module . '/' . 'fields';
			$recordInfo = $this->call($url, array(), 'GET');

			// If token expired, try to login again
			if(isset($recordInfo['success']) && $recordInfo['success'] != true && $recordInfo['code'] == 401)	{
				$this->login();
				$recordInfo = $this->call($url, array(), 'GET');
			}
		
			$config_fields = array();
			if($recordInfo)
			{
				$j = 0;
				for($i = 0; $i<count($recordInfo['fields']); $i++)
				{
					// If type is not set for field, skip that field.
					if(!isset($recordInfo['fields'][$i]['type']['name']))	{
						continue;
					}

					if($recordInfo['fields'][$i]['type']['name'] == 'reference')	{
						//
					}
					elseif($recordInfo['fields'][$i]['name'] == 'modifiedby' || $recordInfo['fields'][$i]['name'] == 'assigned_user_id' )	{
						//
					}
					else{
						$config_fields['fields'][$j] = $recordInfo['fields'][$i];
						$config_fields['fields'][$j]['order'] = $j;
						$config_fields['fields'][$j]['publish'] = 1;
						$config_fields['fields'][$j]['display_label'] = $recordInfo['fields'][$i]['label'];
						if($recordInfo['fields'][$i]['mandatory'] == 1)
						{
							$config_fields['fields'][$j]['wp_mandatory'] = 1;
							$config_fields['fields'][$j]['mandatory'] = 2;
						}
						else
						{
							$config_fields['fields'][$j]['wp_mandatory'] = 0;
						}
						$j++;
					}
				}
			}
			return $config_fields;
		}
	}

	/**
	 * Return users from Joforce
	 * 
	 * @return array $user_details
	 */
	public function getUsersList()
	{
		$page = 1;
		$crm_users = [];
		do {
			$url = $this->url . '/' . $this->end_point . '/Users/list/' . $page;
			$users_list = $this->call($url, array(), 'GET');

			// If token expired, try to login again
			if (isset($users_list['success']) && $users_list['success'] != true && $users_list['code'] == 401) {
				$this->login();
				$users_list = $this->call($url, array(), 'GET');
			}

			if ($users_list) {
				foreach ($users_list['records'] as $record) {
					$crm_users['user_name'][] = $record['user_name'];
					$crm_users['id'][] = $record['id'];
					$crm_users['first_name'][] = $record['first_name'];
					$crm_users['last_name'][] = $record['last_name'];
				}
			}
			$page = $page + 1;
		} while ($users_list['moreRecords'] === true);

		return $crm_users;
	}

	/**
	 * Create a new record to Joforce
	 * 
	 * @param string $module
	 * @param array $module_fields
	 * @return array $data
	 */
	public function createRecord($module, $module_fields)
	{
		$url = $this->url . '/' . $this->end_point . '/' . $module;
		$response = $this->call($url, $module_fields, 'POST');

		// If token expired, try to login again
		if (isset($response['success']) && $response['success'] != true && $response['code'] == 401) {
			$this->login();
			$response = $this->call($url, $module_fields, 'POST');
		}
	
		if(empty($response['record']['createdtime'])){	
			$data['result'] = "Failure";
			$data['failure'] = 1;
			$data['method'] = "Failed";
		} 
		else {
			$data['result'] = "Success";
			$data['failure'] = 0;
			$data['method'] = "Created";
		}
		return $data;
	}

	/**
	 * Update Joforce record 
	 * 
	 * @param string $module
	 * @param array $module_fields
	 * @param id $ids_present
	 * @return array $data
	 */
	public function updateRecord( $module , $module_fields , $ids_present )
	{
		$url = $this->url . '/' . $this->end_point . '/' . $module . '/' . $ids_present;
		$response = $this->call($url, $module_fields, 'PUT');
		// If token expired, try to login again
		if (isset($response['success']) && $response['success'] != true && $response['code'] == 401) {
			$this->login();
			$response = $this->call($url, $module_fields, 'PUT');
		}
	
		
		if(!isset($response['record']['id']))	{
			$data['result'] = "Failure";
			$data['failure'] = 1;
			$data['method'] = "Failed";
		} 
		else {
			$data['result'] = "Success";
			$data['failure'] = 0;
			$data['method'] = "Updated";
		}
		return $data;
	}

	public function createEcomRecord($module, $module_fields , $order_id ){
		$url = $this->url . '/' . $this->end_point . '/' . $module;
		$response = $this->call($url, $module_fields, 'POST');
	
		if (isset($response['success']) && $response['success'] != true && $response['code'] == 401) {
			$this->login();
			$response = $this->call($url, $module_fields, 'POST');
		}
	
		if(empty($response['record']['createdtime'])){	
			$data['result'] = "Failure";
			$data['failure'] = 1;
			$data['method'] = "Failed";
		} 
		else {
			$data['result'] = "Success";
			$data['failure'] = 0;
			$data['method'] = "Created";
		}
	
		if($module == 'Products' && $data['result'] == 'Success'){
			global $wpdb;
			$crm_id = $response['record']['id'];
			$product_no = $response['record']['product_no'];
			
			$get_product_ids = $wpdb->get_results( $wpdb->prepare("SELECT id FROM {$wpdb->prefix}smack_ulb_ecom_info WHERE product_id = %d AND crm_name = %s ", $order_id, 'joforcecrm') ) ;

			end($get_product_ids);
			$get_key = key($get_product_ids);
			$get_product_id = $get_product_ids[$get_key]->id;
			$wpdb->update( "{$wpdb->prefix}smack_ulb_ecom_info" , array( 'crmid' => $crm_id, 'lead_no' => $product_no ) , array( 'id' => $get_product_id ));		
		}

		return $data;
	}

	// Update Ecom Records 
	public function updateEcomRecord( $module , $module_fields , $ids_present, $order_id )
	{
		global $wpdb;
		$url = $this->url . '/' . $this->end_point . '/' . $module . '/' . $ids_present;
		$response = $this->call($url, $module_fields, 'PUT');
	
		// If token expired, try to login again
		if (isset($response['success']) && $response['success'] != true && $response['code'] == 401) {
			$this->login();
			$response = $this->call($url, $module_fields, 'PUT');
		}
		
		if($module == "Products") {
			$crm_id = $response['record']['id'];
			$product_no = $response['record']['product_no'];
		
			$get_product_id = $wpdb->get_var( $wpdb->prepare("SELECT id FROM {$wpdb->prefix}smack_ulb_ecom_info WHERE product_id = %d AND crm_name = %s", $order_id, 'joforcecrm')) ;
			
			$wpdb->update( 
                "{$wpdb->prefix}smack_ulb_ecom_info", 
                array('crmid' => $crm_id, 'lead_no' => $product_no), 
                array('id' => $get_product_id), 
                array('%s', '%s'), 
                array('%d') 
            );
		}
		
		if(!isset($response['record']['id']))	{
			$data['result'] = "Failure";
			$data['failure'] = 1;
			$data['method'] = "Failed";
		} 
		else {
			$data['result'] = "Success";
			$data['failure'] = 0;
			$data['method'] = "Updated";
		}
		return $data;
	}

	// Convert Lead
	// TODO Need to implement this functionality
	public  function convertLead( $module , $crm_id , $order_id , $lead_no, $sales_order)
	{
		global $wpdb;
		$module_fields = [];
		$url = $this->url . '/' . $this->end_point . '/' . $module . '/' . $crm_id;
		$selected_module = $this->call($url, $module_fields, 'GET');

		$update_client = $selected_module;
		$field_replace_array = array('pobox' => 'mailingpobox' , 'city' => 'mailingcity' , 'state' => 'mailingstate' ,'country' => 'mailingcountry' , 'street' => 'mailingstreet' , 'designation' => 'title' );

		if( !empty( $update_client ))
		{
			foreach($update_client as $key => $value){
				foreach( $field_replace_array as $rep_key => $rep_val )
				{
					if( $rep_key == $key )
					{
						$selected_module[$rep_val] = $value;
					}
					else
					{
						$selected_module[$key] = $value;
					}	
				}
			}
		}
		$url = $this->url . '/' . $this->end_point . '/' . 'Contacts';
		$contact_record = $this->call($url, $selected_module, 'POST');
		$cont_no = $contact_record['id'] ;
	
		$this->delete_record($lead_no , $module);
		
		$wpdb->update( "{$wpdb->prefix}smack_ulb_ecom_info" , array('contact_no' => $cont_no) , array( 'order_id' => $order_id ) );
		
		if($contact_record)
		{
			$data['result'] = "Success";
			$data['failure'] = 0;
			$data['method'] = "Created";
		}
		else
		{
			$data['result'] = "Failure";
			$data['failure'] = 1;
			$data['method'] = "Failed";
		}
		return $data;
	}

	public function create_sales_order( $order_id, $sales_order)
	{
		global $wpdb;
		$sales_order['account_id'] = $this->get_organization_id();
		$sales_order['currency_id'] = 1;
	
		$temp = 0;
		$line_item = [];
		foreach($sales_order['product_details'] as $pro_values){
			$product_name = $pro_values['product_name'];
			$is_product_present = $this->checkProductPresent('Products', $product_name, 'specific_search');
		
			if($is_product_present){
				$line_item[$temp]['productid'] = $is_product_present;
				$line_item[$temp]['listprice'] = $pro_values['product_total'];
				$line_item[$temp]['quantity'] = $pro_values['product_qty'];
				$line_item[$temp]['product_name'] = $product_name;
				$line_item[$temp]['entity_type'] = 'Products';
			}else{
				$product_array = [];
				$product_array['productname'] = $product_name;
				$product_array['unit_price'] = $pro_values['product_total'];
				$product_array['qtyinstock'] = $pro_values['product_qty'];
				$product_array['discontinued'] = 1;
				
				$pro_url = $this->url . '/' . $this->end_point . '/Products';
				$pro_response = $this->call($pro_url, $product_array, 'POST');
			
				if (isset($pro_response['success']) && $pro_response['success'] != true && $pro_response['code'] == 401) {
					$this->login();
					$pro_response = $this->call($pro_url, $product_array, 'POST');
				}
				$pro_product_id = $pro_response['record']['id'];
			
				$get_product_id_url = $this->url . '/' . $this->end_point . '/Products/'. $pro_response['record']['id'];
				$get_pro_response = $this->call($get_product_id_url, array(), 'GET');
			
				$line_item[$temp]['productid'] = $pro_product_id;
				$line_item[$temp]['listprice'] = $pro_values['product_total'];
				$line_item[$temp]['quantity'] = $pro_values['product_qty'];
				$line_item[$temp]['entity_type'] = 'Products';	
			}
			$temp++;
		}
		$sales_order['productid'] = $line_item[0]['productid'];
		$sales_order['LineItems'] = $line_item;
		unset($sales_order['product_details']);
	
		$sales_url = $this->url . '/' . $this->end_point . '/SalesOrder';
		$sales_response = $this->call($sales_url, $sales_order, 'POST');

		if (isset($sales_response['success']) && $sales_response['success'] != true && $sales_response['code'] == 401) {
			$this->login();
			$sales_response = $this->call($sales_url, $sales_order, 'POST');
		}
	
		$sales_orderid = $sales_response['id'];
		$salesorder_no = $sales_response['salesorder_no'];
	
		$get_order_ids = $wpdb->get_results( $wpdb->prepare( "SELECT id FROM {$wpdb->prefix}smack_ulb_ecom_info WHERE order_id = %d AND crm_name = %s ", $order_id, 'joforcecrm' ) ) ;
		end($get_order_ids);
		$get_key = key($get_order_ids);
		$get_order_id = $get_order_ids[$get_key]->id;
		
		$wpdb->update( "{$wpdb->prefix}smack_ulb_ecom_info" , array( 'sales_orderid' => $sales_orderid, 'lead_no' => $salesorder_no ) , array( 'id' => $get_order_id ));		
		
		if($sales_response)
		{
			$data['result'] = "Success";
			$data['failure'] = 0;
			$data['method'] = "Created";
		}
		else
		{
			$data['result'] = "Failure";
			$data['failure'] = 1;
			$data['method'] = "Failed";
		}
		return $data;
	} 

	public function update_sales_order( $order_id, $sales_order, $ids_present)
	{
		global $wpdb;
		$sales_order['account_id'] = $this->get_organization_id();
		$sales_order['currency_id'] = 1;

		$temp = 0;
		$line_item = [];
		foreach($sales_order['product_details'] as $pro_values){
			$product_name = $pro_values['product_name'];
			$is_product_present = $this->checkProductPresent('Products', $product_name, 'specific_search');
		
			if($is_product_present){

				/* Update product */
				$product_update_array = [];
				$product_update_array['unit_price'] = $pro_values['product_price'];
				$product_update_array['qtyinstock'] = $pro_values['product_qty'];
				$product_update_array['discontinued'] = 1;

				$pro_update_url = $this->url . '/' . $this->end_point . '/Products/' . $is_product_present;
				$pro_update_response = $this->call($pro_update_url, $product_update_array, 'PUT');
				if (isset($pro_update_response['success']) && $pro_update_response['success'] != true && $pro_update_response['code'] == 401) {
					$this->login();
					$pro_update_response = $this->call($pro_update_url, $product_update_array, 'PUT');
				}

				$line_item[$temp]['productid'] = $is_product_present;
				$line_item[$temp]['listprice'] = $pro_values['product_total'];
				$line_item[$temp]['quantity'] = $pro_values['product_qty'];
				$line_item[$temp]['product_name'] = $product_name;
				$line_item[$temp]['entity_type'] = 'Products';
	
			}else{

				$product_array = [];
				$product_array['productname'] = $product_name;
				$product_array['unit_price'] = $pro_values['product_price'];
				$product_array['qtyinstock'] = $pro_values['product_qty'];
				$product_array['discontinued'] = 1;
				
				$pro_url = $this->url . '/' . $this->end_point . '/Products';
				$pro_response = $this->call($pro_url, $product_array, 'POST');
			
				if (isset($pro_response['success']) && $pro_response['success'] != true && $pro_response['code'] == 401) {
					$this->login();
					$pro_response = $this->call($pro_url, $product_array, 'POST');
				}
				$pro_record = $pro_response['record']['id'];

				$line_item[$temp]['productid'] = $pro_record;
				$line_item[$temp]['listprice'] = $pro_values['product_total'];
				$line_item[$temp]['quantity'] = $pro_values['product_qty'];
				$line_item[$temp]['product_name'] = $product_name;
				$line_item[$temp]['entity_type'] = 'Products';
	
			}
			$temp++;
		}

		$sales_order['productid'] = $line_item[0]['productid'];
		$sales_order['LineItems'] = $line_item;
		unset($sales_order['product_details']);

		/* Update salesorder*/
		$sales_update_url = $this->url . '/' . $this->end_point . '/SalesOrder/' . $ids_present;
		$sales_update_response = $this->call($sales_update_url, $sales_order, 'PUT');

		if (isset($sales_update_response['success']) && $sales_update_response['success'] != true && $sales_update_response['code'] == 401) {
			$this->login();
			$sales_update_response = $this->call($sales_update_url, $sales_order, 'PUT');
		}
		$sales_orderid = $sales_update_response['id'];
		$salesorder_no = $sales_update_response['salesorder_no'];
		
		$get_order_ids = $wpdb->get_results( $wpdb->prepare( "SELECT id FROM {$wpdb->prefix}smack_ulb_ecom_info WHERE order_id = %d AND crm_name = %s ", $order_id, 'joforcecrm'));

		end($get_order_ids);
		$get_key = key($get_order_ids);
		$get_order_id = $get_order_ids[$get_key]->id;
		
		$wpdb->update( "{$wpdb->prefix}smack_ulb_ecom_info" , array( 'sales_orderid' => $sales_orderid, 'lead_no' => $salesorder_no ) , array( 'id' => $get_order_id ));		
		
		if($sales_update_response)
		{
			$data['result'] = "Success";
			$data['failure'] = 0;
			$data['method'] = "Updated";
		}
		else
		{
			$data['result'] = "Failure";
			$data['failure'] = 1;
			$data['method'] = "Failed";
		}
		return $data;
	} 

	// ECOM Check product already present
	public function checkProductPresent( $module , $product , $search_method )
	{
		$result_pro_ids = array();
		$url = $this->url . '/' . $this->end_point . '/' . $module . '/search/productname/' . $product;
		$response = $this->call($url, array(), 'GET');
	
		if(isset($response['records']) && count($response['records']) > 0)	{
			if($search_method == 'specific_search'){
				return $response['records'][0]['id'];
			}

			foreach($response['records'] as $record){
				$result_pro_ids[] = $record['id'];
			}
			$this->result_pro_ids = $result_pro_ids;
			return true;
		}
		return false;
	}

	public function checkOrderPresent( $module , $order_no )
	{
		$result_order_ids = array();
		$url = $this->url . '/' . $this->end_point . '/' . $module . '/search/salesorder_no/' . $order_no;
		$response = $this->call($url, array(), 'GET');
	
		if(isset($response['records']) && count($response['records']) > 0)	{
			foreach($response['records'] as $record){
				$result_order_ids[] = $record['id'];
			}
			$this->result_order_ids = $result_order_ids;
			return true;
		}
		return false;
	}

	/**
	 * Check email present in the module
	 * @param string $module
	 * @param string $email
	 * @return boolean
	 */
	public function checkEmailPresent( $module , $email )
	{
		$result_emails = array();
		$result_ids = array();
		$url = $this->url . '/' . $this->end_point . '/' . $module . '/search/email/' . $email;
		$response = $this->call($url, array(), 'GET');
		// If token expired, try to login again
		if (isset($response['success']) && $response['success'] != true && $response['code'] == 401) {
			$this->login();
			$response = $this->call($url, array(), 'GET');
		}

		if(isset($response['records']) && count($response['records']) > 0)	{
			foreach($response['records'] as $record)	{
				$record_email=$record['email'];
				if(strpos($record_email, '</')){

					$record_email = explode('</', $record_email);
					if(strpos($record_email[0], '">')){
						$record_email = explode('">', $record_email[0]);
					}
				}

				$result_emails[] = $record_email[1];
				$result_ids[] = $record['id'];
			}
			$this->result_emails = $result_emails;
			$this->result_ids = $result_ids;
			return true;
		}
		return false;
	}

	public function delete_record($id , $module){
		$url = $this->url . '/' . $this->end_point . '/' . $module . '/' . $id;
		$params = array('id' => $id);
		$response = $this->call($url, $params, 'DELETE');

		// If token expired, try to login again
		if (isset($response['success']) && $response['success'] != true && $response['code'] == 401) {
			$this->login();
			$response = $this->call($url, $id, 'DELETE');
		}
	}

	public function get_organization_id(){
		$url = $this->url . '/' . $this->end_point . '/Accounts/list/1';
		$response = $this->call($url, array(), 'GET');

		if (isset($response['success']) && $response['success'] != true && $response['code'] == 401) {
			$this->login();
			$response = $this->call($url, array(), 'GET');
		}

		$organization_id = $response['records'][0]['id'];
		return $organization_id;
	}

	/**
	 * Call to CRM
	 * 
	 * @param string $url
	 * @param array $params
	 * @param string $method
	 */
	public function call($url, $params, $method)
	{
		if($method == 'PUT')	{
			$post_params = null;
			foreach($params as $key => $value)	{ 
				$post_params .= $key.'='.$value.'&'; 
			}
			rtrim($post_params, '&');
		}
		else	{
			$post_params = $params;
		}

		$headers = array( 'Authorization' => 'Bearer '. $this->token,
							'Cache-Control' =>  'no-cache'
								//'Content-Type' => 'application/json'
						);
			
		$args = array(
			'method' => $method,
			'sslverify' => false,
			'body' => $post_params,
			'headers' => $headers
			);
			
		$result = wp_remote_post($url, $args ) ;
		$response = wp_remote_retrieve_body($result);
		$http_code = wp_remote_retrieve_response_code($result);

		$result_array = json_decode($response,TRUE);
		return $result_array;
	}

    /**
     * getRoundRobinBasedAssignedTo
     *
     * @param  mixed $sync_for
     *
     * @return void
     */
    public function getRoundRobinBasedAssignedTo($crm_users , $old_assigned_to_user, $user_option_name){

        // Return the first user if already not set
        if(!$old_assigned_to_user){
            $user_key = array_keys($crm_users);
            $assigned_user = reset($user_key);

            $user = $crm_users[$assigned_user]['user_id'];
            update_option($user_option_name, $user);
            return $user;
        }

        $temp = false;
        for( $i = 0 ; $i < count($crm_users) ; $i++){
            if($crm_users[$i]['user_id'] == $old_assigned_to_user){
                $temp = true;
               
                if($i == count($crm_users) - 1){
                    update_option($user_option_name, $crm_users[0]['user_id']);
                    return $crm_users[0]['user_id'];
                }
                continue;
            }

            if($temp){
                update_option($user_option_name, $crm_users[$i]['user_id']);
                return $crm_users[$i]['user_id'];
            }
        }
    }
}
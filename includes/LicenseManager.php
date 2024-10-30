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
 * Class LicenseManager
 * @package Smackcoders\WPULB
 */
class LicenseManager{
	protected static $instance = null;

	/**
	 * LicenseManager constructor.
	 */
	public function __construct()
	{
		$this->plugin = WPULBPlugin::getInstance();
	}

	/**
	 * LicenseManager Instances.
	 */
	public static function getInstance() {
		if ( null == self::$instance ) {
			self::$instance = new self;
			self::$instance->doHooks();
		}
		return self::$instance;
	}

	public function doHooks(){
		add_action('wp_ajax_wpulb_buynow_click', array($this, 'buyNowClick'));
		add_action('wp_ajax_wpulb_license_tab', array($this, 'licenseTabActivation'));
		add_action('wp_ajax_wpulb_send_licensekey', array($this, 'verifyLicenseFromInApp'));
		add_action('wp_ajax_wpulb_verify_license', array($this, 'verifyLicenseFromSeperateAddon'));
		add_action('wp_ajax_wpulb_installed_addons', array($this, 'installedAddons'));
		add_action('wp_ajax_wpulb_send_billing_details', array($this, 'billingDetails'));
		add_action('wp_ajax_wpulb_get_licensekey_details', array($this, 'getLicenseDetails'));
		add_action('wp_ajax_wpulb_licensekey_details_tab', array($this, 'licenseKeyTabActivation'));
	}

	public static function buyNowClick(){
		$selected_addons =wp_unslash(sanitize_text_field($_POST['addons']) );	
	//	$selected_addons = str_replace("\\" , '' , $_POST['addons']);	
		$selected_addons = json_decode($selected_addons, True);	

		foreach($selected_addons as $selected_addon){

			update_option("WPULB_SELECT_BUYNOW_ADDONS", $selected_addon);

		}
		$addon_value = array_unique($selected_addons, SORT_REGULAR);
		$addon_array = array_values($addon_value);

		$urlparts = parse_url(home_url());
		$domain_name = $urlparts['host'];
		//$domain_name = $_SERVER['HTTP_HOST'];
		echo wp_json_encode(['response' => ['addon_array' => $addon_array], 'message' => 'Selected Addons','domain_name'=>$domain_name, 'status' => 200, 'success' => true]);
		wp_die();
	}

	public static function licenseTabActivation(){
		$get_select_buynow_addons = get_option("WPULB_SELECT_BUYNOW_ADDONS");
		$inapp_purchase = get_option("INAPP_PURCHASE");
		$get_purchase_status = get_option("WPULB_PURCHASE_STATUS");
		$tab_activation = get_option('WPULB_LICENSE_TAB');
		$tab_activation_status = get_option('WPULB_LICENSE_TAB_ACTIVE');
		$installed_addon = get_option('WPULB_ADDON_SLUG');
		if(!empty($get_select_buynow_addons) && $inapp_purchase == 'true' && $get_purchase_status == 'true' && $tab_activation =='true'){	
			echo wp_json_encode(['response' => ['license_tab' => true, 'status' => 200, 'success' => true]]);
		}
		elseif( $tab_activation_status == 'true' && !empty($installed_addon)){
			$addon_name = self::$instance->getAddonName($installed_addon);
			$slug = self::$instance->getAddonSlug($addon_name);
			$addon_slug = $slug['url'];
			if ( self::is_plugin_installed( $addon_slug ) ) {
				echo wp_json_encode(['response' => ['license_tab' => true,'addon_slug'=>$installed_addon, 'status' => 200, 'success' => true]]);
			}
			else{
				echo wp_json_encode(['response' => ['license_tab' => false, 'status' => 200, 'success' => true]]);
			}
		}		
		else{
			echo wp_json_encode(['response' => ['license_tab' => false, 'status' => 200, 'success' => true]]);
		}
		wp_die();
	}

	public static function billingDetails(){
		$postdata  = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);
		update_option("BILLING_DETAILS",$postdata);
		update_option("WPULB_PURCHASE_STATUS",'true');
		update_option("INAPP_PURCHASE",'true');
		update_option("WPULB_LICENSE_TAB",'true');
		echo wp_json_encode(['response' => ['status' => 200, 'success' => true]]);  
	}

	public static function verifyLicenseFromInApp(){
		$license_key = sanitize_text_field($_POST['license_key']);
		$urlparts = parse_url(home_url());
		$domain_name = $urlparts['host'];
		$url ='https://www.smackcoders.com/?rest_route=/licensemanager/v1/getproducts&key='.$license_key.'&domain_url='.$domain_name;
	       
		$headers = array( 'Content-Type' => 'application/json');
		$args = array(
				'method' => 'POST',
				'sslverify' => false,
				'headers' => $headers
				);

        $result = wp_remote_post($url, $args ) ;
		$result_value = wp_remote_retrieve_body($result);
		$result_array = json_decode($result_value, true);
	
		$package_info = $result_array['productinfo'];
		if($result_array['success'] == 1){
			$download_response = self::$instance->downloadPackage($package_info,$license_key);
			foreach($download_response as $dkey => $dval){
				if($dval['success'] == true){
					update_option("INAPP_PURCHASE",'false');
					update_option("WPULB_LICENSE_TAB",'false');
					$message[$dkey] = 'License Key Verified '. $dval['message'];
				}
				else{
					$message[$dkey] = 'License key verified ,Write Permission Denied for WP-Content,Could not Download Package from Myaccount Section,You Can Download It Manually';
				}
			}
			echo wp_json_encode(['response' => '' ,'is_license_vefified' => true, 'message' => $message , 'status' => 200, 'success' => true]);				
		}	
		else{
			echo wp_json_encode(['response' => '','is_license_vefified' => false, 'message' => 'Invalid License Key,Please Check Your License Key in Myaccount Section', 'status' => 200, 'success' => false]);	
		}
		wp_die();	
	}

	public static function installedAddons(){
		
		// $addons_list = array('Vtiger Pro Plus','Sugar Pro Plus','Suite Pro Plus','Salesforce Pro Plus','JoforceCRM Pro Plus','Freshsales Pro Plus','Zoho CRM Pro Plus','Zoho Desk Pro Plus','Freshdesk Pro Plus','Zendesk Pro Plus','Vtiger Ticket Pro Plus','Woocommerce Sync Pro Plus','User Sync Pro Plus','Gravity Form Pro Plus','Qu Form Pro Plus','Contact Form Pro Plus','Ninja Form WP Pro Plus','WP Form Pro Plus','Caldera Form Pro Plus','Leads Multi Forms Pro Plus');
		$addons_list = array('Vtiger Pro Plus','Sugar Pro Plus','Suite Pro Plus','Salesforce Pro Plus','Freshsales Pro Plus','Zoho CRM Pro Plus','Zoho Desk Pro Plus','Freshdesk Pro Plus','Zendesk Pro Plus','Vtiger Ticket Pro Plus','Woocommerce Sync Pro Plus','User Sync Pro Plus','Gravity Form Pro Plus','Qu Form Pro Plus','Contact Form Pro Plus','Ninja Form WP Pro Plus','WP Form Pro Plus','Caldera Form Pro Plus','Leads Multi Forms Pro Plus');
		$installed_addon = [];
		foreach($addons_list as $key => $value ){
			$slug = self::$instance->getAddonSlug($value);
			$addon_url = $slug['url'];
			$addon_slug = $slug['slug'];

			if ( self::is_plugin_installed( $addon_url ) ) {
				$installed_addon[] = $addon_slug;
			}		
		}
		echo wp_json_encode(['response' => '','installed_addon' => $installed_addon, 'status' => 200, 'success' => true]);	
		wp_die();
	}

	public static function verifyLicenseFromSeperateAddon(){

		$license_key = sanitize_text_field($_POST['license_key']);
		$addon_slug = sanitize_text_field($_POST['addon_slug']);
		$addon_name = self::$instance->getAddonName($addon_slug);
		$urlparts = parse_url(home_url());
		$domain_name = $urlparts['host'];
		$url ='https://www.smackcoders.com/?rest_route=/licensemanager/v1/isvalid&product_slug='.$addon_slug.'&key='.$license_key.'&domain_url='.$domain_name;
		
		$headers = array( 'Content-Type' => 'application/json');
		$args = array(
				'method' => 'POST',
				'sslverify' => false,
				'headers' => $headers
				);

		$result = wp_remote_post($url, $args ) ;
		$result_value = wp_remote_retrieve_body($result);
		$result_array = json_decode($result_value, true);
	
		if($result_array['success'] == 'true'){
			$get_domain_products = self::$instance->getDomainProducts($license_key,$domain_name);
			$get_products = $get_domain_products['productinfo'];
			if($get_domain_products['success'] == 1){
				foreach($get_products as $prod_key => $prod_val){
					$addon_name = $prod_val['product_name']; 
					$get_slug = self::$instance->getAddonSlug($addon_name);
					$addon_slug = $get_slug['url'];
					if ( self::is_plugin_installed( $addon_slug ) ) {
						$activate_addon = self::$instance->activateAddon($addon_slug,$license_key);
						$response_message[$prod_key] = 'License Key Verified for '.' '.$addon_name.' '.$activate_addon;
					}
					else{
						$response_message[$prod_key] = 'License Key Verified for '.' '.$addon_name.' '.'and Could not be activated';
					}
				}
				update_option('WPULB_LICENSE_TAB_ACTIVE', 'false');
				echo wp_json_encode(['response' => '' ,'is_license_vefified' => true, 'message' => $response_message, 'status' => 200, 'success' => true]);					
			}
			else{
				echo wp_json_encode(['response' => '' ,'is_license_vefified' => true, 'message' => 'Invalid License Key', 'status' => 200, 'success' => false]);					
			}
		}
		else{
			echo wp_json_encode(['response' => '','is_license_vefified' => false, 'message' => $result_array['message'].' '.$addon_name, 'status' => 200, 'success' => false]);	
		}
		wp_die();	
	}

	public static function getDomainProducts($license_key,$domain_name){
		$url ='https://www.smackcoders.com/?rest_route=/licensemanager/v1/getproducts&key='.$license_key.'&domain_url='.$domain_name;
		
		$headers = array( 'Content-Type' => 'application/json');
		$args = array(
				'method' => 'POST',
				'sslverify' => false,
				'headers' => $headers
				);

		$result = wp_remote_post($url, $args ) ;
		$result_value = wp_remote_retrieve_body($result);
		$result_array = json_decode($result_value, true);
		return $result_array;
	}

	public static function verifyLicenseKey($license_key,$addon_slug){
		$urlparts = parse_url(home_url());
		$domain_name = $urlparts['host'];
		$url ='https://www.smackcoders.com/?rest_route=/licensemanager/v1/isvalid&product_slug='.$addon_slug.'&key='.$license_key.'&domain_url='.$domain_name;
	
		$headers = array( 'Content-Type' => 'application/json');
		$args = array(
				'method' => 'POST',
				'sslverify' => false,
				'headers' => $headers
			);

		$result = wp_remote_post($url, $args ) ;
		$result_value = wp_remote_retrieve_body($result);
		$result_array = json_decode($result_value, true);
		return $result_array;
	}

	public static function getAddonName($addon_name){
		switch($addon_name){
		case 'vtiger-pro-plus':
			$plugin_name = 'Vtiger Pro Plus';
			break;

		case 'sugar-pro-plus':
			$plugin_name = 'Sugar Pro Plus';
			break;

		case 'suite-pro-plus':
			$plugin_name = 'Suite Pro Plus';
			break;

		case 'salesforce-pro-plus':
			$plugin_name = 'Salesforce Pro Plus';
			break;

		// case 'joforcecrm-pro-plus':
		// 	$plugin_name = 'JoforceCRM Pro Plus';
		// 	break;

		case 'freshsales-pro-plus':
			$plugin_name = 'Freshsales Pro Plus';
			break;

		case 'zoho-crm-pro-plus':
			$plugin_name = 'Zoho CRM Pro Plus';
			break; 

		case 'zoho-desk-pro-plus':
			$plugin_name = 'Zoho Desk Pro Plus';
			break;

		case 'freshdesk-pro-plus':
			$plugin_name = 'Freshdesk Pro Plus';
			break;

		case 'zendesk-pro-plus':
			$plugin_name = 'Zendesk Pro Plus';
			break;

		case 'vtiger-ticket-pro-plus':
			$plugin_name = 'Vtiger Ticket Pro Plus';
			break;

		case 'woocommerce-sync-pro-plus':
			$plugin_name = 'Woocommerce Sync Pro Plus';
			break;

		case 'user-sync-pro-plus':
			$plugin_name = 'User Sync Pro Plus';
			break;

		case 'gravity-form-pro-plus':
			$plugin_name = 'Gravity Form Pro Plus';
			break;

		case 'qu-form-pro-plus':
			$plugin_name = 'Qu Form Pro Plus';
			break;

		case 'contact-form-pro-plus':
			$plugin_name = 'Contact Form Pro Plus';
			break;

		case 'ninja-form-wp-pro-plus':
			$plugin_name = 'Ninja Form WP Pro Plus';
			break;
		
		case 'wp-form-pro-plus':
			$plugin_name = 'WP Form Pro Plus';
			break;

		case 'caldera-form-pro-plus':
			$plugin_name = 'Caldera Form Pro Plus';
			break;

		case 'leads-multi-forms-pro-plus':
			$plugin_name = 'Leads Multi Forms Pro Plus';
			break;
		}
		return $plugin_name;
	}


	public static function downloadPackage($package_info,$license_key){
		foreach($package_info as $pack_key => $pack_val){
			$file_url = $pack_val['download_urls'][0];
			$addon_name = $pack_val['product_name']; 
			$addon_slug = self::$instance->getAddonSlug($addon_name);
			$slug = $addon_slug['url'];
			$downloadable_file_name = $addon_slug['slug'];
		
			if ( self::is_plugin_installed( $slug ) ) {
				if(!is_plugin_active( $slug )){
					$response[$pack_key]['success'] = true;
					$response[$pack_key]['message'] = self::$instance->activateAddon($slug,$license_key);
					$response[$pack_key]['message'] = $addon_name.$response[$pack_key]['message'];
				}
				else{
					$response[$pack_key]['success'] = true;
					$response[$pack_key]['message'] = $addon_name. ' Already activated';
				}
			}
			else{
				if(strstr($file_url, 'https://www.dropbox.com/')) {
					$filename = basename($file_url);
					$get_local_filename = explode('?', $filename);
					$url_file_name = $get_local_filename[0];
					$url_file_name = str_replace('%20', ' ', $url_file_name);	
				}		

				$zip_response = [];
				$result = wp_remote_retrieve_body( wp_remote_get($file_url) );
		
				if(is_wp_error($result)){
					$response[$pack_key]['success'] = false;						
					$response[$pack_key]['message'] = $result->get_error_message();
				}else{		
					$plugin_dir = WP_PLUGIN_DIR;
					$path = $plugin_dir.'/'.$downloadable_file_name.'.zip';
					$extract_path = $plugin_dir.'/';
		
					$file = fopen($path , "w+");
					fputs($file, $result);
					chmod($path, 0777);
					chmod($extract_path, 0777);
					$zip_response = self::$instance->zip_upload($path , $extract_path);
			
					if($zip_response == 'Zip Extracted'){
						$response[$pack_key]['success'] = true;
							if($downloadable_file_name =='all-in-one-crm-leads-plus'){
								$addon_array = self::$instance->getAddons();
									foreach($addon_array as $addon_key => $addon_val){
										$response[$addon_key]['success'] = true;
										$slug_addon = self::$instance->getAddonSlug($addon_val);
										$activate_slug  = $slug_addon['url'];
										$license_validation = self::$instance->verifyLicenseKey($license_key,$downloadable_file_name);
										if($license_validation['success'] == 1){
											$res[$addon_key]['message'] = self::$instance->activateAddon($activate_slug,$license_key);
											$response[$addon_key]['message'] = $addon_val.' '.$res[$addon_key]['message'];
										}
								}
							}
							else{
								$license_validation = self::$instance->verifyLicenseKey($license_key,$downloadable_file_name);
								if($license_validation['success'] == 1){
									$response[$pack_key]['message'] = self::$instance->activateAddon($slug,$license_key);	
									$response[$pack_key]['message'] = $addon_name.' '.$response[$pack_key]['message'];	
								}
								else{
									$response[$pack_key]['message'] = $license_validation['message'];
									$response[$pack_key]['message'] = $addon_name.' '.$response[$pack_key]['message'];	
								}
								}
					}
					else{
						$response[$pack_key]['success'] = false;
						$response[$pack_key]['message'] = 'Error Occured while extracting zip file.';
					}
				}
			}
		}				
		return $response;
	}

	public static function activateAddon($plugin_slug,$license_key){	
		switch($plugin_slug){
		case 'vtiger-pro-plus/vtiger-pro-plus.php':
			$license_name = 'WPULB_VTIGERCRM_LICENSE';
			$addon_status = 'WPULB_ADDON_STATUS_VTIGERCRM';
			$license_key_option = 'WPULB_VTIGERCRM_LICENSE_KEY';
			break;

		case 'salesforce-pro-plus/salesforce-pro-plus.php':
			$license_name = 'WPULB_SALESFORCECRM_LICENSE';
			$addon_status = 'WPULB_ADDON_STATUS_SALESFORCE';
			$license_key_option = 'WPULB_SALESFORCECRM_LICENSE_KEY';
			break;

		case 'freshsales-pro-plus/freshsales-pro-plus.php':
			$license_name = 'WPULB_FRESHSALES_LICENSE';
			$addon_status = 'WPULB_ADDON_STATUS_FRESHSALES';
			$license_key_option = 'WPULB_FRESHSALES_LICENSE_KEY';
			break;

		case 'suite-pro-plus/suite-pro-plus.php':
			$license_name = 'WPULB_SUITECRM_LICENSE';
			$addon_status = 'WPULB_ADDON_STATUS_SUITECRM';
			$license_key_option = 'WPULB_SUITECRM_LICENSE_KEY';
			break;

		case 'sugar-pro-plus/sugar-pro-plus.php':
			$license_name = 'WPULB_SUGARCRM_LICENSE';
			$addon_status = 'WPULB_ADDON_STATUS_SUGARCRM';
			$license_key_option = 'WPULB_SUGARCRM_LICENSE_KEY';
			break;

		// case 'joforcecrm-pro-plus/joforcecrm-pro-plus.php':
		// 	$license_name = 'WPULB_JOFORCECRM_LICENSE';
		// 	$addon_status = 'WPULB_ADDON_STATUS_JOFORCECRM';
		// 	$license_key_option = 'WPULB_JOFORCECRM_LICENSE_KEY';
		// 	break;

		case 'zoho-crm-pro-plus/zoho-crm-pro-plus.php':
			$license_name = 'WPULB_ZOHOCRM_LICENSE';
			$addon_status = 'WPULB_ADDON_STATUS_ZOHOCRM';
			$license_key_option = 'WPULB_ZOHOCRM_LICENSE_KEY';
			break;

		case 'zoho-desk-pro-plus/zoho-desk-pro-plus.php':
			$license_name = 'WPULB_ZOHODESK_LICENSE';	
			$addon_status = 'WPULB_ADDON_STATUS_ZOHODESK';
			$license_key_option = 'WPULB_ZOHODESK_LICENSE_KEY';
			break;

		case 'zendesk-pro-plus/zendesk-pro-plus.php':
			$license_name = 'WPULB_ZENDESK_LICENSE';
			$addon_status = 'WPULB_ADDON_STATUS_ZENDESK';
			$license_key_option = 'WPULB_ZENDESK_LICENSE_KEY';
			break;

		case 'freshdesk-pro-plus/freshdesk-pro-plus.php':
			$license_name = 'WPULB_FRESHDESK_LICENSE';
			$addon_status = 'WPULB_ADDON_STATUS_FRESHDESK';
			$license_key_option = 'WPULB_FRESHDESK_LICENSE_KEY';
			break;

		case 'vtiger-ticket-pro-plus/vtiger-ticket-pro-plus.php':
			$license_name = 'WPULB_VTIGETTICKET_LICENSE';
			$addon_status = 'WPULB_ADDON_STATUS_VTIGETTICKET';
			$license_key_option = 'WPULB_VTIGETTICKET_LICENSE_KEY';
			break;

		case 'woocommerce-sync-pro-plus/woocommerce-sync-pro-plus.php':
			$license_name = 'WPULB_WC_LICENSE';
			$addon_status = 'WPULB_ADDON_STATUS_WC';
			$license_key_option = 'WPULB_WC_LICENSE_KEY';
			break;

		case 'user-sync-pro-plus/user-sync-pro-plus.php':
			$license_name = 'WPULB_USER_LICENSE';
			$addon_status = 'WPULB_ADDON_STATUS_USER';
			$license_key_option = 'WPULB_USER_LICENSE_KEY';
			break;

		case 'wp-form-pro-plus/wp-form-pro-plus.php':
			$license_name = 'WPULB_WPFORM_LICENSE';
			$addon_status = 'WPULB_ADDON_STATUS_WPFORM';
			$license_key_option = 'WPULB_WPFORM_LICENSE_KEY';
			break;

		case 'contact-form-pro-plus/contact-form-pro-plus.php':
			$license_name = 'WPULB_CFORM_LICENSE';
			$addon_status = 'WPULB_ADDON_STATUS_CFORM';
			$license_key_option = 'WPULB_CFORM_LICENSE_KEY';
			break;

		case 'qu-form-pro-plus/qu-form-pro-plus.php':
			$license_name = 'WPULB_QUFORM_LICENSE';
			$addon_status = 'WPULB_ADDON_STATUS_QUFORM';
			$license_key_option = 'WPULB_QUFORM_LICENSE_KEY';
			break;

		case 'gravity-form-pro-plus/gravity-form-pro-plus.php':
			$license_name = 'WPULB_GRAVITYFORM_LICENSE';
			$addon_status = 'WPULB_ADDON_STATUS_GRAVITYFORM';
			$license_key_option = 'WPULB_GRAVITYFORM_LICENSE_KEY';
			break;	

		case 'ninja-form-wp-pro-plus/ninja-form-wp-pro-plus.php':
			$license_name = 'WPULB_NINJAFORM_LICENSE';
			$addon_status = 'WPULB_ADDON_STATUS_NINJAFORM';
			$license_key_option = 'WPULB_NINJAFORM_LICENSE_KEY';
			break;	

		case 'caldera-form-pro-plus/caldera-form-pro-plus.php':
			$license_name = 'WPULB_CALDERA_LICENSE';
			$addon_status = 'WPULB_ADDON_STATUS_CALDERA';
			$license_key_option = 'WPULB_CALDERA_LICENSE_KEY';
			break;	

		case 'leads-multi-forms-pro-plus/leads-multi-forms-pro-plus.php':
			$license_name = 'WPULB_MULTIFORM_LICENSE';
			$addon_status = 'WPULB_ADDON_STATUS_MULTIFORM';
			$license_key_option = 'WPULB_MULTIFORM_LICENSE_KEY';
			break;		
		}
		update_option($addon_status,'initial');
		update_option($license_name,'verified');
		update_option($license_key_option,$license_key);
		update_option('WPULB_ADDON_SLUG',$plugin_slug);
		activate_plugin( $plugin_slug );
		if(!is_plugin_active( $plugin_slug )){
			$response = ' Addon could not be activated';
		}
		else{
			$response = ' Activated successfully';
		}
		return $response;
	}

	public static function getAddons(){
		$addon_array = [];
		// $addon_array = array('Vtiger Pro Plus','Sugar Pro Plus','Suite Pro Plus','Salesforce Pro Plus','JoforceCRM Pro Plus','Freshsales Pro Plus','Zoho CRM Pro Plus','Zoho Desk Pro Plus','Freshdesk Pro Plus','Zendesk Pro Plus','Vtiger Ticket Pro Plus','Woocommerce Sync Pro Plus','User Sync Pro Plus','Gravity Form Pro Plus','Qu Form Pro Plus','Contact Form Pro Plus','Ninja Form WP Pro Plus','WP Form Pro Plus','Caldera Form Pro Plus','Leads Multi Forms Pro Plus');
		$addon_array = array('Vtiger Pro Plus','Sugar Pro Plus','Suite Pro Plus','Salesforce Pro Plus','Freshsales Pro Plus','Zoho CRM Pro Plus','Zoho Desk Pro Plus','Freshdesk Pro Plus','Zendesk Pro Plus','Vtiger Ticket Pro Plus','Woocommerce Sync Pro Plus','User Sync Pro Plus','Gravity Form Pro Plus','Qu Form Pro Plus','Contact Form Pro Plus','Ninja Form WP Pro Plus','WP Form Pro Plus','Caldera Form Pro Plus','Leads Multi Forms Pro Plus');
		return $addon_array;
	}

	public static function getAddonSlug($addon_name){
		switch($addon_name){
		case 'Vtiger Pro Plus':
			$plugin['url'] = 'vtiger-pro-plus/vtiger-pro-plus.php';
			$plugin['slug'] = 'vtiger-pro-plus';
			break;

		case 'Sugar Pro Plus':
			$plugin['url'] = 'sugar-pro-plus/sugar-pro-plus.php';
			$plugin['slug'] = 'sugar-pro-plus';
			break;

		case 'Suite Pro Plus':
			$plugin['url'] = 'suite-pro-plus/suite-pro-plus.php';
			$plugin['slug'] = 'suite-pro-plus';
			break;

		case 'Salesforce Pro Plus':
			$plugin['url'] = 'salesforce-pro-plus/salesforce-pro-plus.php';
			$plugin['slug'] = 'salesforce-pro-plus';
			break;

		// case 'JoforceCRM Pro Plus':
		// 	$plugin['url'] = 'joforcecrm-pro-plus/joforcecrm-pro-plus.php';
		// 	$plugin['slug'] = 'joforcecrm-pro-plus';
		// 	break;

		case 'Freshsales Pro Plus':
			$plugin['url'] = 'freshsales-pro-plus/freshsales-pro-plus.php';
			$plugin['slug'] = 'freshsales-pro-plus';
			break;

		case 'Zoho CRM Pro Plus':
			$plugin['url'] = 'zoho-crm-pro-plus/zoho-crm-pro-plus.php';
			$plugin['slug'] = 'zoho-crm-pro-plus';
			break;

		case 'Zoho Desk Pro Plus':
			$plugin['url'] = 'zoho-desk-pro-plus/zoho-desk-pro-plus.php';
			$plugin['slug'] = 'zoho-desk-pro-plus';
			break;

		case 'Freshdesk Pro Plus':
			$plugin['url'] = 'freshdesk-pro-plus/freshdesk-pro-plus.php';
			$plugin['slug'] = 'freshdesk-pro-plus';
			break;

		case 'Zendesk Pro Plus':
			$plugin['url'] = 'zendesk-pro-plus/zendesk-pro-plus.php';
			$plugin['slug'] = 'zendesk-pro-plus';
			break;

		case 'Vtiger Ticket Pro Plus':
			$plugin['url'] = 'vtiger-ticket-pro-plus/vtiger-ticket-pro-plus.php';
			$plugin['slug'] = 'vtiger-ticket-pro-plus';
			break;

		case 'Woocommerce Sync Pro Plus':
			$plugin['url'] = 'woocommerce-sync-pro-plus/woocommerce-sync-pro-plus.php';
			$plugin['slug'] = 'woocommerce-sync-pro-plus';
			break;

		case 'User Sync Pro Plus':
			$plugin['url'] = 'user-sync-pro-plus/user-sync-pro-plus.php';
			$plugin['slug'] = 'user-sync-pro-plus';
			break;

		case 'Gravity Form Pro Plus':
			$plugin['url'] = 'gravity-form-pro-plus/gravity-form-pro-plus.php';
			$plugin['slug'] = 'gravity-form-pro-plus';
			break;

		case 'Qu Form Pro Plus':
			$plugin['url'] = 'qu-form-pro-plus/qu-form-pro-plus.php';
			$plugin['slug'] = 'qu-form-pro-plus';
			break;

		case 'Contact Form Pro Plus':
			$plugin['url'] = 'contact-form-pro-plus/contact-form-pro-plus.php';
			$plugin['slug'] = 'contact-form-pro-plus';
			break;

		case 'Ninja Form WP Pro Plus':
			$plugin['url'] = 'ninja-form-wp-pro-plus/ninja-form-wp-pro-plus.php';
			$plugin['slug'] = 'ninja-form-wp-pro-plus';
			break;

		case 'WP Form Pro Plus':
			$plugin['url'] = 'wp-form-pro-plus/wp-form-pro-plus.php';
			$plugin['slug'] = 'wp-form-pro-plus';
			break;

		case 'Caldera Form Pro Plus':
			$plugin['url'] = 'caldera-form-pro-plus/caldera-form-pro-plus.php';
			$plugin['slug'] = 'caldera-form-pro-plus';
			break;

		case 'Leads Multi Forms Pro Plus':
			$plugin['url'] = 'leads-multi-forms-pro-plus/leads-multi-forms-pro-plus.php';
			$plugin['slug'] = 'leads-multi-forms-pro-plus';
			break;
		
		case 'All In One Crm Leads Plus':
			$plugin['slug'] = 'all-in-one-crm-leads-plus';
			break;
		}
		return $plugin;
	}

	public static function is_plugin_installed( $slug ) {
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		$all_plugins = get_plugins();

		if ( !empty( $all_plugins[$slug] ) ) {
			return true;
		} else {
			return false;
		}
	}

	public function zip_upload($path , $extract_path ){
	
		if (class_exists('ZipArchive')) {
			$zip = new \ZipArchive;
			$res = $zip->open($path);

			if ($res === TRUE) {    
				$extract = $zip->extractTo($extract_path );
				$zip->close();
				$response = 'Zip Extracted';
			} else {
				$response = 'Error Occured while extracting zip file.';
			}

		}else{
			$response = 'ZipArchive class not exists';
		}

		return $response;
	}

	public static function getLicenseDetails(){
		$addons_name = array(
							'VTIGERCRM' => 'Vtiger Pro Plus', 
							'SALESFORCECRM' => 'Salesforce Pro Plus', 
							'FRESHSALES' => 'Freshsales Pro Plus', 
							'SUITECRM' => 'Suite Pro Plus', 
							'SUGARCRM' => 'Sugar Pro Plus', 
							//'JOFORCECRM' => 'JoforceCRM Pro Plus', 
							'ZOHOCRM' => 'Zoho CRM Pro Plus', 
							'ZOHODESK' => 'Zoho Desk Pro Plus', 
							'ZENDESK' => 'Zendesk Pro Plus', 
							'FRESHDESK' => 'Freshdesk Pro Plus', 
							'VTIGETTICKET' => 'Vtiger Ticket Pro Plus',
							'WC' => 'Woocommerce Sync Pro Plus', 
							'USER' => 'User Sync Pro Plus', 
							'WPFORM' => 'WP Form Pro Plus', 
							'CFORM' => 'Contact Form Pro Plus', 
							'QUFORM' => 'Qu Form Pro Plus', 
							'GRAVITYFORM' => 'Gravity Form Pro Plus', 
							'NINJAFORM' => 'Ninja Form WP Pro Plus', 
							'CALDERA' => 'Caldera Form Pro Plus', 
							'MULTIFORM' => 'Leads Multi Forms Pro Plus'
						);
	
		$purchased_products = [];
		foreach($addons_name as $addon_key => $addon_value){
			$check_license = get_option("WPULB_{$addon_key}_LICENSE_KEY");
			if(!empty($check_license)){
				$purchased_products[$addon_value] = $check_license;
			}
		}
		echo wp_json_encode(['response' => ['products_with_license' => $purchased_products], 'message' => 'Purchased products with license key', 'status' => 200, 'success' => true]);	
		wp_die();
	}

	public static function licenseKeyTabActivation(){
		
		// $tab_activation_status = get_option('WPULB_LICENSE_TAB_ACTIVE');
		// if(!empty($tab_activation_status)){
		// 	$license_key_tab = true;
		// }

		$license_key_tab = false;
		// $all_addons = array('VTIGERCRM', 'SALESFORCECRM', 'FRESHSALES', 'SUITECRM', 'SUGARCRM', 'JOFORCECRM', 'ZOHOCRM', 'ZOHODESK', 'ZENDESK', 'FRESHDESK',
		// 						'VTIGETTICKET', 'WC', 'USER', 'WPFORM', 'CFORM', 'QUFORM', 'GRAVITYFORM', 'NINJAFORM', 'CALDERA', 'MULTIFORM'
		// 					);

		$all_addons = array('VTIGERCRM', 'SALESFORCECRM', 'FRESHSALES', 'SUITECRM', 'SUGARCRM', 'ZOHOCRM', 'ZOHODESK', 'ZENDESK', 'FRESHDESK',
								'VTIGETTICKET', 'WC', 'USER', 'WPFORM', 'CFORM', 'QUFORM', 'GRAVITYFORM', 'NINJAFORM', 'CALDERA', 'MULTIFORM'
							);
		
		foreach($all_addons as $addon_value){
			$check_license = get_option("WPULB_{$addon_value}_LICENSE_KEY");
			if(!empty($check_license)){
				$license_key_tab = true;
			}
		}
		echo wp_json_encode(['response' => ['licensekey_tab_activation' => $license_key_tab], 'message' => 'Purchased products with license key', 'status' => 200, 'success' => true]);	
		wp_die();
	}
}

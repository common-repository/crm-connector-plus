<?php
/**
* CRM Connector Plus plugin file. 
*
* Copyright (C) 2010-2020, Smackcoders Inc - info@smackcoders.com 
*/

namespace Smackcoders\WPULB;

if ( ! defined( 'ABSPATH' ) )
exit; // Exit if accessed directly

require_once(plugin_dir_path(__FILE__). 'Schedule.php');
require_once(plugin_dir_path(__FILE__). 'ManageForms.php');
require_once(plugin_dir_path(__FILE__). 'ManageFields.php');
require_once(plugin_dir_path(__FILE__). 'wp-lb-hooks.php');
require_once(plugin_dir_path(__FILE__). '../controllers/defaultformsync/FormOptions.php');
require_once(plugin_dir_path(__FILE__). 'ManageGroups.php');
require_once(plugin_dir_path(__FILE__). 'ManageFields.php');
require_once(plugin_dir_path(__FILE__). 'PluginTables.php');
require_once(plugin_dir_path(__FILE__). '../controllers/installplugins/InstallPlugins.php');
require_once(plugin_dir_path(__FILE__). 'DataBuckets.php');
require_once(plugin_dir_path(__FILE__). 'LicenseManager.php');
require_once(plugin_dir_path(__FILE__). 'DataBucketMigration.php');
require_once(plugin_dir_path(__FILE__). 'joForceSupport/JoForceCrmFunctions.php');
require_once(plugin_dir_path(__FILE__). '../controllers/ContactFormsSupport.php');
require_once(plugin_dir_path(__FILE__). '../languages/LangEN.php');
require_once(plugin_dir_path(__FILE__). '../languages/LangFR.php');
require_once(plugin_dir_path(__FILE__). '../languages/LangES.php');
require_once(plugin_dir_path(__FILE__). '../languages/LangIT.php');
require_once(plugin_dir_path(__FILE__). '../languages/LangGE.php');

class WPULBAdmin
{
	protected static $instance = null;
	protected static $addons = null;
	protected static $schedule_instance = null;
	protected static $joforcecrm_instance = null;
	public $plugin = null;

	protected static $english_lang_instance = null;
	protected static $french_lang_instance = null;
	protected static $spanish_lang_instance = null;
	protected static $italian_lang_instance = null;
	protected static $german_lang_instance = null;
	
	/**
	 * Admin constructor.
	 */
	public function __construct()
	{
		$this->plugin=WPULBPlugin::getInstance();
	}

	/**
	 * Admin Instances
	 */
	public static function getInstance() {
		
		if (self::$instance == null) {
			self::$instance=new self();

			// Load plugin functionalities when it is activated
			if(is_plugin_active( WPULBPlugin::$leads_builder_slug . '/' . WPULBPlugin::$leads_builder_slug . '.php')){

				global $wpulb_plugin_ajax_hooks;
				$plugin_pages = [ WPULBPlugin::$leads_builder_slug . '-admin-menu' ];

				if(isset($_REQUEST['page'])){
					if(in_array($_REQUEST['page'], $plugin_pages)){
						WPULBAdmin::initPluginFunctionalities();
					}
				}else if(isset($_REQUEST['action'])){
					if(in_array($_REQUEST['action'], $wpulb_plugin_ajax_hooks)){
						WPULBAdmin::initPluginFunctionalities();
					}
				}
				self::$schedule_instance = Schedule::getInstance();
				self::$joforcecrm_instance = JoForceCrm::getInstance();
				self::$english_lang_instance = LangEN::getInstance();
				self::$french_lang_instance = LangFR::getInstance();
				self::$spanish_lang_instance = LangES::getInstance();
				self::$italian_lang_instance = LangIT::getInstance();
				self::$german_lang_instance = LangGE::getInstance();
				self::$instance->doHooks();
			}
		}
		return self::$instance;
	}

	public static function initPluginFunctionalities()
	{
		PluginTables::getInstance();
		ThirdPartyForm::getInstance();
		ManageFields::getInstance();
		ManageForms::getInstance();
		MappingSection::getInstance();
		ManageGroups::getInstance();		
		FormOptions::getInstance();
		InstallPlugins::getInstance();
		LicenseManager::getInstance();
		DataBuckets::getInstance();
		DataBucketMigration::getInstance();
		ContactFormsSupport::getInstance();
	}

	/**
	 * Admin Hooks
	 */
	public function doHooks(){
		add_action('admin_menu', array($this,'addpluginAdminmenu'));
		add_action('plugins_loaded',array($this,'loadLanguages'));
		add_action('admin_enqueue_scripts',array($this,'enqueueAdminScripts'));
		
		add_action('wp_ajax_wpulb_addon_list', array($this, 'addonList'));
		
		add_action('wp_ajax_wpulb_document_ready', array($this, 'loadOnDocumentReady'));
		add_action('wp_ajax_wpulb_active_addons', array($this, 'addonList'));

		add_action('wp_ajax_wpulb_save_configuration',array($this,'saveConfiguration'));
		add_action('wp_ajax_wpulb_save_help_configuration',array($this,'saveHelpConfiguration'));

		add_action('wp_ajax_wpulb_zohoauth',array($this,'zohoAuthentication'));
		add_action('wp_ajax_wpulb_salesauth',array($this,'salesAuthentication'));
		add_action('wp_ajax_wpulb_zohosupport_auth',array($this,'zohosupportAuthentication'));
		
		add_action('wp_ajax_wpulb_get_active_addons_for_config', array($this, 'getAddonsForCRMConfigurationPicklist'));
		add_action('wp_ajax_wpulb_get_active_help_addons_for_config', array($this, 'getAddonsForHelpdeskConfigurationPicklist'));

		add_action('wp_ajax_wpulb_get_configured_crm', array($this, 'getConfiguredCrm'));
		add_action('wp_ajax_wpulb_get_configured_helpdesk', array($this, 'getConfiguredHelpDesk'));

		add_action('wp_ajax_wpulb_list_filters', array($this, 'listFilters'));
		add_action('wp_ajax_wpulb_filter_details', array($this, 'FilterDetails'));

		add_action('wp_ajax_wpulb_get_callback_url', array($this, 'callbackUrl'));
		add_action('wp_ajax_wpulb_check_woocommerce_active', array($this, 'checkWoocommerceActive'));
		
		add_action('wp_ajax_wpulb_get_user_details_checkout', array($this, 'getUserDetailsCheckout'));

		add_filter('widget_text', 'do_shortcode');
		
		if(!is_plugin_active('contact-form-pro-plus/contact-form-pro-plus.php')){
			add_action('wpcf7_before_send_mail', array($this, 'contact_form_submission_hook'));
		}
	}

	function contact_form_submission_hook($arr){
		$contact_form_instance = ContactFormsSupport::getInstance();
		$contact_form_instance->contact_form_submission($arr);
	}

	public static function callbackUrl(){
		$url = site_url()."/wp-admin/admin.php?page=wp-crm-connector-plus-admin-menu";
		echo wp_json_encode(['response' => ['callback_url' => $url], 'message' => 'CallBack Url', 'status' => 200, 'success' => true]);
		wp_die();
	}

	public static function checkWoocommerceActive(){
		
		$woocom_installed = false;
		$woocom_activated = false;
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		$all_plugins = get_plugins();

		if ( !empty( $all_plugins['woocommerce/woocommerce.php'] ) ) {
			$woocom_installed = true;
		} 
		if(is_plugin_active('woocommerce/woocommerce.php')){
			$woocom_activated = true;
		}

		if($woocom_installed && $woocom_activated){
			echo wp_json_encode(['response' => ['woocommerce_active' => true], 'message' => '', 'status' => 200, 'success' => true]);
		}
		else{
			if(!$woocom_installed){
				echo wp_json_encode(['response' => ['woocommerce_active' => false], 'message' => 'Install', 'status' => 200, 'success' => true]);
			}
			elseif($woocom_installed && !$woocom_activated){
				echo wp_json_encode(['response' => ['woocommerce_active' => false], 'message' => 'Activate', 'status' => 200, 'success' => true]);
			}
		}
		wp_die();
	} 

	public static function buyNowClick(){
		$selected_addons = str_replace("\\" , '' , sanitize_text_field($_POST['addons']));	
		$selected_addons = json_decode($selected_addons, True);	
		global $wpulb_addons_price;
		update_option("WPULB_SELECT_BUYNOW_ADDONS", $selected_addons);
		update_option("INAPP_PURCHASE",'true');
		
		$addon_array = [];
		$temp = 0;
		foreach($selected_addons as $selected_addon){
			$addon_array[$temp]['product_name'] = $selected_addon;
			$addon_array[$temp]['product_price'] = $wpulb_addons_price[$selected_addon];
			$temp++;
		}
		$urlparts = parse_url(home_url());
		$domain_name = $urlparts['host'];
		//$domain_name = $_SERVER['HTTP_HOST'];
		echo wp_json_encode(['response' => ['addon_array' => $selected_addons], 'message' => 'Selected Addons','domain_name'=>$domain_name, 'status' => 200, 'success' => true]);
		wp_die();
	}

	public static function getUserDetailsCheckout(){	
		global $wpulb_addons_price;

		$get_select_buynow_addons = get_option("WPULB_SELECT_BUYNOW_ADDONS");	
		$upload_dir = self::$instance->create_upload_leads_dir();

		$leads_addon_arr = [];
		$leads_addons = [];
		$temp = 0;
		foreach($get_select_buynow_addons as $selected_addon){
			$leads_addons[$temp]['product_name'] = $selected_addon;
			$leads_addons[$temp]['product_price'] = $wpulb_addons_price[$selected_addon];
			$temp++;
		}

		$leads_user_details = [];
		$leads_user_details['username'] = sanitize_text_field($_POST['username']);
		$leads_user_details['email'] = sanitize_email($_POST['email']);
		$leads_user_details['domain'] = sanitize_text_field($_POST['domain']);
		$leads_user_details['street_address'] = sanitize_textarea_field($_POST['street_address']);
		$leads_user_details['town_city'] = sanitize_text_field($_POST['town_city']);
		$leads_user_details['country'] = sanitize_text_field($_POST['country']);
		$leads_user_details['postal_code'] = intval($_POST['postal_code']);
		$leads_user_details['phone'] = sanitize_text_field($_POST['phone']);

		$leads_addon_arr['data'] = $leads_addons;
		$leads_addon_arr['user_details'] = $leads_user_details;

		$leads_addons_path = $upload_dir.'leads_addons.json';
		chmod($leads_addons_path , 0777);

		$json = json_encode($leads_addon_arr);
		//write json to file
		file_put_contents($leads_addons_path, $json);

		echo wp_json_encode(['response' => '', 'message' => 'Json file saved', 'status' => 200, 'success' => true]);
		wp_die();
	}

	public function create_upload_leads_dir(){
		$upload = wp_upload_dir();
		$upload_dir = $upload['basedir'];
		if(!is_dir($upload_dir)){
			return false;
		}else{
			$upload_dir = $upload_dir . '/smack_leads_addons/';	
			if (!is_dir($upload_dir)) {
				wp_mkdir_p( $upload_dir);
			}
			chmod($upload_dir, 0777);		
			return $upload_dir;
		}
		chmod($upload_dir, 0777);		
		return $upload_dir;
	}

	/**
	 * getAddonsForCRMConfigurationPicklist
	 *
	 * @return void
	 */
	public static function getAddonsForCRMConfigurationPicklist(){	
		global $supported_addons;
		
		$addon_list = [];
		$crm_addon_list[0] = ['label' => 'JoforceCRM Pro Plus', 'value' => 'joforcecrm'];
		$temp = 1;
		foreach($supported_addons['crm_addons'] as $crm_addon){
			if(is_plugin_active( $crm_addon['plugin_slug'] . '/' . $crm_addon['plugin_filename'])){
				$crm_addon_list[$temp] = ['label' => $crm_addon['plugin_label'], 'value' => $crm_addon['plugin_wpulb_shortname']];
				$temp++;
			}
		}
	
		$addon_list[0]['options'] = $crm_addon_list;
		$addon_list[0]['label'] = 'CRM Addons';
		$addon_list[0]['value'] = 'crm_addons';
	
		echo wp_json_encode(['response' => ['addon_list' => $addon_list], 'message' => 'Addons for Configuration', 'status' => 200, 'success' => true]);
		wp_die();
	}

	/**
	 * getAddonsForHelpdeskConfigurationPicklist
	 *
	 * @return void
	 */
	public static function getAddonsForHelpdeskConfigurationPicklist(){	
		global $supported_addons;
		
		$addon_list = [];
		$helpdesk_addon_list = [];
		$temp = 0;
		foreach($supported_addons['helpdesk_addons'] as $helpdesk_addon){
			if(is_plugin_active( $helpdesk_addon['plugin_slug'] . '/' . $helpdesk_addon['plugin_filename'])){
				// $helpdesk_addon_list[$temp] = ['addon_label' => 'Helpdesk Addons', 'addon_shortname' => $helpdesk_addon['plugin_wpulb_shortname'], 'options' => ['label' => $helpdesk_addon['plugin_label'], 'value' => $helpdesk_addon['plugin_wpulb_shortname']]];
				$helpdesk_addon_list[$temp] = ['label' => $helpdesk_addon['plugin_label'], 'value' => $helpdesk_addon['plugin_wpulb_shortname']];
				$temp++;
			}
		}

		$addon_list[0]['options'] = $helpdesk_addon_list;
		$addon_list[0]['label'] = 'Helpdesk Addons';
		$addon_list[0]['value'] = 'helpdesk_addons';

		echo wp_json_encode(['response' => ['addon_list' => $addon_list], 'message' => 'Helpdesk Addons for Configuration', 'status' => 200, 'success' => true]);
		wp_die();
	}

	/**
	 * getConfiguredCrm
	 *
	 * @return void
	 */
	public static function getConfiguredCrm(){
		$configured_crm = get_option('WPULB_ACTIVE_CRM_ADDON');
		if(isset($configured_crm) && $configured_crm != 'wpulb_crm' && $configured_crm != ''){
			$info = [];
			if($configured_crm == 'zohocrm'){
				$get_credentials = get_option('WPULB_CONNECTED_ZOHOCRM_CREDENTIALS');
				if(isset($get_credentials['access_token'])){
					$info['username'] = $get_credentials['key'];
					$info['password'] = $get_credentials['secret'];
				}
			}
			elseif($configured_crm == 'joforcecrm'){
				$get_credentials = get_option('WPULB_CONNECTED_JOFORCECRM_CREDENTIALS');
				$info['username'] = $get_credentials['username'];
				$info['password'] = $get_credentials['password'];
				$info['api_endpoint_url'] = $get_credentials['app_url'];
			}
			elseif($configured_crm == 'vtigercrm'){
				$get_credentials = get_option('WPULB_CONNECTED_VTIGERCRM_CREDENTIALS');
				$info['username'] = $get_credentials['username'];
				$info['password'] = $get_credentials['accesskey'];
				$info['api_endpoint_url'] = $get_credentials['url'];
			}
			elseif($configured_crm == 'sugarcrm'){
				$get_credentials = get_option('WPULB_CONNECTED_SUGARCRM_CREDENTIALS');
				$info['username'] = $get_credentials['username'];
				$info['password'] = $get_credentials['password'];
				$info['api_endpoint_url'] = $get_credentials['url'];
			}
			elseif($configured_crm == 'freshsalescrm'){
				$get_credentials = get_option('WPULB_CONNECTED_FRESHSALESCRM_CREDENTIALS');
				$info['username'] = $get_credentials['username'];
				$info['password'] = $get_credentials['password'];
				$info['api_endpoint_url'] = $get_credentials['domain_url'];
			}
			elseif($configured_crm == 'salesforcecrm'){
				$get_credentials = get_option('WPULB_CONNECTED_SALESFORCECRM_CREDENTIALS');
				if(isset($get_credentials['access_token'])){
					$info['username'] = $get_credentials['username'];
					$info['password'] = $get_credentials['password'];
				}
			}
			elseif($configured_crm == 'suitecrm'){
				$get_credentials = get_option('WPULB_CONNECTED_SUITECRM_CREDENTIALS');
				$info['username'] = $get_credentials['username'];
				$info['password'] = $get_credentials['password'];
				$info['api_endpoint_url'] = $get_credentials['api_endpoint_url'];
			}

			global $supported_addons;
			$crm_addons = [];
			foreach($supported_addons['crm_addons'] as $addons){
				if($addons['plugin_wpulb_shortname'] == $configured_crm){
					$crm_addons['label'] = $addons['plugin_label'];
					$crm_addons['value'] = $addons['plugin_wpulb_shortname'];
				}
			}

			echo wp_json_encode(['response' => $info, 'is_configured' => true, 'crm' => $crm_addons ,'message' => 'Crm configured', 'status' => 200, 'success' => true]);
		}elseif($configured_crm == 'wpulb_crm' || $configured_crm == ''){
			echo wp_json_encode(['response' => '', 'is_configured' => false, 'message' => 'No crm configured', 'status' => 200, 'success' => false]);
		}
		wp_die();
	}

	/**
	 * getConfiguredHelpDesk
	 *
	 * @return void
	 */
	public static function getConfiguredHelpDesk(){
			
		$configured_crm = get_option('WPULB_ACTIVE_HELPDESK_ADDON');
		
		if(isset($configured_crm) && $configured_crm != 'wpulb_crm' && $configured_crm != ''){
			$info = [];
			if($configured_crm == 'freshdesk' || $configured_crm == 'vtigersupport' || $configured_crm == 'zendesk'){
				
				if($configured_crm == 'freshdesk'){
					$get_credentials = get_option('WPULB_CONNECTED_FRESHDESK_CREDENTIALS');
				}elseif($configured_crm == 'vtigersupport'){
					$get_credentials = get_option('WPULB_CONNECTED_VTIGERSUPPORT_CREDENTIALS');
				}elseif($configured_crm == 'zendesk'){
					$get_credentials = get_option('WPULB_CONNECTED_ZENDESK_CREDENTIALS');
				}

				$info['username'] = $get_credentials['username'];
				$info['password'] = $get_credentials['password'];
				$info['domain_url'] = $get_credentials['domain_url'];
			}

			elseif($configured_crm == 'zohosupport'){
				$get_credentials = get_option('WPULB_CONNECTED_ZOHOSUPPORT_CREDENTIALS');
				if(isset($get_credentials['access_token'])){
					$info['username'] = $get_credentials['key'];
					$info['password'] = $get_credentials['secret'];
					$info['domain_url'] = $get_credentials['org_id'];
				}
			}

			global $supported_addons;
			$crm_addons = [];
			foreach($supported_addons['helpdesk_addons'] as $addons){
				if($addons['plugin_wpulb_shortname'] == $configured_crm){
					$crm_addons['label'] = $addons['plugin_label'];
					$crm_addons['value'] = $addons['plugin_wpulb_shortname'];
				}
			}

			echo wp_json_encode(['response' => $info, 'is_configured' => true, 'helpdesk' => $crm_addons ,'message' => 'Helpdesk configured', 'status' => 200, 'success' => true]);
		}elseif($configured_crm == 'wpulb_crm' || $configured_crm == ''){
			echo wp_json_encode(['response' => '', 'is_configured' => false, 'message' => 'No helpdesk configured', 'status' => 200, 'success' => false]);
		}
		wp_die();
	}

	/**
	 * loadOnDocumentReady
	 *
	 * @return void
	 */
	public static function loadOnDocumentReady(){

		$has_crm_addon_active = get_option('WPULB_ACTIVE_CRM_ADDON');
		$has_helpdesk_addon_active = get_option('WPULB_ACTIVE_HELPDESK_ADDON');

		if($has_crm_addon_active && $has_crm_addon_active != 'wpulb_crm'){
			$has_crm_addon = true;
		}else{
			$has_crm_addon = false;
		}

		if($has_helpdesk_addon_active && $has_helpdesk_addon_active != 'wpulb_crm'){
			$has_helpdesk_addon = true;
		}else{
			$has_helpdesk_addon = false;
		}

		$recently_activated_plugin = null;
		$is_addon_currently_configured = false;
		if(isset($_GET['code'])){
			$is_addon_currently_configured = true;
			$recently_activated_plugin = get_option('WPULB_RECENTLY_ACTIVATED'); // Plugin label will come here
			//delete_option('WPULB_RECENTLY_ACTIVATED');
		}

		if(is_plugin_active("user-sync-pro-plus/user-sync-pro-plus.php")){
			if($has_crm_addon_active == 'wpulb_crm' && $has_helpdesk_addon_active == 'wpulb_crm'){
				$is_user_activated = false;
			}
			else{
				$is_user_activated = true;
			}
		}else{
			$is_user_activated = false;
		}
		
		if(is_plugin_active("woocommerce-sync-pro-plus/woocommerce-sync-pro-plus.php")){
			$woocom_restrict = array('wpulb_crm', 'freshsalescrm');
			
			if(!in_array($has_crm_addon_active, $woocom_restrict)){
				$is_woocom_activated = true;
			}
			else{
				$is_woocom_activated = false;
			}
		}else{
			$is_woocom_activated = false;
		}
		
		echo wp_json_encode(['response' => [
			'has_crm_addon' => $has_crm_addon,
			'has_helpdesk_addon' => $has_helpdesk_addon,
			'user_configuration' => $is_user_activated,
			'woocommerce_configuration' => $is_woocom_activated,
			'is_addon_currently_configured' => $is_addon_currently_configured,
			'recently_activated_plugin' => $recently_activated_plugin,
		], 'message' => 'Init call response', 'status' => 200, 'success' => true]);
		wp_die();
	}

	/**
	 * addonList
	 *
	 * @return void
	 */
	public function addonList(){
	
		global $supported_addons;
		
		$addon_list = [];
		$crm_addon_list = [];
		$helpdesk_addon_list = [];
		$temp = 0;

		$configured_crm_addon = get_option('WPULB_ACTIVE_CRM_ADDON');
		$configured_help_addon = get_option('WPULB_ACTIVE_HELPDESK_ADDON');

		foreach($supported_addons['crm_addons'] as $crm_addon){
			$crm_addon_list[$temp]['plugin_url'] = $crm_addon['plugin_url'];
			$crm_addon_list[$temp]['plugin_label'] = $crm_addon['plugin_label'];
			$crm_addon_list[$temp]['plugin_slug'] = $crm_addon['plugin_slug'];
			if(is_plugin_active( $crm_addon['plugin_slug'] . '/' . $crm_addon['plugin_slug'] . '.php')){
				$crm_addon_list[$temp]['active'] = true;
			}else{
				$crm_addon_list[$temp]['active'] = false;
			}

			if($crm_addon['plugin_wpulb_shortname'] == $configured_crm_addon){
				$crm_addon_list[$temp]['configured'] = true;
			}else{
				$crm_addon_list[$temp]['configured'] = false;
			}
			$temp++;
		}

		$addon_list[0]['options'] = $crm_addon_list;
		$addon_list[0]['label'] = 'CRM Addons';
		$addon_list[0]['value'] = 'crm_addons';

		$temp = 0;
		foreach($supported_addons['helpdesk_addons'] as $helpdesk_addon){
			$helpdesk_addon_list[$temp]['plugin_url'] = $helpdesk_addon['plugin_url'];
			$helpdesk_addon_list[$temp]['plugin_label'] = $helpdesk_addon['plugin_label'];
			$helpdesk_addon_list[$temp]['plugin_slug'] = $helpdesk_addon['plugin_slug'];
			if(is_plugin_active( $helpdesk_addon['plugin_slug'] . '/' . $helpdesk_addon['plugin_slug'] . '.php')){
				$helpdesk_addon_list[$temp]['active'] = true;
			}else{
				$helpdesk_addon_list[$temp]['active'] = false;
			}

			if($helpdesk_addon['plugin_wpulb_shortname'] == $configured_help_addon){
				$helpdesk_addon_list[$temp]['configured'] = true;
			}else{
				$helpdesk_addon_list[$temp]['configured'] = false;
			}
			$temp++;
		}

		$addon_list[1]['options'] = $helpdesk_addon_list;
		$addon_list[1]['label'] = 'Helpdesk Addons';
		$addon_list[1]['value'] = 'helpdesk_addons';

		echo wp_json_encode(['response' => ['addon_list' => $addon_list],
		 'message' => 'Supported Plugin List', 'status' => 200, 'success' => true]);

		wp_die();
	}

	/**
	 * loadLanguages
	 *
	 * @return void
	 */
	public function loadLanguages(){
		$lang_dir = dirname( plugin_basename( __FILE__ ) ) . '/languages/';
		load_plugin_textdomain( WPULBPlugin::$leads_builder_slug , false, $lang_dir );
	}

	public static function loadPluginDependencies(){
		$includesFile = glob( '/*.php');
		foreach ($includesFile as $includesFileValue) {
			require_once($includesFileValue);
		}

		$includesFile = glob( plugin_dir_path(__FILE__). '*.php' );

		foreach ($includesFile as $includesFileValue) {
			require_once($includesFileValue);
		}

		require_once(plugin_dir_path(__FILE__). '../controllers/defaultformsync/FormOptions.php');
		require_once(plugin_dir_path(__FILE__). '../controllers/installplugins/InstallPlugins.php');
		require_once(plugin_dir_path(__FILE__). '../controllers/ThirdPartyForm.php');
		require_once(plugin_dir_path(__FILE__). '../controllers/ContactFormsSupport.php');
	}

	public function addpluginAdminmenu(){
		$this->pluginScreenHookSuffix=add_menu_page(
			'CRM Connector Plus', // Title of the page
			'CRM Connector Plus', // Text to show on the menu link
				'manage_options', // Capability requirement to see the link
				'wp-crm-connector-plus-admin-menu',
				array($this,'display_plugin_admin_page'),// The 'slug' - file to display when clicking the link
				plugins_url("../assets/images/wp-crm-connector-plus.png",__FILE__)	
			);

	}

	public function display_plugin_admin_page(){
		echo "<div id='wp-crm-connector-plus'></div>";
	}

	public function display_plugin_settings_page(){
		echo "<div id='wp-crm-connector-plus-settings'></div>";	
	}

	/**
	 * Admin Scripts
	 */
	public function enqueueAdminScripts(){
		if(!isset($this->pluginScreenHookSuffix)){
			return;
		}
		$screen=get_current_screen();
		if($this->pluginScreenHookSuffix == $screen->id){
			// wp_enqueue_script($this->plugin->getPluginSlug().'bootstrap-select',plugins_url( 'assets/js/deps/bootstrap-select.min.js', dirname(__FILE__)), array( 'jquery' ),'',true );
			
			// wp_enqueue_style($this->plugin->getPluginSlug().'bootstrap-select-css', plugins_url( 'assets/css/deps/bootstrap-select.min.css', dirname(__FILE__)));
			wp_enqueue_style($this->plugin->getPluginSlug().'fontawesome', plugins_url( 'assets/css/font-awesome.min.css', dirname(__FILE__)));
			wp_enqueue_style($this->plugin->getPluginSlug().'boostrap-css', plugins_url( 'assets/css/bootstrap.min.css', dirname(__FILE__)));
			wp_enqueue_style($this->plugin->getPluginSlug().'icon-style',plugins_url( 'assets/css/icons.css', dirname(__FILE__)));
			wp_enqueue_style($this->plugin->getPluginSlug().'react-toasty-css',plugins_url( 'assets/css/ReactToastify.min.css', dirname(__FILE__)));
			wp_enqueue_style($this->plugin->getPluginSlug().'react-datapicker-css',plugins_url( 'assets/css/react-datepicker.css', dirname(__FILE__)));
			wp_enqueue_style($this->plugin->getPluginSlug().'react-Confirm-css',plugins_url( 'assets/css/react-confirm-alert.css', dirname(__FILE__)));
			wp_enqueue_style($this->plugin->getPluginSlug().'main-style',plugins_url( 'assets/css/leads-builder.css', dirname(__FILE__)));
			wp_enqueue_script($this->plugin->getPluginSlug().'popper',plugins_url( 'assets/js/deps/popper.js', dirname(__FILE__)), array( 'jquery' ),'',true );
			wp_enqueue_script($this->plugin->getPluginSlug().'bootstrap',plugins_url( 'assets/js/deps/bootstrap.min.js', dirname(__FILE__)), array( 'jquery' ),'',true );
			wp_enqueue_script($this->plugin->getPluginSlug().'admin-vendor-script',plugins_url( 'assets/vendors/admin.js', dirname(__FILE__)));
			wp_enqueue_script($this->plugin->getPluginSlug().'admin-script',plugins_url( 'assets/js/admin.js', dirname(__FILE__)));
			// wp_localize_script($this->plugin->getPluginSlug().'admin-script', 'wpulb_wpr_object', array(
			// 			'imagePath' => plugin_dir_url( dirname(__FILE__)) . 'assets/images/'
			// 			));

			$language = get_locale();
		
			if ($language == 'it_IT') {
				$contents = self::$italian_lang_instance->contents();
				$response = wp_json_encode($contents);
				wp_localize_script($this->plugin->getPluginSlug() . 'admin-script', 'wpulb_wpr_object', array('file' => $response, __FILE__, 'imagePath' => plugin_dir_url( dirname(__FILE__)) . 'assets/images/'));
			} elseif ($language == 'fr_FR') {
				$contents = self::$french_lang_instance->contents();
				$response = wp_json_encode($contents);
				wp_localize_script($this->plugin->getPluginSlug() . 'admin-script', 'wpulb_wpr_object', array('file' => $response, __FILE__, 'imagePath' => plugin_dir_url( dirname(__FILE__)) . 'assets/images/'));
			} elseif ($language == 'de_DE') {
				$contents = self::$german_lang_instance->contents();
				$response = wp_json_encode($contents);
				wp_localize_script($this->plugin->getPluginSlug() . 'admin-script', 'wpulb_wpr_object', array('file' => $response, __FILE__, 'imagePath' => plugin_dir_url( dirname(__FILE__)) . 'assets/images/'));
			} elseif ($language == 'es_ES') {
				$contents = self::$spanish_lang_instance->contents();
				$response = wp_json_encode($contents);
				wp_localize_script($this->plugin->getPluginSlug() . 'admin-script', 'wpulb_wpr_object', array('file' => $response, __FILE__, 'imagePath' => plugin_dir_url( dirname(__FILE__)) . 'assets/images/'));
			}
			else {
				$contents = self::$english_lang_instance->contents();
				$response = wp_json_encode($contents);
				wp_localize_script($this->plugin->getPluginSlug() . 'admin-script', 'wpulb_wpr_object', array('file' => $response, __FILE__, 'imagePath' => plugin_dir_url( dirname(__FILE__)) . 'assets/images/'));
			}
		}
	}

	/**
     * saveConfiguration
     *
     * @return void
     */

	public static function saveConfiguration(){

        try{
		
			$configuration['username'] = sanitize_text_field($_POST['username']);
			$configuration['password'] = sanitize_text_field($_POST['password']);
			$configuration['api_endpoint_url'] = sanitize_text_field($_POST['api_endpoint_url']);
			
			$configuration['addon_slug'] = $addon_slug = $_POST['addon_slug'];
        
			if(sanitize_text_field($_POST['addon_type']) == 'crm_addon'){
				if($addon_slug == 'salesforcecrm' || $addon_slug == 'zohocrm'){
					$redirect_url = '';

					update_option('WPULB_RECENTLY_ACTIVATED', $addon_slug);
					if($addon_slug == 'zohocrm'){
						$domain = (isset($_POST['domain'])) ? $_POST['domain'] : '.com';
						$redirect_url = "https://accounts.zoho".$domain."/oauth/v2/auth?scope=ZohoCRM.users.ALL,ZohoCRM.modules.ALL,ZohoCRM.settings.ALL,ZohoCRM.org.ALL&client_id=" . $configuration['username'] . "&response_type=code&access_type=offline&redirect_uri=" . site_url()."/wp-admin/admin.php?page=wp-crm-connector-plus-admin-menu"; 
					}elseif($addon_slug == 'salesforcecrm'){
						// $redirect_url = "https://login.salesforce.com/services/oauth2/authorize?response_type=token&client_id=" . $configuration['username'] . "&redirect_uri=" .site_url()."/wp-admin/admin.php?page=wp-crm-connector-plus-admin-menu";
						$redirect_url = "https://login.salesforce.com/services/oauth2/authorize?response_type=code&client_id=" . $configuration['username'] . "&redirect_uri=" .site_url()."/wp-admin/admin.php?page=wp-crm-connector-plus-admin-menu";
					
						update_option('WPULB_CONNECTED_SALESFORCECRM_CREDENTIALS' , $configuration);
					}
					echo wp_json_encode(['response' => ['redirect_url' => $redirect_url], 'message' => 'Configuration saved successfully', 'status' => 200, 'success' => true]);
				}
				elseif($addon_slug == 'joforcecrm'){
					//global $joforce_addon_instance;
					self::$joforcecrm_instance->wpulb_verify_joforce_credentials($configuration);
				}	
				elseif($addon_slug == 'vtigercrm'){
					global $vtiger_addon_instance;
					$vtiger_addon_instance->wpulb_verify_vtiger_credentials($configuration);
				}
				elseif($addon_slug == 'sugarcrm'){
					global $sugar_addon_instance;
					$sugar_addon_instance->wpulb_verify_sugar_credentials($configuration);
				}
				elseif($addon_slug == 'freshsalescrm'){
					global $freshsales_addon_instance;
					$freshsales_addon_instance->wpulb_verify_freshsales_credentials($configuration);
				}
				elseif($addon_slug == 'suitecrm'){
					global $suite_addon_instance;
					$suite_addon_instance->wpulb_verify_suite_credentials($configuration);
				}
			}	   
		    wp_die();
        }catch(\Exception $exception){
            echo wp_json_encode(['response' => $exception, 'message' => $exception->getMessage(), 'status' => 200, 'success' => false]);
		    wp_die();
        }
	}

	public static function saveHelpConfiguration(){
		$addon_slug = sanitize_text_field($_POST['addon_slug']);
		$configuration['username'] = sanitize_text_field($_POST['username']);
		$configuration['password'] = sanitize_text_field($_POST['password']);

		if(sanitize_text_field($_POST['addon_type']) == 'helpdesk_addon'){
			if($addon_slug == 'zohosupport'){
				update_option('WPULB_RECENTLY_ACTIVATED', $addon_slug);
				
				$zoho_configuration['key'] = $configuration['username'];
				$zoho_configuration['secret'] = $configuration['password'];
				$zoho_configuration['org_id'] = sanitize_text_field($_POST['org_id']);
				$zoho_configuration['domain'] = sanitize_text_field($_POST['domain']);
				$zoho_configuration['callback'] = site_url()."/wp-admin/admin.php?page=wp-crm-connector-plus-admin-menu";

				$redirect_url = "https://accounts.zoho".$zoho_configuration['domain']."/oauth/v2/auth?response_type=code&access_type=offline&scope=Desk.settings.READ,Desk.tickets.READ,Desk.basic.READ,Desk.tickets.CREATE,Desk.tickets.UPDATE,Desk.contacts.CREATE,Desk.contacts.UPDATE,Desk.contacts.READ&client_id=" . $zoho_configuration['key'] . "&redirect_uri=" . site_url()."/wp-admin/admin.php?page=wp-crm-connector-plus-admin-menu"; 
			
				update_option('WPULB_CONNECTED_ZOHOSUPPORT_CREDENTIALS' , $zoho_configuration);
				echo wp_json_encode(['response' => ['redirect_url' => $redirect_url], 'message' => 'Configuration saved successfully', 'status' => 200, 'success' => true]);
			}
			elseif($addon_slug == 'freshdesk'){
				$configuration['domain_url'] = esc_url_raw($_POST['domain_url']);
				global $freshdesk_addon_instance;
				$freshdesk_addon_instance->wpulb_connect_freshdesk_addon($configuration);
			}
			elseif($addon_slug == 'zendesk'){
				$configuration['domain_url'] = esc_url_raw($_POST['domain_url']);
				global $zendesk_addon_instance;
				$zendesk_addon_instance->wpulb_connect_zendesk_addon($configuration);
			}
			elseif($addon_slug == 'vtigersupport'){
				$configuration['domain_url'] = esc_url_raw($_POST['domain_url']);
				global $vtigersupport_addon_instance;
				$vtigersupport_addon_instance->wpulb_connect_vtigersupport_addon($configuration);
			}	
		}
		wp_die();
	}
	
	public static function zohoAuthentication(){   
		global $zoho_addon_instance;
		// $_POST  = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);
		// $configuration = $_POST;
		$configuration  = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);
		$zoho_addon_instance->wpulb_connect_zohocrm_addon($configuration);       
	}

	public static function salesAuthentication(){
		global $salesforce_addon_instance;
		// $_POST  = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);
		// $configuration = $_POST;
		$configuration  = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);
		$salesforce_addon_instance->wpulb_connect_salesforcecrm_addon($configuration);
	}

	public static function zohosupportAuthentication(){
		global $zohosupport_addon_instance;
		// $_POST  = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);
		// $configuration = $_POST;
		$configuration  = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);
		$zohosupport_addon_instance->wpulb_connect_zohosupport_addon($configuration);
	}

	public static function listFilters(){
		
		global $wpdb;
		$fields_list = [];
		$local_crm_fields = [];
		$fields = $wpdb->get_results( "SELECT field_name, field_label, field_type FROM {$wpdb->prefix}smack_ulb_manage_fields" );
		foreach($fields as $field){
			$local_crm_fields['label'] = $field->field_label;
			$local_crm_fields['value'] = $field->field_name;
			$local_crm_fields['type'] = $field->field_type;
			array_push($fields_list , $local_crm_fields);
		}
		
		$conditions_list = [];
		$conditions = [];
		$conditions_array = array('is', 'is not', 'is one of','is not one of','Equal to','Not equal to','Greater than','Less than','Greater than or equal to','Less than or equal to','Contain string',
									'Not contain string','Start with string','Not start with string','End with string','Not end with string','Between','Not between');
		foreach($conditions_array as $value){
			$conditions['label'] = $value;
			$conditions['value'] = $value;
			array_push($conditions_list , $conditions);
		}
		echo wp_json_encode(['response' => ['fields' => $fields_list, 'conditions' => $conditions_list], 'message' => 'Filters list', 'status' => 200, 'success' => true]);
		wp_die();
	}

	public static function FilterDetails(){
		global $wpdb;
		$info = [];
		$form_details = [];
		$mapping_data =  str_replace("\\" , '' ,sanitize_text_field($_POST['data']) );	
		$mapping_data = json_decode($mapping_data, True);	

		$mapping_condition = str_replace("\\" , '' , sanitize_text_field($_POST['key']));
		$mapping_condition = json_decode($mapping_condition, True);
		
		$select_query = "SELECT id, source_name, source_from, form_name, created_at, sync_status FROM {$wpdb->prefix}smack_ulb_databucket_meta WHERE ";
        for($i = 0 ; $i < count($mapping_data) ; $i++){
			
			$field_name = $mapping_data[$i]['field'];
			$field_value = $mapping_data[$i]['value'];
			$condition = $mapping_data[$i]['condition'];

			if(isset($mapping_condition[$i])){
				$key = $mapping_condition[$i];
			}else{
				$key = '';
			}
			
			if($condition == 'is'){
				$select_query = $select_query.$field_name." LIKE '".$field_value."' " . $key . " ";
			}
			elseif($condition == 'is not'){
				$select_query = $select_query.$field_name." NOT LIKE '".$field_value."' " . $key . " ";
			}
			elseif($condition == 'is one of'){
				$multi_values = implode(',',$field_value);
				$select_query = $select_query.$field_name." IN ( ".$multi_values." ) ". $key . " ";
			}
			elseif($condition == 'is not one of'){
				$multi_values = implode(',',$field_value);
				$select_query = $select_query.$field_name." NOT IN ( ".$multi_values." ) ". $key . " ";
			}
			elseif($condition == 'Equal to'){
				$select_query = $select_query.$field_name." = ".$field_value." ". $key . " ";
			}
			elseif($condition == 'Not equal to'){
				$select_query = $select_query.$field_name." != ".$field_value." ". $key . " ";
			}
			elseif($condition == 'Greater than'){
				$select_query = $select_query.$field_name." > ".$field_value." ". $key . " ";
			}
			elseif($condition == 'Less than'){
				$select_query = $select_query.$field_name." < ".$field_value." ". $key . " ";
			}
			elseif($condition == 'Greater than or equal to'){
				$select_query = $select_query.$field_name." >= ".$field_value." ". $key . " ";
			}
			elseif($condition == 'Less than or equal to'){
				$select_query = $select_query.$field_name." <= ".$field_value." ". $key . " ";
			}
			elseif($condition == 'Contain string'){
				$select_query = $select_query.$field_name." LIKE '%".$field_value."%' ". $key . " ";
			}
			elseif($condition == 'Not contain string'){
				$select_query = $select_query.$field_name." NOT LIKE '%".$field_value."%' ". $key . " ";
			}
			elseif($condition == 'Start with string'){
				$select_query = $select_query.$field_name." LIKE '".$field_value."%' ". $key . " ";
			}
			elseif($condition == 'Not start with string'){
				$select_query = $select_query.$field_name." NOT LIKE '".$field_value."%' ". $key . " ";
			}
			elseif($condition == 'End with string'){
				$select_query = $select_query.$field_name." LIKE '%".$field_value."' ". $key . " ";
			}
			elseif($condition == 'Not end with string'){
				$select_query = $select_query.$field_name." NOT LIKE '%".$field_value."' ". $key . " ";
			}
			elseif($condition == 'Between'){
				$select_query = $select_query.$field_name." BETWEEN '".$field_value['min']."' AND '".$field_value['max']."' ". $key . " ";
			}
			elseif($condition == 'Not between'){
				$select_query = $select_query.$field_name." NOT BETWEEN '".$field_value['min']."' AND '".$field_value['max']."' ". $key . " ";
			}
		}
		
		$total_query     = "SELECT COUNT(1) FROM (${select_query}) AS combined_table";
        $total             = $wpdb->get_var( $total_query );
        // Records per Page
        $items_per_page = get_option('posts_per_page');
        $page  = isset( $_REQUEST['cpage'] ) ? abs( (int) $_REQUEST['cpage'] ) : 1;
        
        $offset = ( $page * $items_per_page ) - $items_per_page;
        $result  = $wpdb->get_results( $select_query . " ORDER BY id DESC LIMIT ${offset}, ${items_per_page}" );
        $totalPage = ceil($total / $items_per_page);
	
		if(!empty($result)){
			foreach($result as $submitted_details){
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
		}else{
			echo wp_json_encode(['response' => ['forms' => $info , 'total_page' => $totalPage], 'message' => 'No Forms Found', 'status' => 200, 'success' => false]); 
		}
        
		wp_die();
	}
	
	public static function getCrmUsersList(){
		$activate_crm = get_option('WPULB_ACTIVE_CRM_ADDON');
		switch($activate_crm){
			case 'zohocrm':
				global $zoho_addon_instance;
				$zoho_addon_instance->wpulb_fetch_zohocrm_users();
				break;

			case 'joforcecrm':
				//global $joforce_addon_instance;
				self::$joforcecrm_instance->wpulb_fetch_joforcecrm_users();
				break;

			case 'vtigercrm':
				global $vtiger_addon_instance;
				$vtiger_addon_instance->wpulb_fetch_vtigercrm_users();
				break;

			case 'sugarcrm':
				global $sugar_addon_instance;
				$sugar_addon_instance->wpulb_fetch_sugarcrm_users();
				break;

			case 'freshsalescrm':
				global $freshsales_addon_instance;
				$freshsales_addon_instance->wpulb_fetch_freshsalescrm_users();
				break;

			case 'salesforcecrm':
				global $salesforce_addon_instance;
				$salesforce_addon_instance->wpulb_fetch_salesforcecrm_users();
				break;

			case 'suitecrm':
				global $suite_addon_instance;
				$suite_addon_instance->wpulb_fetch_suitecrm_users();
				break;
		}
	}

	public static function getHelpUsersList(){
		$activate_help = get_option('WPULB_ACTIVE_HELPDESK_ADDON');
		switch($activate_help){
			case 'zohosupport':
				global $zohosupport_addon_instance;
				$zohosupport_addon_instance->wpulb_fetch_zohohelp_users();
				break;
			
			case 'freshdesk':
				global $freshdesk_addon_instance;
				$freshdesk_addon_instance->wpulb_fetch_freshdesk_users();
				break;

			case 'zendesk':
				global $zendesk_addon_instance;
				$zendesk_addon_instance->wpulb_fetch_zendesk_users();
				break;

			case 'vtigersupport':
				global $vtigersupport_addon_instance;
				$vtigersupport_addon_instance->wpulb_fetch_vtigersupport_users();
				break;
		}
	} 
}

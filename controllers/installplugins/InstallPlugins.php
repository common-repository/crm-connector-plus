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
 * Install Plugin Class
 **/
class InstallPlugins{
	protected static $instance = null;

	public function __construct()
	{
		//$this->plugin = WPULBPlugin::getInstance();
	}

	
	/**
	 * getInstance
	 * Installplugins - Instances
	 *
	 * @return void
	 */
	public static function getInstance()
	{
		if (null == self::$instance) {
			self::$instance = new self;
			self::$instance->doHooks();
		}
		return self::$instance;
	}

	public function doHooks()
	{
		add_action('wp_ajax_wpulb_install_plugins',array($this,'install'));
		add_action('wp_ajax_wpulb_activate_addon',array($this,'activateAddon'));
	}

	public function install(){
		$crmtype =sanitize_text_field($_POST['addon']) ;
		self::plugin_install($crmtype);
	}

	/**
	  Code for download and install plugin from org
	 **/

	public function plugin_install($crmtype){
		switch($crmtype){
			case 'wp-tiger':
				$plugin_slug = 'wp-tiger/wp-tiger.php';
				$plugin_zip = 'https://wordpress.org/plugin/wp-tiger.zip';
				$old_plugin_slug = 'wp-tiger/index.php';
				break;

			case 'wp-sugar-free':
				$plugin_slug = 'wp-sugar-free/wp-sugar-free.php';
				$plugin_zip = 'https://wordpress.org/plugin/wp-sugar-free.zip';
				$old_plugin_slug = 'wp-sugar-free/index.php';
				break;

			case 'wp-salesforce':
				$plugin_slug = 'wp-salesforce/wp-salesforce.php';
				$plugin_zip = 'https://wordpress.org/plugin/wp-salesforce.zip';
				$old_plugin_slug = 'wp-salesforce/index.php';
				break;

			// case 'wp-joforce-crm':
			// 	$plugin_slug = 'wp-joforce-crm/wp-joforce-crm.php';
			// 	$plugin_zip = 'https://wordpress.org/plugin/wp-joforce-crm.zip';
			// 	break;

			case 'wp-freshsales':
				$plugin_slug = 'wp-freshsales/wp-freshsales.php';
				$plugin_zip = 'https://wordpress.org/plugin/wp-freshsales.zip';
				$old_plugin_slug = 'wp-freshsales/index.php';
				break;

			case 'wp-zoho-crm':
				$plugin_slug = 'wp-zoho-crm/wp-zoho-crm.php';
				$plugin_zip = 'https://wordpress.org/plugin/wp-zoho-crm.zip';
				$old_plugin_slug = 'wp-zoho-crm/index.php';
				break;

			case 'wp-tiger-support':
				$plugin_slug = 'wp-tiger-support/wp-tiger-support.php';
				$plugin_zip = 'https://wordpress.org/plugin/wp-tiger-support.zip';
				break;

			case 'wp-zendesk-support':
				$plugin_slug = 'wp-zendesk-support/wp-zendesk-support.php';
				$plugin_zip = 'https://wordpress.org/plugin/wp-zendesk-support.zip';
				break;

			case 'wp-freshdesk-support':
				$plugin_slug = 'wp-freshdesk-support/wp-freshdesk-support.php';
				$plugin_zip = 'https://wordpress.org/plugin/wp-freshdesk-support.zip';
				break;

			case 'wp-zohodesk-support':
				$plugin_slug = 'wp-zohodesk-support/wp-zohodesk-support.php';
				$plugin_zip = 'https://wordpress.org/plugin/wp-zohodesk-support.zip';
				break;

			case 'wp-ninja-forms-supports':
				$plugin_slug = 'wp-ninja-forms-supports/wp-ninja-forms-supports.php';
				$plugin_zip = 'https://wordpress.org/plugin/wp-ninja-forms-supports.zip';
				break;

			case 'contact-forms-supports':
				$plugin_slug = 'contact-forms-supports/contact-forms-supports.php';
				$plugin_zip = 'https://wordpress.org/plugin/contact-forms-supports.zip';
				break;

			case 'gravity-forms-supports':
				$plugin_slug = 'gravity-forms-supports/gravity-forms-supports.php';
				$plugin_zip = 'https://wordpress.org/plugin/gravity-forms-supports.zip';
				break;

			case 'qu-form-support':
				$plugin_slug = 'qu-form-support/qu-form-support.php';
				$plugin_zip = 'https://wordpress.org/plugin/qu-form-support.zip';
				break;

			case 'user-sync':
				$plugin_slug = 'user-sync/user-sync.php';
				$plugin_zip = 'https://wordpress.org/plugin/user-sync.zip';
				break;

			case 'woocommerce-support':
				$plugin_slug = 'woocommerce-support/woocommerce-support.php';
				$plugin_zip = 'https://wordpress.org/plugin/woocommerce-support.zip';
				break;
		}

		if ( self::is_plugin_installed( $plugin_slug ) ) {
			self::upgrade_plugin( $plugin_slug );
			$installed = true;
			echo wp_json_encode(['response' => '', 'message' => 'Already installed', 'status' => 200, 'success' => true]);
			wp_die();
		} 

		if ( !is_wp_error( $installed ) && $installed ) {
			$activate = activate_plugin( $plugin_slug );
			if ( is_null($activate) ) {
				if(!empty($old_plugin_slug)){
					deactivate_plugins( array( $old_plugin_slug ) );
				}
				echo wp_json_encode(['response' => '', 'message' => 'Plugin installed', 'status' => 200, 'addon_slug' => $plugin_slug, 'old_addon_slug' => $old_plugin_slug, 'success' => true]);
			}
		} else {
			echo wp_json_encode(['response' => '', 'message' => 'Could not install the new plugin.', 'status' => 200, 'success' => false]);
		}
		wp_die();	
	}

	public function activateAddon(){
		$plugin_slug = sanitize_text_field($_POST['addon_slug']);
		$old_plugin_slug = sanitize_text_field($_POST['old_addon_slug']);
		$activate = activate_plugin( $plugin_slug );
		if ( is_null($activate) ) {
			if(!empty($old_plugin_slug)){
				deactivate_plugins( array( $old_plugin_slug ) );
			}
			echo wp_json_encode(['response' => '', 'message' => 'Plugin Activated', 'status' => 200, 'success' => true]);
		}
		else {
			echo wp_json_encode(['response' => '', 'message' => 'Could not activate the new addon.', 'status' => 200, 'success' => false]);
		}
	}

	/**
	  Check wheather the plugin is already installed
	 **/
	public function is_plugin_installed( $slug ) {
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

	/**
	  Code for Install plugin 
	 **/
	public function install_plugin( $plugin_zip ) {
		include_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
		wp_cache_flush();
		$upgrader = new \Plugin_Upgrader();
		$installed = $upgrader->install( $plugin_zip );
		return $installed;
	}

	public function upgrade_plugin( $plugin_slug ) {
		include_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
		wp_cache_flush();
		$upgrader = new \Plugin_Upgrader();
		$upgraded = $upgrader->upgrade( $plugin_slug );
		return $upgraded;
	}	

}

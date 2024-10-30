<?php
/**
* CRM Connector Plus plugin file. 
*
* Copyright (C) 2010-2020, Smackcoders Inc - info@smackcoders.com 
*/

namespace Smackcoders\WPULB;

if ( ! defined( 'ABSPATH' ) )
exit; // Exit if accessed directly

require_once __DIR__ . '/ManageForms.php';
require_once __DIR__ . '/MappingSection.php';
require_once(plugin_dir_path(__FILE__). '../controllers/ThirdPartyForm.php' );


//A singleton class
class WPULBPlugin
{
	protected static $instance = null;
	static $leads_builder_slug = 'crm-connector-plus';
	static $leads_builder_settings_slug = 'crm-connector-plus-settings';
	protected $pluginSlug = 'crm-connector-plus';
	protected $pluginVersion = '0.1';

	/**
	 * getInstance
	 *
	 * @return void
	 */
	public static function getInstance() {
		//
		if ( null == self::$instance ) {
			self::$instance = new self;
		}
		return self::$instance;
	}

	//Getters
	/**
	 * getPluginSlug
	 *
	 * @return void
	 */
	public function getPluginSlug() {
		return $this->pluginSlug;
	}

	/**
	 * getPluginVersion
	 *
	 * @return void
	 */
	public function getPluginVersion() {
		return $this->pluginVersion;
	}
	
	/**
	 * activate
	 *
	 * @return void
	 */
	public static function activate() {
		PluginTables::createPluginTables();
	}

	/**
	 * The code that runs during plugin deactivation.
	 * 
	 * @return void
	 */
	public static function deactivate() {
		$timestamp = wp_next_scheduled( 'leads_builder_schedule_hook' );
    	wp_unschedule_event( $timestamp, 'leads_builder_schedule_hook' );
	}
}
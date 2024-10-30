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
 * Class Schedule
 * @package Smackcoders\WPULB
 */
class Schedule{
	
	protected static $instance = null;
	
    /**
     * Schedule constructor.
     */
	public function __construct()
	{
		$this->plugin = WPULBPlugin::getInstance();
	}

    /**
     * Schedule Instances.
     * @return null|Schedule
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
        add_action('wp_ajax_wpulb_get_schedule_status',array($this,'getScheduleStatus'));
		add_action('wp_ajax_wpulb_get_schedule_configuration',array($this,'getScheduleConfigured'));	  
	}

	public static function start_scheduler($timing){
		if (!wp_next_scheduled('leads_builder_schedule_hook')) {
            wp_schedule_event(time(), $timing , 'leads_builder_schedule_hook');
        }
	}

    public function getScheduleStatus(){
		$schedule_status = sanitize_text_field($_POST['enabled']);
		$schedule_timing = sanitize_text_field($_POST['time']);
        if($schedule_status == 'true' && isset($schedule_timing) && $schedule_timing != 'undefined'){
			update_option('WPULB_SCHEDULE_STATUS', 'on');
			update_option('WPULB_SCHEDULE_TIME', $schedule_timing);	
			self::$instance->start_scheduler($schedule_timing);
        }
        elseif($schedule_status == 'false'){
			update_option('WPULB_SCHEDULE_STATUS', 'off');
			delete_option('WPULB_SCHEDULE_TIME');
			$timestamp = wp_next_scheduled( 'leads_builder_schedule_hook' );
    		wp_unschedule_event( $timestamp, 'leads_builder_schedule_hook' );
		}	
        echo wp_json_encode(['response' => '', 'message' => 'Updated successfully', 'status' => 200, 'success' => true]);
		wp_die();
	}
	
	public function getScheduleConfigured(){

		$schedule_configured = get_option('WPULB_SCHEDULE_STATUS');
		if($schedule_configured == 'on'){
			$configured = true;
		}elseif($schedule_configured == 'off'){
			$configured = false;
		}

		$time = [];
		$timings = array(
			'5 minutes' => '5min',
			'10 minutes' => '10min',
			'30 minutes' => '30min'
		);

		$temp = 0;
		foreach($timings as $key => $value){
			$time[$temp] = ['label' => $key, 'value' => $value];
			$temp++;
		}

		$configured_time = [];
		$schedule_time = get_option('WPULB_SCHEDULE_TIME');
	
		if(isset($schedule_time)){
			foreach($timings as $key => $value){
				if($schedule_time == $value){
					$configured_time = ['label' => $key, 'value' => $value];
				}
			}
		}

		echo wp_json_encode(['response' => ['timings' => $time , 'configuration' => ['is_configured' => $configured , 'configured_time' => $configured_time ]], 'message' => 'Configuration Details', 'status' => 200, 'success' => true]);
		wp_die();
	}
}
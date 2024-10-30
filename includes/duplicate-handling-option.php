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

global $duplicate_handling_options; 
global $duplicate_handling_array;
global $wpulb_addons_price;

$duplicate_handling_array = [
    'SKIP' => 'Skip',
    'CREATE' => 'Create',
    'UPDATE' => 'Update',
    'SKIP_BOTH' => 'Skip Both',
];

$temp = 0;      
foreach($duplicate_handling_array as $option_value => $option_label){
    $duplicate_handling_options[$temp]['label'] = $option_label;
    $duplicate_handling_options[$temp]['value'] = $option_value;
    $duplicate_handling_options[$temp]['restrict'] = false;
    
    $temp++;
}

$wpulb_addons_price = array(
    'vtigercrm' => 30,
   // 'joforcecrm' => 30,
    'suitecrm' => 30,
    'sugarcrm' => 30,
    'zohocrm' => 30,
    'salesforcecrm' => 30,
    'freshsalescrm' => 30,
    'freshdesk' => 30,
    'zendesk' => 30,
    'vtigersupport' => 30,
    'zohosupport' => 30,
    'qu-form-support' => 30,
    'gravity-forms-supports' => 30,
    'wp-ninja-forms-supports' => 30,
    'contact-forms-supports' => 30,
    'caldera-forms-support' => 30,
    'wp-forms-support' => 30,
    'wp-leads-multi-forms-support' => 30,
    'user-sync' => 30,
    'woocommerce-support' => 30, 
);
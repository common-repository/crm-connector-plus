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

global $supported_addons, $supported_thirdparty_forms , $duplicate_handling_options; 

global $migrated_field_types;
global $form_type_array;

$supported_addons = [
    'crm_addons' => [
        [
            'plugin_slug' => 'vtiger-pro-plus',
            'plugin_filename' => 'vtiger-pro-plus.php', 
            'plugin_label' => 'Vtiger Pro Plus',
            'plugin_namespace' => 'WPULBTiger',
            'plugin_url' => '', 
            'plugin_wpulb_shortname' => 'vtigercrm',
        ],
        [
            'plugin_slug' => 'sugar-pro-plus',
            'plugin_filename' => 'sugar-pro-plus.php', 
            'plugin_label' => 'Sugar Pro Plus',
            'plugin_namespace' => 'WPULBSugar',
            'plugin_url' => '', 
            'plugin_wpulb_shortname' => 'sugarcrm',
        ],
        [
            'plugin_slug' => 'salesforce-pro-plus',
            'plugin_filename' => 'salesforce-pro-plus.php', 
            'plugin_label' => 'Salesforce Pro Plus',
            'plugin_namespace' => 'WPULBSalesforce',
            'plugin_url' => '', 
            'plugin_wpulb_shortname' => 'salesforcecrm',
        ],
        [
            'plugin_slug' => 'joforcecrm-pro-plus',
            'plugin_filename' => 'joforcecrm-pro-plus.php', 
            'plugin_label' => 'JoforceCRM Pro Plus',
            'plugin_namespace' => 'WPULBJoforce',
            'plugin_url' => '', 
            'plugin_wpulb_shortname' => 'joforcecrm',
        ],
        [
            'plugin_slug' => 'freshsales-pro-plus',
            'plugin_filename' => 'freshsales-pro-plus.php', 
            'plugin_label' => 'Freshsales Pro Plus',
            'plugin_namespace' => 'WPULBFreshsales',
            'plugin_url' => '', 
            'plugin_wpulb_shortname' => 'freshsalescrm',
        ],
        [
            'plugin_slug' => 'zoho-crm-pro-plus',
            'plugin_filename' => 'zoho-crm-pro-plus.php', 
            'plugin_label' => 'Zoho CRM Pro Plus',
            'plugin_namespace' => 'WPULBZoho',
            'plugin_url' => '', 
            'plugin_wpulb_shortname' => 'zohocrm',
        ],
        [
            'plugin_slug' => 'suite-pro-plus',
            'plugin_filename' => 'suite-pro-plus.php', 
            'plugin_label' => 'Suite Pro Plus',
            'plugin_namespace' => 'WPULBSuite',
            'plugin_url' => '', 
            'plugin_wpulb_shortname' => 'suitecrm',
        ]
    ],
    'helpdesk_addons' => [
        [
            'plugin_slug' => 'vtiger-ticket-pro-plus',
            'plugin_filename' => 'vtiger-ticket-pro-plus.php', 
            'plugin_label' => 'Vtiger Ticket Pro Plus',
            'plugin_namespace' => 'WPULBVTigerSupport',
            'plugin_url' => '', 
            'plugin_wpulb_shortname' => 'vtigersupport',
        ],
        [
            'plugin_slug' => 'zendesk-pro-plus',
            'plugin_filename' => 'zendesk-pro-plus.php', 
            'plugin_label' => 'Zendesk Pro Plus',
            'plugin_namespace' => 'WPULBZendesk',
            'plugin_url' => '', 
            'plugin_wpulb_shortname' => 'zendesk',
        ],
        [
            'plugin_slug' => 'freshdesk-pro-plus',
            'plugin_filename' => 'freshdesk-pro-plus.php', 
            'plugin_label' => 'Freshdesk Pro Plus',
            'plugin_namespace' => 'WPULBFreshdesk',
            'plugin_url' => '', 
            'plugin_wpulb_shortname' => 'freshdesk',
        ],
        [
            'plugin_slug' => 'zoho-desk-pro-plus',
            'plugin_filename' => 'zoho-desk-pro-plus.php', 
            'plugin_label' => 'Zoho Desk Pro Plus',
            'plugin_namespace' => 'WPULBZohoSupport',
            'plugin_url' => '', 
            'plugin_wpulb_shortname' => 'zohosupport',
        ],
    ]
];

$supported_thirdparty_forms = [
    [
        'plugin_slug' => 'quform',
        'plugin_filename' => 'quform.php', 
        'plugin_label' => 'Qu Form',
        'plugin_url' => '', 
        'plugin_wpulb_uniquename' => 'QU_FORM',
        'leads_form_slug' => 'qu-form-pro-plus',
        'leads_form_filename' => 'qu-form-pro-plus.php',
    ],
    [
        'plugin_slug' => 'gravityforms',
        'plugin_filename' => 'gravityforms.php', 
        'plugin_label' => 'Gravity Form',
        'plugin_url' => '', 
        'plugin_wpulb_uniquename' => 'GRAVITY_FORM',
        'leads_form_slug' => 'gravity-form-pro-plus',
        'leads_form_filename' => 'gravity-form-pro-plus.php',
    ],
    [
        'plugin_slug' => 'ninja-forms',
        'plugin_filename' => 'ninja-forms.php', 
        'plugin_label' => 'Ninja Form',
        'plugin_url' => '',
        'plugin_wpulb_uniquename' => 'NINJA_FORM',
        'leads_form_slug' => 'ninja-form-wp-pro-plus',
        'leads_form_filename' => 'ninja-form-wp-pro-plus.php',
    ],
    [
        'plugin_slug' => 'contact-form-7',
        'plugin_filename' => 'wp-contact-form-7.php', 
        'plugin_label' => 'Contact Form',
        'plugin_url' => '', 
        'plugin_wpulb_uniquename' => 'CONTACT_FORM',
        'leads_form_slug' => 'contact-form-pro-plus',
        'leads_form_filename' => 'contact-form-pro-plus.php',
    ],
    [
        'plugin_slug' => 'caldera-forms',
        'plugin_filename' => 'caldera-core.php', 
        'plugin_label' => 'Caldera Form',
        'plugin_url' => '', 
        'plugin_wpulb_uniquename' => 'CALDERA_FORM',
        'leads_form_slug' => 'caldera-form-pro-plus',
        'leads_form_filename' => 'caldera-form-pro-plus.php',
    ],
    [
        'plugin_slug' => 'wpforms-lite',
        'plugin_filename' => 'wpforms.php', 
        'plugin_label' => 'WP Form',
        'plugin_url' => '', 
        'plugin_wpulb_uniquename' => 'WP_FORM',
        'leads_form_slug' => 'wp-form-pro-plus',
        'leads_form_filename' => 'wp-form-pro-plus.php',
    ],
];

$migrated_field_types = array(
    'string' => 'Text',
    'boolean' => 'Boolean',
    'text' => 'Text',
    'multipicklist' => 'Multi-Select',
    'datetime' => 'DateTime',
    'picklist' => 'Select',
    'currency' => 'Currency',
    'url' => 'Text',
    'email' => 'Text',
    'phone' => 'Phone',
    'integer' => 'Integer',
    'date' => 'Date',
    'email' => 'Email'
    );

    $form_type_array = array(
        'Ninja Form' => 'NINJA_FORM',
        'Contact Form' => 'CONTACT_FORM',
        'Gravity Form' => 'GRAVITY_FORM',
        'Qu Form' => 'QU_FORM',
        'Caldera Form' => 'CALDERA_FORM',
        'WP Form' => 'WP_FORM',
        'Default Form' => 'DEFAULT_FORM'
    );
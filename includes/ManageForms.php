<?php
/**
* CRM Connector Plus plugin file. 
*
* Copyright (C) 2010-2020, Smackcoders Inc - info@smackcoders.com 
*/

namespace Smackcoders\WPULB;

if (!defined('ABSPATH')) {
    exit(); // Exit if accessed directly
}

require_once(plugin_dir_path(__FILE__). 'Admin.php');
require_once(plugin_dir_path(__FILE__). '../controllers/ContactFormsSupport.php');

class ManageForms
{
    protected static $instance,
        $admin_instance = null;
    protected static $mapping_instance = null;
    protected static $shortcode_instance = null;
    protected static $third_party_instance = null;
    protected static $contact_form_instance = null;
    /**
     * __construct
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * getInstance
     *
     * @return void
     */
    public static function getInstance()
    {
        if (self::$instance == null) {
            self::$instance = new self();
            self::$instance->doHooks();
            self::$admin_instance = WPULBAdmin::getInstance();
            self::$mapping_instance = MappingSection::getInstance();
            self::$third_party_instance = ThirdPartyForm::getInstance();
            self::$contact_form_instance = ContactFormsSupport::getInstance();
        }

        return self::$instance;
    }

    /**
     * doHooks
     *
     * @return void
     */
    public function doHooks()
    {
        if (
            !is_plugin_active(
                'leads-multi-forms-pro-plus/leads-multi-forms-pro-plus.php'
            )
        ) {
            add_action('wp_ajax_wpulb_get_form_list', [$this, 'fetchAllForms']);
        }

        add_action('wp_ajax_wpulb_show_create_form_step1', [
            $this,
            'createFormStep1',
        ]);
        add_action('wp_ajax_wpulb_show_create_form_step2', [
            $this,
            'createFormStep2',
        ]);
        add_action('wp_ajax_wpulb_show_mapping_section', [
            $this,
            'showMappingSection',
        ]);

        add_action('wp_ajax_wpulb_reorder_default_with_crm', [
            $this,
            'reorderDefaultWithCrm',
        ]);
        add_action('wp_ajax_wpulb_default_form_settings', [
            $this,
            'defaultFormSettings',
        ]);

        add_action('wp_ajax_wpulb_edit_form', [$this, 'editForm']);
        add_action('wp_ajax_wpulb_delete_form', [$this, 'deleteForm']);
    }

    /**
     * createFormStep1
     *
     * @return void
     */
    public static function createFormStep1()
    {
        global $wpdb;
        global $supported_thirdparty_forms;
        $allowed_forms = [];
        $temp = 0;

        foreach ($supported_thirdparty_forms as $supported_thirdparty_form) {
            if (
                $supported_thirdparty_form['plugin_wpulb_uniquename'] ==
                    'CONTACT_FORM' &&
                is_plugin_active(
                    $supported_thirdparty_form['plugin_slug'] .
                        '/' .
                        $supported_thirdparty_form['plugin_filename']
                ) &&
                !is_plugin_active(
                    $supported_thirdparty_form['leads_form_slug'] .
                        '/' .
                        $supported_thirdparty_form['leads_form_filename']
                )
            ) {
                $form_type = $supported_thirdparty_form['plugin_wpulb_uniquename'];
                $form_count = $wpdb->get_var($wpdb->prepare("SELECT count(*) FROM {$wpdb->prefix}smack_ulb_shortcode_manager WHERE form_type = %s", $form_type));

                $allowed_forms[$temp]['label'] =
                    $supported_thirdparty_form['plugin_label'];
                $allowed_forms[$temp]['value'] =
                    $supported_thirdparty_form['plugin_wpulb_uniquename'];
                if ($form_count == 0) {
                    $allowed_forms[$temp]['restrict'] = false;
                } else {
                    $allowed_forms[$temp]['restrict'] = true;
                }
                $temp++;
            } else {
                if (
                    is_plugin_active(
                        $supported_thirdparty_form['plugin_slug'] .
                            '/' .
                            $supported_thirdparty_form['plugin_filename']
                    ) &&
                    is_plugin_active(
                        $supported_thirdparty_form['leads_form_slug'] .
                            '/' .
                            $supported_thirdparty_form['leads_form_filename']
                    )
                ) {
                    $allowed_forms[$temp]['label'] =
                        $supported_thirdparty_form['plugin_label'];
                    $allowed_forms[$temp]['value'] =
                        $supported_thirdparty_form['plugin_wpulb_uniquename'];
                    $allowed_forms[$temp]['restrict'] = false;

                    $temp++;
                }
            }
        }

        if (
            is_plugin_active(
                'leads-multi-forms-pro-plus/leads-multi-forms-pro-plus.php'
            )
        ) {
            $allowed_forms[$temp]['label'] = 'Default Form';
            $allowed_forms[$temp]['value'] = 'DEFAULT_FORM';
            $allowed_forms[$temp]['restrict'] = false;
        }

        $has_crm_addon = get_option('WPULB_CONNECTED_CRM_CREDENTIALS');
        $has_crm_addon = $has_crm_addon ? true : false;
        $has_helpdesk_addon = get_option(
            'WPULB_CONNECTED_HELPDESK_CREDENTIALS'
        );
        $has_helpdesk_addon = $has_helpdesk_addon ? true : false;

        // Maintain this shortcode until the end
        $shortcode = ManageForms::generateShortcode();

        echo wp_json_encode([
            'response' => [
                'forms' => $allowed_forms,
                'shortcode' => $shortcode,
                'has_helpdesk_addon' => $has_helpdesk_addon,
                'has_crm_addon' => $has_crm_addon,
            ],
            'message' => 'All Forms',
            'status' => 200,
            'success' => true,
        ]);
        wp_die();
    }

    public static function generateShortcode()
    {
        $length = 5;
        $characters =
            '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, strlen($characters) - 1)];
        }
        return $randomString;
    }

    /**
     * createFormStep2
     *
     * @return void
     */
    public static function createFormStep2()
    {
        $form_type = sanitize_text_field($_GET['form_type']);
        $addon = sanitize_text_field($_GET['addon']);
        $shortcode = sanitize_text_field($_GET['shortcode']);

        if ($addon == 'CRM' || $addon == 'HELPDESK') {
            // Any addon is enabled
            if ($form_type == 'DEFAULT_FORM') {
                // Scenario 1
                ManageForms::showDefaultCRMModuleConfiguration(
                    $form_type,
                    $addon,
                    $shortcode
                );
            } else {
                // Any thirdparty form is choosen // Scenario 2
                // do some stuff with this scenario
                ManageForms::showCRMModuleConfiguration(
                    $form_type,
                    $addon,
                    $shortcode
                );
            }
        } elseif ($addon == 'DATA_BUCKET_ONLY') {
            if ($form_type == 'DEFAULT_FORM') {
                // Scenario 3
                if (
                    !is_plugin_active(
                        'leads-multi-forms-pro-plus/leads-multi-forms-pro-plus.php'
                    )
                ) {
                    ManageForms::showFieldChoosingSection();
                } else {
                    global $multiform_addon_instance;
                    $multiform_addon_instance->showFieldChoosingSection(
                        'via_databucket'
                    );
                }
            } else {
                // Scenario 4
                ManageForms::showThirdpartyFormChoosingSection();
            }
        }
    }

    /**
     * showThirdpartyFormChoosingSection
     *
     * @param  mixed $form_type
     * @param  mixed $shortcode
     *
     * @return void
     */
    public static function showThirdpartyFormChoosingSection()
    {
        $form_type = sanitize_text_field($_GET['form_type']);
        $shortcode = sanitize_text_field($_GET['shortcode']);
        $thirdparty_forms = ManageForms::getThirdPartyForms(
            $form_type,
            $shortcode
        );

        echo wp_json_encode([
            'response' => [
                'active_forms' => $thirdparty_forms,
                'shortcode' => $shortcode,
                'form_type' => $form_type,
            ],
            'message' => 'Active Thirdparty forms',
            'status' => 200,
            'success' => true,
        ]);
        wp_die();
    }

    /**
     * showCRMModuleConfiguration
     *
     * @param  mixed $form_type
     * @param  mixed $addon
     * @param  mixed $shortcode
     *
     * @return void
     */
    public static function showCRMModuleConfiguration(
        $form_type,
        $addon,
        $shortcode
    ) {
        // Get all available forms from the thirdparty form plugins
        global $duplicate_handling_options;

        if ($addon == 'HELPDESK') {
            unset($duplicate_handling_options[3]);
        }

        $active_forms = ManageForms::getThirdPartyForms($form_type, $shortcode);
        $modules = ManageForms::getAllowedModules($addon);
        $record_owner_options = ManageForms::getRecordOwnerOptions(
            $addon,
            'RoundRobin'
        );

        echo wp_json_encode([
            'response' => [
                'modules' => ManageForms::returnResultTORequestedFormat(
                    $modules
                ),
                'active_forms' => $active_forms,
                'duplicate_handling_options' => $duplicate_handling_options,
                'record_owner_options' => ManageForms::returnResultTORequestedFormat(
                    $record_owner_options
                ),
            ],
            'message' => 'CRM Module Configuration',
            'status' => 200,
            'success' => true,
        ]);
        wp_die();
    }

    public static function showDefaultCRMModuleConfiguration(
        $form_type,
        $addon,
        $shortcode
    ) {
        global $duplicate_handling_options;

        if ($addon == 'HELPDESK') {
            unset($duplicate_handling_options[3]);
        }

        $active_forms = ManageForms::getThirdPartyForms($form_type, $shortcode);
        $modules = ManageForms::getAllowedModules($addon);
        $record_owner_options = ManageForms::getRecordOwnerOptions(
            $addon,
            'RoundRobin'
        );
        // $fields_by_grouping = ManageFields::fetchAllFields('show-fields-for-mapping');

        if (
            !is_plugin_active(
                'leads-multi-forms-pro-plus/leads-multi-forms-pro-plus.php'
            )
        ) {
            $fields_by_grouping = ManageFields::fetchAllFields(
                'show-fields-for-mapping'
            );
        } else {
            global $multiform_addon_instance;
            $fields_by_grouping = $multiform_addon_instance->showFieldChoosingSection(
                'via_crm'
            );
        }

        echo wp_json_encode([
            'response' => [
                'fields_by_grouping' => $fields_by_grouping,
                'modules' => ManageForms::returnResultTORequestedFormat(
                    $modules
                ),
                'active_forms' => $active_forms,
                'duplicate_handling_options' => $duplicate_handling_options,
                'record_owner_options' => ManageForms::returnResultTORequestedFormat(
                    $record_owner_options
                ),
            ],
            'message' => 'CRM Module Configuration',
            'status' => 200,
            'success' => true,
        ]);
        wp_die();
    }

    /**
     * returnResultTORequestedFormat
     *
     * @param  mixed $options
     *
     * @return void
     */
    public static function returnResultTORequestedFormat($options = [])
    {
        $picklist_values = [];
        $temp = 0;

        if (ManageForms::isAssociativeArray($options) === true) {
            foreach ($options as $option_value => $option_label) {
                $picklist_values[$temp]['label'] = $option_label;
                $picklist_values[$temp]['value'] = strval($option_value);
                $temp++;
            }
        } else {
            foreach ($options as $option) {
                $picklist_values[$temp]['label'] = $option;
                $picklist_values[$temp]['value'] = $option;
                $temp++;
            }
        }

        return $picklist_values;
    }

    public static function isAssociativeArray(array $arr)
    {
        if ([] === $arr) {
            return false;
        }
        return array_keys($arr) !== range(0, count($arr) - 1);
    }

    /**
     * showFieldChoosingSection
     *
     * @param  mixed $form_type
     * @param  mixed $shortcode
     *
     * @return void
     */
    public static function showFieldChoosingSection()
    {
        $fields_by_grouping = ManageFields::fetchAllFields(
            'show-fields-for-mapping'
        );
        echo wp_json_encode([
            'response' => ['fields_by_grouping' => $fields_by_grouping],
            'message' => 'Field choosing section',
            'status' => 200,
            'success' => true,
        ]);
        wp_die();
    }

    /**
     * showMappingSection
     *
     * @return void
     */

    public static function showMappingSection()
    {
        $form_type = sanitize_text_field($_GET['form_type']);
        $shortcode = sanitize_text_field($_GET['shortcode']);
        $sync_to = sanitize_text_field($_GET['sync_to']);
        global $wpdb;

        if ($form_type == 'DEFAULT_FORM') {
            $shortcode = self::$mapping_instance->retrieve_shortcode(
                $shortcode
            );

            $enabled_fields = [];
            //$fields = ManageFields::fetchAllFields('field-name-and-label');
            $req_enabled_fields = str_replace(
                "\\",
                '',
                sanitize_text_field($_GET['enabled_fields'])
            );
            $req_enabled_fields = json_decode($req_enabled_fields, true);
            $enabled_fields = array_keys($req_enabled_fields);

            $native_form_info = get_option(
                "WPULB_INFO_DEFAULT_FORM_{$shortcode}"
            );
            $native_form_info['enabled_fields'] = $enabled_fields;

            $fields_name_and_label = ManageFields::fetchAllFields(
                'field-name-and-label'
            );

            $thirdparty_form_fields = [];
            $default_form_fields = $enabled_fields;
            foreach ($default_form_fields as $field_name) {
                $field_label = $wpdb->get_var( $wpdb->prepare( "SELECT field_label FROM {$wpdb->prefix}smack_ulb_manage_fields WHERE field_name = %s", $field_name));
                $thirdparty_form_fields[$field_name] = $field_label;
            }

            if ($sync_to == 'CRM') {
                $crm_type = get_option('WPULB_ACTIVE_CRM_ADDON');

                $native_form_info['connected_crm'] = $crm_type;
                $module = $native_form_info['module'];
                $addon_fields = ManageFields::getAddonFields(
                    get_option('WPULB_ACTIVE_CRM_ADDON'),
                    $module,
                    $sync_to
                );
            } elseif ($sync_to == 'HELPDESK') {
                $help_type = get_option('WPULB_ACTIVE_HELPDESK_ADDON');

                $native_form_info['connected_help'] = $help_type;
                $module = $native_form_info['module'];
                $addon_fields = ManageFields::getAddonFields(
                    get_option('WPULB_ACTIVE_HELPDESK_ADDON'),
                    $module,
                    $sync_to
                );
            }
            update_option(
                'WPULB_INFO_DEFAULT_FORM_' . $shortcode,
                $native_form_info
            );
        } else {
            if ($form_type == 'CALDERA_FORM') {
                $form_id = intval($_GET['form_id']);
            } else {
                $form_id = intval($_GET['form_id']);
            }

            $other_form_info = [];

            if ($sync_to != 'DATA_BUCKET_ONLY') {
                $duplicate_handling = sanitize_text_field(
                    $_GET['duplicate_handling']
                );
                $record_owner = sanitize_text_field($_GET['record_owner']);
                $module = sanitize_text_field($_GET['module']);

                $other_form_info['module'] = $module;
                $other_form_info['owner'] = $record_owner;
                $other_form_info['duplicate_handle'] = $duplicate_handling;
            }

            if ($sync_to == 'CRM') {
                $crm_type = get_option('WPULB_ACTIVE_CRM_ADDON');
                $other_form_info['connected_crm'] = $crm_type;

                $addon_fields = ManageFields::getAddonFields(
                    get_option('WPULB_ACTIVE_CRM_ADDON'),
                    $module,
                    $sync_to
                );
                $fields_name_and_label = ManageFields::fetchAllFields(
                    'field-name-and-label'
                );
            } elseif ($sync_to == 'HELPDESK') {
                $help_type = get_option('WPULB_ACTIVE_HELPDESK_ADDON');
                $other_form_info['connected_help'] = $help_type;

                $addon_fields = ManageFields::getAddonFields(
                    get_option('WPULB_ACTIVE_HELPDESK_ADDON'),
                    $module,
                    $sync_to
                );
                $fields_name_and_label = ManageFields::fetchAllFields(
                    'field-name-and-label'
                );
            } elseif ($sync_to == 'DATA_BUCKET_ONLY') {
                $other_form_info['connected_crm'] = 'wpulb_crm';
                $databucket_fields = ManageFields::fetchAllFields(
                    'field-name-and-label'
                );

                $addon_fields = self::$third_party_instance->change_array_to_asso_array(
                    $databucket_fields
                );
                $fields_name_and_label = [];
            }

            $thirdparty_form_fields = ManageForms::getThirdpartyFormFields(
                $form_type,
                $form_id
            );
            update_option(
                "WPULB_INFO_{$form_type}_{$form_id}",
                $other_form_info
            );
        }

        if (
            $thirdparty_form_fields == 'Not a valid key' ||
            $thirdparty_form_fields ==
                'Activate the domain in your my account page'
        ) {
            echo wp_json_encode([
                'response' => '',
                'message' => $thirdparty_form_fields . ' Form Addon',
                'status' => 200,
                'success' => false,
            ]);
        } else {
            echo wp_json_encode([
                'response' => [
                    'shortcode' => $shortcode,
                    'form_type' => $form_type,
                    'addon_fields' => $addon_fields,
                    'data_bucket_fields' => ManageForms::returnResultTORequestedFormat(
                        $fields_name_and_label
                    ),
                    'form_fields' => ManageForms::returnResultTORequestedFormat(
                        $thirdparty_form_fields
                    ),
                ],
                'message' => 'Thirdparty form step2 section',
                'status' => 200,
                'success' => true,
            ]);
        }
        wp_die();
    }

    public static function reorderDefaultWithCrm()
    {
        $form_type = sanitize_text_field($_POST['form_type']);
        $shortcode = sanitize_text_field($_POST['shortcode']);
        $duplicate_handling = sanitize_text_field($_POST['duplicate_handling']);
        $record_owner = sanitize_text_field($_POST['record_owner']);
        $module = sanitize_text_field($_POST['module']);
        $sync_to = sanitize_text_field($_POST['sync_to']);
        global $wpdb;
        $forms = [];
        $info = [];
        $native_form_info = [];
        $mandatory_fields = [];
        $fields = ManageFields::fetchAllFields('field-name-and-label');
        $req_mandatory_fields = str_replace(
            "\\",
            '',
            sanitize_text_field($_POST['mandatory_fields'])
        );
        $req_mandatory_fields = json_decode($req_mandatory_fields, true);
        $req_enabled_fields = str_replace(
            "\\",
            '',
            sanitize_text_field($_POST['enabled_fields'])
        );
        $req_enabled_fields = json_decode($req_enabled_fields, true);

        foreach ($fields as $field_key => $field) {
            if (isset($req_enabled_fields[$field_key])) {
        
                $field_type = $wpdb->get_var( $wpdb->prepare("SELECT field_type FROM {$wpdb->prefix}smack_ulb_manage_fields WHERE field_name = %s", $field_key));
                $forms['label'] = $field;
                $forms['value'] = $field_key;
                $forms['type'] = $field_type;

                if (isset($req_mandatory_fields[$field_key])) {
                    $forms['mandatory'] = true;
                } else {
                    $forms['mandatory'] = false;
                }
                array_push($info, $forms);

                if (isset($req_mandatory_fields[$field_key])) {
                    $mandatory_fields[] = $field_key;
                }
            }
        }

        if ($sync_to == 'CRM' || $sync_to == 'HELPDESK') {
            $native_form_info['module'] = $module;
            $native_form_info['owner'] = $record_owner;
            $native_form_info['duplicate_handle'] = $duplicate_handling;
        }

        $native_form_info['mandatory_fields'] = $mandatory_fields;
        $native_form_info['before_sorting'] = $info;
        update_option(
            "WPULB_INFO_DEFAULT_FORM_{$shortcode}",
            $native_form_info
        );

        //echo wp_json_encode(['response' => ['forms' => $info], 'message' => 'Selected fields', 'status' => 200, 'success' => true]);

        $widget_options = ['None', 'Post', 'Widget'];
        $form_type = [];
        $temp = 0;
        foreach ($widget_options as $values) {
            $form_type[$temp]['label'] = $values;
            $form_type[$temp]['value'] = $values;
            $temp++;
        }

        global $supported_thirdparty_forms;
        $allowed_forms = [];

        $allowed_forms[0]['label'] = 'None';
        $allowed_forms[0]['value'] = 'None';

        $temp = 1;
        foreach ($supported_thirdparty_forms as $supported_thirdparty_form) {
            if ($supported_thirdparty_form['plugin_label'] != 'Qu Form') {
                //if( is_plugin_active( $supported_thirdparty_form['plugin_slug'] . '/' . $supported_thirdparty_form['plugin_filename']) ) {
                if (
                    is_plugin_active(
                        $supported_thirdparty_form['plugin_slug'] .
                            '/' .
                            $supported_thirdparty_form['plugin_filename']
                    ) &&
                    is_plugin_active(
                        $supported_thirdparty_form['leads_form_slug'] .
                            '/' .
                            $supported_thirdparty_form['leads_form_filename']
                    )
                ) {
                    $allowed_forms[$temp]['label'] =
                        $supported_thirdparty_form['plugin_label'];
                    $allowed_forms[$temp]['value'] =
                        $supported_thirdparty_form['plugin_wpulb_uniquename'];
                    $temp++;
                }
            }
        }

        $check_google_captcha_enabled = get_option("WPULB_ENABLE_CAPTCHA");
        if ($check_google_captcha_enabled == 'on') {
            $google_captcha = true;
        } else {
            $google_captcha = false;
        }

        echo wp_json_encode([
            'response' => [
                'form_type' => $form_type,
                'third_party_forms' => $allowed_forms,
                'captcha_enabled' => $google_captcha,
            ],
            'message' => 'Default form settings',
            'status' => 200,
            'success' => true,
        ]);
        wp_die();
    }

    /**
     * getThirdPartyForms
     *
     * @param  mixed $form_type
     *
     * @return void
     */
    public static function getThirdPartyForms($form_type, $shortcode)
    {
        global $wpdb;
        $available_forms = [];
        switch ($form_type) {
            case 'QU_FORM':
                global $qu_addon_instance;
                $available_forms = $qu_addon_instance->all_qu_forms(
                    $available_forms
                );
                break;
            case 'GRAVITY_FORM':
                global $gravity_addon_instance;
                $available_forms = $gravity_addon_instance->all_gravity_forms(
                    $available_forms
                );
                break;
            case 'NINJA_FORM':
                global $ninja_addon_instance;
                $available_forms = $ninja_addon_instance->all_ninja_forms(
                    $available_forms
                );
                break;
            case 'CONTACT_FORM':
                if (
                    !is_plugin_active(
                        'contact-form-pro-plus/contact-form-pro-plus.php'
                    )
                ) {
                    $available_forms = self::$contact_form_instance->all_contact_forms(
                        $available_forms
                    );
                } else {
                    global $contact_addon_instance;
                    $available_forms = $contact_addon_instance->all_contact_forms(
                        $available_forms
                    );
                }
                break;
            case 'CALDERA_FORM':
                global $caldera_addon_instance;
                $available_forms = $caldera_addon_instance->all_caldera_forms(
                    $available_forms
                );
                break;
            case 'WP_FORM':
                global $wpform_addon_instance;
                $available_forms = $wpform_addon_instance->all_wp_forms(
                    $available_forms
                );
                break;

            default:
                // DEFAULT_FORM
                $available_forms = [];
                break;
        }

        $all_available_forms = [];
        $temp = 0;

        if (!empty($available_forms)) {
            foreach ($available_forms as $form_keys => $form_values) {
                $get_form_count = $wpdb->get_var($wpdb->prepare("SELECT count(*) FROM {$wpdb->prefix}smack_ulb_shortcode_manager WHERE shortcode_name = %s AND form_id = %s AND form_type = %s ", $form_values, $form_keys, $form_type ));
                $all_available_forms[$temp]['label'] = $form_values;
                $all_available_forms[$temp]['value'] = $form_keys;

                if ($get_form_count == 0) {
                    $get_all_shortcodes = $wpdb->get_results($wpdb->prepare( "SELECT shortcode_name FROM {$wpdb->prefix}smack_ulb_shortcode_manager WHERE form_type = %s", $form_type), ARRAY_A);

                    $all_shortcodes = array_column(
                        $get_all_shortcodes,
                        'shortcode_name'
                    );

                    if (in_array($shortcode, $all_shortcodes)) {
                        $all_available_forms[$temp]['restrict'] = true;
                    } else {
                        $all_available_forms[$temp]['restrict'] = false;
                    }
                } else {
                    if ($form_values == $shortcode) {
                        $all_available_forms[$temp]['restrict'] = false;
                    } else {
                        $all_available_forms[$temp]['restrict'] = true;
                    }
                }
                $temp++;
            }
        } else {
            $all_available_forms = $available_forms;
        }
        return $all_available_forms;
    }

    /**
     * getThirdpartyFormFields
     *
     * @param  mixed $form_type
     * @param  mixed $form_id
     *
     * @return void
     */
    public static function getThirdpartyFormFields($form_type, $form_id)
    {
        switch ($form_type) {
            case 'QU_FORM':
                global $qu_addon_instance;
                $form_fields = $qu_addon_instance->all_qu_fields($form_id);
                break;
            case 'GRAVITY_FORM':
                global $gravity_addon_instance;
                $form_fields = $gravity_addon_instance->all_gravity_fields(
                    $form_id
                );
                break;
            case 'NINJA_FORM':
                global $ninja_addon_instance;
                $form_fields = $ninja_addon_instance->all_ninja_fields(
                    $form_id
                );
                break;
            case 'CONTACT_FORM':
                if (
                    !is_plugin_active(
                        'contact-form-pro-plus/contact-form-pro-plus.php'
                    )
                ) {
                    $form_fields = self::$contact_form_instance->all_contact_fields(
                        $form_id
                    );
                } else {
                    global $contact_addon_instance;
                    $form_fields = $contact_addon_instance->all_contact_fields(
                        $form_id
                    );
                }
                break;
            case 'CALDERA_FORM':
                global $caldera_addon_instance;
                $form_fields = $caldera_addon_instance->all_caldera_fields(
                    $form_id
                );
                break;
            case 'WP_FORM':
                global $wpform_addon_instance;
                $form_fields = $wpform_addon_instance->all_wpform_fields(
                    $form_id
                );
                break;
        }

        return $form_fields;
    }

    /**
     * getRecordOwnerOptions
     *
     * @param  mixed $want_to_add_round_robin
     *
     * @return void
     */
    public static function getRecordOwnerOptions(
        $addon,
        $want_to_add_round_robin = false
    ) {
        if ($addon == 'CRM') {
            $users = get_option('WPULB_USERS_CONNECTED');
            if (empty($users)) {
                self::$admin_instance->getCrmUsersList();
                $users = get_option('WPULB_USERS_CONNECTED');
            }
        } elseif ($addon == 'HELPDESK') {
            $users = get_option('WPULB_HELP_USERS_CONNECTED');
            if (empty($users)) {
                self::$admin_instance->getHelpUsersList();
                $users = get_option('WPULB_HELP_USERS_CONNECTED');
            }
        }

        $all_users = [];
        if (!empty($users)) {
            foreach ($users as $user) {
                $all_users[$user['user_id']] = $user['user_name'];
            }
        }
        if ($want_to_add_round_robin) {
            $all_users[$want_to_add_round_robin] = 'Round Robin';
        }
        return $all_users;
    }

    /**
     * getAllowedModules
     *
     * @param  mixed $addon_shortname
     *
     * @return void
     */
    public static function getAllowedModules($addon_shortname)
    {
        global $wpdb;
        if ($addon_shortname == 'CRM') {
            return ['Leads', 'Contacts'];
        } elseif ($addon_shortname == 'HELPDESK') {
            return ['Tickets', 'Contacts'];
        }
    }

    /**
     * fetchAllForms
     *
     * @return void
     */
    public static function fetchAllForms($return_as_array = false)
    {
        global $wpdb;
        global $supported_thirdparty_forms;
        $is_thirdparty_form = false;

        $default_form_posts = [
            'Post' => 'post',
            'Widget' => 'widget',
        ];

        foreach ($default_form_posts as $default_key => $default_value) {
            ManageForms::store_default_form_info_on_creation(
                $default_key,
                $default_value
            );
        }

        $query = "SELECT * FROM {$wpdb->prefix}smack_ulb_shortcode_manager WHERE status in ('Created','Updated')";
        $total_query = "SELECT COUNT(1) FROM (${query}) AS combined_table";
        $total = $wpdb->get_var($total_query);
        // Records per Page
        $items_per_page = get_option('posts_per_page');
        $page = isset($_REQUEST['cpage']) ? abs((int) $_REQUEST['cpage']) : 1;

        $offset = $page * $items_per_page - $items_per_page;
        $forms = $wpdb->get_results(
            $query .
                " ORDER BY updated_at DESC LIMIT ${offset}, ${items_per_page}"
        );
        $totalPage = ceil($total / $items_per_page);

        if ($return_as_array) {
            return $forms;
        }

        $all_active_forms = [];
        foreach ($forms as $form_key => $form_value) {
            $form_type = $form_value->form_type;
            if ($form_type == 'CONTACT_FORM') {
                if (is_plugin_active('contact-form-7/wp-contact-form-7.php')) {
                    array_push($all_active_forms, $form_value);
                }
            } elseif ($form_type == 'NINJA_FORM') {
                if (
                    is_plugin_active('ninja-forms/ninja-forms.php') &&
                    is_plugin_active(
                        'ninja-form-wp-pro-plus/ninja-form-wp-pro-plus.php'
                    )
                ) {
                    array_push($all_active_forms, $form_value);
                }
            } elseif ($form_type == 'GRAVITY_FORM') {
                if (
                    is_plugin_active('gravityforms/gravityforms.php') &&
                    is_plugin_active(
                        'gravity-form-pro-plus/gravity-form-pro-plus.php'
                    )
                ) {
                    array_push($all_active_forms, $form_value);
                }
            } elseif ($form_type == 'QU_FORM') {
                if (
                    is_plugin_active('quform/quform.php') &&
                    is_plugin_active('qu-form-pro-plus/qu-form-pro-plus.php')
                ) {
                    array_push($all_active_forms, $form_value);
                }
            } elseif ($form_type == 'CALDERA_FORM') {
                if (
                    is_plugin_active('caldera-forms/caldera-core.php') &&
                    is_plugin_active(
                        'caldera-form-pro-plus/caldera-form-pro-plus.php'
                    )
                ) {
                    array_push($all_active_forms, $form_value);
                }
            } elseif ($form_type == 'WP_FORM') {
                if (
                    is_plugin_active('wpforms-lite/wpforms.php') &&
                    is_plugin_active('wp-form-pro-plus/wp-form-pro-plus.php')
                ) {
                    array_push($all_active_forms, $form_value);
                }
            } elseif ($form_type == 'DEFAULT_FORM') {
                array_push($all_active_forms, $form_value);
            }
        }

        foreach ($all_active_forms as $key => $form_value) {
            $crm_type = $form_value->crm_type;
            $module = $form_value->module;
            $form_type = $form_value->form_type;

            $crm_array = [
                'vtigercrm',
                'sugarcrm',
                'salesforcecrm',
                'joforcecrm',
                'freshsalescrm',
                'zohocrm',
                'suitecrm',
            ];
            $help_array = [
                'vtigersupport',
                'zendesk',
                'freshdesk',
                'zohosupport',
            ];

            if ($crm_type == 'wpulb_crm') {
                $addon = 'DATA_BUCKET_ONLY';
            } elseif (in_array($crm_type, $crm_array)) {
                $addon = 'CRM';
            } elseif (in_array($crm_type, $help_array)) {
                $addon = 'HELPDESK';
            }

            if ($addon == 'DATA_BUCKET_ONLY') {
                $all_active_forms[$key]->sync_to = 'none';
            } elseif ($addon == 'CRM') {
                global $supported_addons;
                foreach ($supported_addons['crm_addons'] as $addons) {
                    if ($addons['plugin_wpulb_shortname'] == $crm_type) {
                        $all_active_forms[$key]->sync_to =
                            $addons['plugin_label'] . ' - ' . $module;
                    }
                }
            } elseif ($addon == 'HELPDESK') {
                global $supported_addons;
                foreach ($supported_addons['helpdesk_addons'] as $addons) {
                    if ($addons['plugin_wpulb_shortname'] == $crm_type) {
                        $all_active_forms[$key]->sync_to =
                            $addons['plugin_label'] . ' - ' . $module;
                    }
                }
            }

            if ($form_type == 'DEFAULT_FORM') {
                $all_active_forms[$key]->formType = 'Default Form';
                $all_active_forms[$key]->shortcode_name =
                    '[smack-web-form name=' . $form_value->shortcode_name . ']';
            } else {
                foreach ($supported_thirdparty_forms as $supported_forms) {
                    if (
                        $supported_forms['plugin_wpulb_uniquename'] ==
                        $form_type
                    ) {
                        $all_active_forms[$key]->formType =
                            $supported_forms['plugin_label'];
                    }
                }
            }
        }

        foreach ($supported_thirdparty_forms as $supported_thirdparty_form) {
            if ($supported_thirdparty_form['plugin_slug'] == 'contact-form-7') {
                if (
                    is_plugin_active(
                        $supported_thirdparty_form['plugin_slug'] .
                            '/' .
                            $supported_thirdparty_form['plugin_filename']
                    )
                ) {
                    $is_thirdparty_form = true;
                }
            } else {
                if (
                    is_plugin_active(
                        $supported_thirdparty_form['plugin_slug'] .
                            '/' .
                            $supported_thirdparty_form['plugin_filename']
                    ) &&
                    is_plugin_active(
                        $supported_thirdparty_form['leads_form_slug'] .
                            '/' .
                            $supported_thirdparty_form['leads_form_filename']
                    )
                ) {
                    $is_thirdparty_form = true;
                }
            }
        }

        $message1 = "";
        $message2 = "";
        global $supported_addons;
        $get_configured_addon = get_option("WPULB_ACTIVE_CRM_ADDON");

        if ($get_configured_addon != 'joforcecrm') {
            foreach ($supported_addons['crm_addons'] as $addons) {
                if (
                    $addons['plugin_wpulb_shortname'] == $get_configured_addon
                ) {
                    if (
                        !is_plugin_active(
                            $addons['plugin_slug'] .
                                '/' .
                                $addons['plugin_filename']
                        )
                    ) {
                        $configure_crm_addon = $addons['plugin_label'];
                        $message1 = "Please activate $configure_crm_addon addon before submitting forms";
                    }
                }
            }
        }

        $get_configured_helpdesk = get_option("WPULB_ACTIVE_HELPDESK_ADDON");
        foreach ($supported_addons['helpdesk_addons'] as $addons) {
            if ($addons['plugin_wpulb_shortname'] == $get_configured_helpdesk) {
                if (
                    !is_plugin_active(
                        $addons['plugin_slug'] .
                            '/' .
                            $addons['plugin_filename']
                    )
                ) {
                    $configure_help_addon = $addons['plugin_label'];
                    $message2 = "Please activate $configure_help_addon addon before submitting forms";
                }
            }
        }

        if (!empty($message1) && !empty($message2)) {
            $message = "Please activate $configure_crm_addon and $configure_help_addon addon before submitting forms";
        } else {
            $message = $message1 . $message2;
        }

        $migration_alert = "";
        $get_databucket_submissions = $wpdb->get_results($wpdb->prepare( "SELECT * FROM {$wpdb->prefix}smack_ulb_databucket_meta WHERE sync_status = %s", 'CRM/Helpdesk not configured'));

        $get_first_time_crm_configuration = get_option(
            "WPULB_FIRST_TIME_CRM_CONFIGURATION"
        );
        $get_first_time_help_configuration = get_option(
            "WPULB_FIRST_TIME_HELP_CONFIGURATION"
        );

        if (
            !empty($get_databucket_submissions) &&
            $get_first_time_crm_configuration == 'yes'
        ) {
            $migration_alert = "Move already submitted form details from Databucket to $get_configured_addon by configuring mapping and enabling Migration Settings in Settings Tab";
            update_option("WPULB_FIRST_TIME_CRM_CONFIGURATION", 'no');
        }
        if (
            !empty($get_databucket_submissions) &&
            $get_first_time_help_configuration == 'yes'
        ) {
            $migration_alert = "Move already submitted form details from Databucket to $get_configured_helpdesk by configuring mapping and enabling Migration Settings in Settings Tab";
            update_option("WPULB_FIRST_TIME_HELP_CONFIGURATION", 'no');
        }

        echo wp_json_encode([
            'response' => [
                'forms' => $all_active_forms,
                'total_page' => $totalPage,
                'is_thirdparty_form' => $is_thirdparty_form,
            ],
            'message' => $message,
            'migration_alert' => $migration_alert,
            'status' => 200,
            'success' => true,
        ]);
        wp_die();
    }

    public static function defaultFormSettings()
    {
        $form_type = isset($_POST['form_type'])
            ? sanitize_text_field($_POST['form_type'])
            : '';
        $error_message = isset($_POST['error_message'])
            ? sanitize_text_field($_POST['error_message'])
            : '';
        $success_message = isset($_POST['success_message'])
            ? sanitize_text_field($_POST['success_message'])
            : '';
        $enable_url_redirection = isset($_POST['redirection'])
            ? sanitize_text_field($_POST['redirection'])
            : '';
        $google_captcha = isset($_POST['captcha'])
            ? sanitize_text_field($_POST['captcha'])
            : '';
        $third_party_form = isset($_POST['captcha'])
            ? sanitize_text_field($_POST['form'])
            : '';
        $third_title = isset($_POST['title'])
            ? sanitize_text_field($_POST['title'])
            : '';
        $shortcode = isset($_POST['shortcode'])
            ? sanitize_text_field($_POST['shortcode'])
            : '';

        $shortcode = self::$mapping_instance->retrieve_shortcode($shortcode);
        $form_settings = [];

        if ($form_type == 'undefined') {
            $form_type = '';
        }
        if ($error_message == 'undefined') {
            $error_message = '';
        }
        if ($success_message == 'undefined') {
            $success_message = '';
        }
        if ($google_captcha == 'undefined') {
            $google_captcha = '';
        }
        if ($third_title == 'undefined') {
            $third_title = '';
        }

        $form_settings['form_type'] = $form_type;
        $form_settings['error_message'] = $error_message;
        $form_settings['success_message'] = $success_message;
        $form_settings['redirection'] = $enable_url_redirection;
        $form_settings['captcha'] = $google_captcha;
        $form_settings['form'] = $third_party_form;
        $form_settings['title'] = $third_title;

        update_option(
            "WPULB_DEFAULT_FORM_SETTINGS_{$shortcode}",
            $form_settings
        );

        $native_form_info = get_option("WPULB_INFO_DEFAULT_FORM_{$shortcode}");
        $info = $native_form_info['before_sorting'];

        echo wp_json_encode([
            'response' => ['forms' => $info],
            'message' => 'Selected fields',
            'status' => 200,
            'success' => true,
        ]);
        wp_die();
    }

    public static function manageThirdPartyForms(
        $form_type,
        $form_title,
        $shortcode,
        $enabled_fields,
        $mandatory_fields
    ) {
        if ($form_type == 'NINJA_FORM') {
            global $ninja_addon_instance;
            $new_id = $ninja_addon_instance->manage_converted_ninja_forms(
                $form_type,
                $form_title,
                $shortcode,
                $enabled_fields,
                $mandatory_fields
            );
        } elseif ($form_type == 'CONTACT_FORM') {
            if (
                !is_plugin_active(
                    'contact-form-pro-plus/contact-form-pro-plus.php'
                )
            ) {
                $contact_form_instance = ContactFormsSupport::getInstance();
                $new_id = $contact_form_instance->manage_converted_contact_forms(
                    $form_type,
                    $form_title,
                    $shortcode,
                    $enabled_fields,
                    $mandatory_fields
                );
            } else {
                global $contact_addon_instance;
                $new_id = $contact_addon_instance->manage_converted_contact_forms(
                    $form_type,
                    $form_title,
                    $shortcode,
                    $enabled_fields,
                    $mandatory_fields
                );
            }
        } elseif ($form_type == 'GRAVITY_FORM') {
            global $gravity_addon_instance;
            $new_id = $gravity_addon_instance->manage_converted_gravity_forms(
                $form_type,
                $form_title,
                $shortcode,
                $enabled_fields,
                $mandatory_fields
            );
        }
        return $new_id;
    }

    public static function editForm()
    {
        global $supported_thirdparty_forms;
        global $duplicate_handling_array;
        $form_type = sanitize_text_field($_POST['form_type']);
        $shortcode = sanitize_text_field($_POST['shortcode']);
        $crm_type = sanitize_text_field($_POST['crm_type']);

        $crm_array = [
            'vtigercrm',
            'sugarcrm',
            'salesforcecrm',
            'joforcecrm',
            'freshsalescrm',
            'zohocrm',
            'suitecrm',
        ];
        $help_array = ['vtigersupport', 'zendesk', 'freshdesk', 'zohosupport'];

        if ($crm_type == 'wpulb_crm') {
            $addon = 'DATA_BUCKET_ONLY';
        } elseif (in_array($crm_type, $crm_array)) {
            $addon = 'CRM';
        } elseif (in_array($crm_type, $help_array)) {
            $addon = 'HELPDESK';
        }

        $module = '';
        $lead_owner = '';
        $duplicate_handle = '';
        $choosed_form = [];
        $forms = [];
        $default_with_crm = false;
        $third_form_with_data = false;
        $third_form_with_crm = false;

        if ($form_type == 'DEFAULT_FORM') {
            preg_match_all("/\\[(.*?)\\]/", $shortcode, $matches);
            $shortcode = $matches[1][0];
            $shortcode = substr($shortcode, strpos($shortcode, "=") + 1);

            $fields_by_grouping = ManageFields::fetchAllFields(
                'show-fields-for-mapping'
            );

            $native_form_info = get_option(
                "WPULB_INFO_DEFAULT_FORM_{$shortcode}"
            );
            $form_enabled_fields = $native_form_info['enabled_fields'];
            $form_mandatory_fields = $native_form_info['mandatory_fields'];

            foreach (
                $fields_by_grouping
                as $fields_group_key => $fields_group_value
            ) {
                foreach (
                    $fields_group_value['field_group']
                    as $field_key => $field_value
                ) {
                    if (
                        in_array(
                            $field_value['field_name'],
                            $form_enabled_fields
                        )
                    ) {
                        $fields_by_grouping[$fields_group_key]['field_group'][
                            $field_key
                        ]['is_enabled'] = true;
                    } else {
                        $fields_by_grouping[$fields_group_key]['field_group'][
                            $field_key
                        ]['is_enabled'] = false;
                    }

                    if (
                        in_array(
                            $field_value['field_name'],
                            $form_mandatory_fields
                        )
                    ) {
                        $fields_by_grouping[$fields_group_key]['field_group'][
                            $field_key
                        ]['mandatory'] = true;
                    } else {
                        $fields_by_grouping[$fields_group_key]['field_group'][
                            $field_key
                        ]['mandatory'] = false;
                    }
                }
            }

            $forms['label'] = 'Default Form';
            $forms['value'] = $form_type;

            if ($addon == 'DATA_BUCKET_ONLY') {
                echo wp_json_encode([
                    'response' => [
                        'formType' => $forms,
                        'syncType' => $addon,
                        'fields_by_grouping' => $fields_by_grouping,
                        'crm_details' => [],
                    ],
                    'message' => 'Default Form Details with Databucket',
                    'status' => 200,
                    'success' => true,
                ]);
            } elseif ($addon == 'CRM' || $addon == 'HELPDESK') {
                $default_with_crm = true;
                $module = $native_form_info['module'];
                $lead_owner = $native_form_info['owner'];
                $duplicate_handle = $native_form_info['duplicate_handle'];
            }
        } else {
            $form_id = sanitize_text_field($_POST['form_id']);
            $other_form_info = get_option("WPULB_INFO_{$form_type}_{$form_id}");

            if ($addon == 'DATA_BUCKET_ONLY') {
                $third_form_with_data = true;
            } elseif ($addon == 'CRM' || $addon == 'HELPDESK') {
                $third_form_with_crm = true;
                $module = $other_form_info['module'];
                $lead_owner = $other_form_info['owner'];
                $duplicate_handle = $other_form_info['duplicate_handle'];
            }

            $choosed_form['label'] = $shortcode;
            $choosed_form['value'] = $form_id;

            foreach ($supported_thirdparty_forms as $supported_forms) {
                if ($supported_forms['plugin_wpulb_uniquename'] == $form_type) {
                    $forms['label'] = $supported_forms['plugin_label'];
                    $forms['value'] =
                        $supported_forms['plugin_wpulb_uniquename'];
                }
            }
        }

        $sync_module = [];
        if (!empty($module)) {
            $sync_module['label'] = $module;
            $sync_module['value'] = $module;
        }

        $duplicate = [];
        if (!empty($duplicate_handle)) {
            if (
                array_key_exists($duplicate_handle, $duplicate_handling_array)
            ) {
                $duplicate['label'] =
                    $duplicate_handling_array[$duplicate_handle];
                $duplicate['value'] = $duplicate_handle;
            }
        }

        $user_owner = [];
        if (!empty($lead_owner)) {
            if ($addon == 'CRM') {
                $get_users = get_option('WPULB_USERS_CONNECTED');
            } elseif ($addon == 'HELPDESK') {
                $get_users = get_option('WPULB_HELP_USERS_CONNECTED');
            }
            foreach ($get_users as $users) {
                if ($users['user_id'] == $lead_owner) {
                    $user_owner['label'] = $users['user_name'];
                    $user_owner['value'] = $users['user_id'];
                }
            }
            if ($lead_owner == 'RoundRobin') {
                $user_owner['label'] = 'Round Robin';
                $user_owner['value'] = 'RoundRobin';
            }
        }

        if ($default_with_crm) {
            echo wp_json_encode([
                'response' => [
                    'formType' => $forms,
                    'syncType' => $addon,
                    'fields_by_grouping' => $fields_by_grouping,
                    'crm_details' => [
                        'duplicateOption' => $duplicate,
                        'module' => $sync_module,
                        'recordOwner' => $user_owner,
                    ],
                ],
                'message' => 'Default Form Details with CRM',
                'status' => 200,
                'success' => true,
            ]);
        }

        if ($third_form_with_data) {
            echo wp_json_encode([
                'response' => [
                    'formType' => $forms,
                    'syncType' => $addon,
                    'crm_details' => ['activeForm' => $choosed_form],
                ],
                'message' => 'Third party Form Details with Databucket',
                'status' => 200,
                'success' => true,
            ]);
        }

        if ($third_form_with_crm) {
            echo wp_json_encode([
                'response' => [
                    'formType' => $forms,
                    'syncType' => $addon,
                    'crm_details' => [
                        'activeForm' => $choosed_form,
                        'duplicateOption' => $duplicate,
                        'module' => $sync_module,
                        'recordOwner' => $user_owner,
                    ],
                ],
                'message' => 'Third party Form Details with CRM',
                'status' => 200,
                'success' => true,
            ]);
        }
        wp_die();
    }

    public static function deleteForm()
    {
        global $wpdb;
        $form_type = sanitize_text_field($_POST['form_type']);
        $shortcode = sanitize_text_field($_POST['shortcode']);

        $shortcode = self::$mapping_instance->retrieve_shortcode($shortcode);

        if ($form_type == 'DEFAULT_FORM') {
           
            $delete_id = $wpdb->get_var($wpdb->prepare("SELECT shortcode_id FROM {$wpdb->prefix}smack_ulb_shortcode_manager WHERE shortcode_name = '%s", $shortcode));
            $wpdb->delete($wpdb->prefix . 'smack_ulb_shortcode_manager',array('shortcode_id' => $delete_id));

            delete_option("WPULB_CRM_MAPPING_{$form_type}_{$shortcode}");
            delete_option("WPULB_DATA_MAPPING_{$form_type}_{$shortcode}");
            delete_option("WPULB_INFO_{$form_type}_{$shortcode}");
            delete_option("WPULB_DEFAULT_FORM_SETTINGS_{$shortcode}");
        } else {
            $form_id = intval($_POST['form_id']);

            $delete_id = $wpdb->get_var($wpdb->prepare("SELECT shortcode_id FROM {$wpdb->prefix}smack_ulb_shortcode_manager WHERE shortcode_name = %s AND form_id = %s", $shortcode, $form_id));
            $wpdb->delete($wpdb->prefix . 'smack_ulb_shortcode_manager',array('shortcode_id' => $delete_id));

            delete_option("WPULB_CRM_MAPPING_{$form_type}_{$form_id}");
            delete_option("WPULB_DATA_MAPPING_{$form_type}_{$form_id}");
            delete_option("WPULB_INFO_{$form_type}_{$form_id}");
        }
        echo wp_json_encode([
            'response' => '',
            'message' => 'Form Deleted Successfully',
            'status' => 200,
            'success' => true,
        ]);
        wp_die();
    }

    public static function store_default_form_info_on_creation($default_key, $default_value) {
        global $wpdb;
        $get_default_forms_post = $wpdb->get_results($wpdb->prepare( "SELECT * FROM {$wpdb->prefix}smack_ulb_shortcode_manager WHERE form_type = %s AND old_shortcode_name = %s", 'DEFAULT_FORM', $default_value));

        if (empty($get_default_forms_post)) {
            $shortcode_name = ManageForms::generateShortcode();
            $wpdb->insert(
                "{$wpdb->prefix}smack_ulb_shortcode_manager",
                [
                    "shortcode_name" => $shortcode_name,
                    "old_shortcode_name" => $default_value,
                    "form_type" => 'DEFAULT_FORM',
                    'crm_type' => 'wpulb_crm',
                    'status' => 'Created',
                ],
                ['%s', '%s', '%s', '%s']
            );

            $post_form = [];
            $post_form['form_type'] = $default_key;
            $post_form['form'] = '';
            update_option(
                "WPULB_DEFAULT_FORM_SETTINGS_{$shortcode_name}",
                $post_form
            );

            /* Code to initially store databucket fields on two default form creations */
            $info = [];
            $native_form_info = [];
            $enabled_fields = [];
            $fields = ManageFields::fetchAllFields('field-name-and-label');

            foreach ($fields as $field_key => $field) {
                $field_type = $wpdb->get_var($wpdb->prepare("SELECT field_type FROM {$wpdb->prefix}smack_ulb_manage_fields WHERE field_name = %s", $field_key));
                $forms['label'] = $field;
                $forms['value'] = $field_key;
                $forms['type'] = $field_type;
                $forms['mandatory'] = false;
                array_push($info, $forms);

                $enabled_fields[] = $field_key;
            }

            $native_form_info['enabled_fields'] = $enabled_fields;
            $native_form_info['mandatory_fields'] = [];
            $native_form_info['before_sorting'] = $info;
            $native_form_info['configured_addon'] = 'DATA_BUCKET_ONLY';
            $native_form_info['connected_crm'] = 'wpulb_crm';
            $native_form_info['connected_help'] = 'wpulb_crm';
            update_option(
                "WPULB_INFO_DEFAULT_FORM_{$shortcode_name}",
                $native_form_info
            );
        }
    }
}

global $leads_manage_forms_instance;
$leads_manage_forms_instance = new ManageForms();

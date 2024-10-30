<?php
/**
* CRM Connector Plus plugin file. 
*
* Copyright (C) 2010-2020, Smackcoders Inc - info@smackcoders.com 
*/

namespace Smackcoders\WPULB;

if ( ! defined( 'ABSPATH' ) )
{
        exit; // Exit if accessed directly
}

class LangEN
{  
        private static $english_instance = null;

        /**
         * getInstance
         *
         * @return void
         */
        public static function getInstance() 
        {
                if (LangEN::$english_instance == null) {
                        LangEN::$english_instance = new LangEN;
                        return LangEN::$english_instance;
                }
                return LangEN::$english_instance;
        }

        /**
         * contents
         *
         * @return void
         */
        public static function contents()
        {
                $response = array(
                        'Submit' => 'Soumettre',
                        'FormManagement' => 'Form Management',
                        'UserSync' => 'User Sync',
                        'UserSyncMethod' => 'User Sync Method',
                        'WoocommerceSync' => 'Woocommerce Sync',
                        'WoocommerceSyncMethod' => 'Woocommerce Sync Method',
                        'CRMConfiguration' => 'CRM Configuration',
                        'HelpdeskConfiguration' => 'Helpdesk Configuration',
                        'Settings' => 'Settings',
                        'Marketplace' => 'Marketplace',
                        'License' => 'License',
                        'AddFormforSync' => 'Add Form for Sync',
                        'SHORTCODETITLE' => 'SHORT CODE/TITLE',
                        'MAPPING' => 'MAPPING',
                        'ACTIONS' => 'ACTIONS',
                        'DataBucketFieldssearchinForms' => 'Data Bucket Fields search in Forms',
                        'DataBucketFields' => 'Data Bucket Fields',
                        'Conditions' => 'Conditions',
                        'FieldName' => 'Field Name',
                        'Fields' => 'Fields',
                        'Reset' => 'Reset',
                        'FORMNAME' => 'FORM NAME',
                        'FORMTYPE' => 'FORM TYPE',
                        'SUBMITTEDFORMSCOUNT' => 'SUBMITTED FORMS COUNT',
                        'FIRSTNAME' => 'FIRST NAME',
                        'LASTNAME' => 'LAST NAME',
                        'EMAIL' => 'EMAIL',
                        'SUBMITTED_DATE' => 'SUBMITTED_DATE',
                        'SYNC_STATUS' => 'SYNC_STATUS',
                        'createnew' => 'create new',
                        'Lists' => 'Lists',
                        'Lists' => 'Lists',
                        'Back' => 'Back',
                        'Heading' => 'Heading',
                        'SelectPluginCustomFields' => 'Select Plugin-Custom Fields',
                        'Optional' => 'Optional',
                        'ChooseoptiontoSync' => 'Choose option to Sync',
                        'ConfigureMapping' => 'Configure Mapping',
                        'CRM' => 'CRM',
                        'Helpdesk' => 'Helpdesk',
                        'DataBucket' => 'Data Bucket',
                        'DatabucketOnly' => 'Databucket Only',
                        'WPUserAutoSync' => 'WP User Auto Sync',
                        'OneTimeManualSync' => 'One Time Manual Sync',
                        'ChooseWoocommerceProductsorOrderstobeSync' => 'Choose Woocommerce Products or Orders to be Sync',
                        'SyncWoocommerceProductsOrdersas' => 'Sync Woocommerce Products Orders as',
                        'SyncWoocommerceCustomeras' => 'Sync Woocommerce Customer as',
                        'WPWoocommerceAutoSync' => 'WP Woocommerce Auto Sync',
                        'Choosefromthelist' => 'Choose CRM from the list',
                        'ChooseHelpdeskfromthelist' => 'Choose Helpdesk from the list',
                        'ZohoCRMConfiguration' => 'Zoho CRM Configuration',
                        'ClientId' => 'Client Id',
                        'ClientSecret' => 'Client Secret',
                        'Callback' => 'Callback',
                        'AvailableDomains' => 'Available Domains',
                        'ResetConfiguration' => 'Reset Configuration',
                        'Activate' => 'Activate',
                        'Configure' => 'Configure',
                        'EnteryourHelpdeskUrl' => 'Enter your Helpdesk Url',
                        'EnteryourUsername' => 'Enter your Username',
                        'EnterAccessKey' => 'Enter Access Key',
                        'DataBucketssettings' => 'Data Buckets settings',
                        'GroupSettings' => 'Group Settings',
                        'ScheduleSettings' => 'Schedule Settings',
                        'DataBucketMigrationSettings' => 'Data Bucket Migration Settings',
                        'BASICINFORMATION' => 'BASIC INFORMATION',
                        'ClientId' => 'Client Id',
                        'FirstName' => 'First Name',
                        'LastName' => 'Last Name',
                        'Email' => 'Email',
                        'Street' => 'Street',
                        'City' => 'City',
                        'State' => 'State',
                        'Country' => 'Country',
                        'Phone' => 'Phone',
                        'EmailAddress' => 'Email Address',
                        'Zipcode' => 'Zipcode',
                        'PlaceOrder' => 'Place Order',
                        'DefaultFormLogandCaptchaSettings' => 'Default Form Log and Captcha Settings',
                        'WhichLogDoYouNeed' => 'Which Log Do You Need ?',
                        'None' => 'None',
                        'Success' => 'Success',
                        'Failure' => 'Failure',
                        'Both' => 'Both',
                        'SpecifyEmail' => 'Specify Email',
                        'DoYouWanttoEnabletheCaptcha' => 'Do You Want to Enable the Captcha',
                        'GoogleRecaptchaPublicKey' => 'Google Recaptcha Public Key',
                        'GoogleRecaptchaPrivateKey' => 'Google Recaptcha Private Key',
                        'Save' => 'Save',
                        'GROUPNAME' => 'GROUP NAME',
                        'ACTION' => 'ACTION',
                        'AddGroup' => 'Add Group',
                        'EnterGroupName' => 'Enter Group Name',
                        'BasicInformation' => 'Basic Information',
                        'Schedule' => 'Schedule',
                        'EnableSchedule' => 'Enable Schedule',
                        'ScheduleTime' => 'Schedule Time',
                        'Migration' => 'Migration',
                        'Doyouwanttomigratedatabucket' => 'Do you want to migrate data bucket',
                        'DataBucketFormsList' => 'Data Bucket Forms List',
                        'SyncDataToCRM' => 'Sync Data to CRM',
                        'ThankyouforyourPurchase' => 'Thank you for your Purchase!',
                        'Togetstartedyouneedtodownloadandactivatebyenteringthelicensekey' => 'To get started, you need to download and activate by entering the license key',
                        'EntertheLicenseKey' => 'Enter the License Key',
                        'BuyNow' => 'Buy Now',
                        'AddToCart' => 'Add To Cart',
                        'All' => 'All',
                        'crm' => 'crm',
                        'helpdesk' => 'helpdesk',
                        'forms' => 'forms',
                        'others' => 'others',
                        'Checkout' => 'Checkout',
                        'GoToCheckout' => 'Go To Checkout'
                        
		);
                return $response;
        }
}
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

class LangGE
{
        
        private static $german_instance = null;

        /**
         * getInstance
         *
         * @return void
         */
        public static function getInstance() 
        {
            if (LangGE::$german_instance == null) {
                LangGE::$german_instance = new LangGE;
                return LangGE::$german_instance;
            }
            return LangGE::$german_instance;
        }

        /**
         * contents
         *
         * @return void
         */
        public static function contents()
        {
                $response = array(
                        'Submit' => 'einreichen',
                        'FormManagement' => 'Formularverwaltung',
                        'UserSync' => 'Benutzersynchronisierung',
                        'UserSyncMethod' => 'Benutzersynchronisierungsmethode',
                        'WoocommerceSync' => 'Woocommerce Sync',
                        'WoocommerceSyncMethod' => 'Woocommerce Synchronisierungsmethode',
                        'CRMConfiguration' => 'CRM-Konfiguration',
                        'HelpdeskConfiguration' => 'Helpdesk-Konfiguration',
                        'Settings' => 'die Einstellungen',
                        'Marketplace' => 'Marktplatz',
                        'License' => 'Lizenz',
                        'AddFormforSync' => 'Formular für die Synchronisierung hinzufügen',
                        'ChooseFormType' => 'Wählen Sie Formulartyp',
                        'ChooseAnyOneOftheForm' => 'Wählen Sie eines der Formulare aus',
                        'ShowMapping' => 'Mapping anzeigen',
                        'DATABUCKETFIELD' => 'DATEN EIMERFELD',
                        'FORMFIELD' => 'FORMULARFELD',
                        'SaveForm' => 'Formular speichern',
                        'ChoosetheModulewithLeadOwner' => 'Wählen Sie das Modul mit Lead Owner',
                        'ChooseYourModule' => 'Wählen Sie Ihr Modul',
                        'DuplicateHandling' => 'Doppelte Handhabung',
                        'AssigntoLeadOwner' => 'Dem Hauptbesitzer zuweisen',
                        'ADDONFIELD' => 'ADDON FELD',
                        'SelecttheFormFields' => 'Wählen Sie die Formularfelder aus',
                        'MandatoryFields' => 'Pflichtfelder',
                        'FormSettings' => 'Formulareinstellungen',
                        'FormType' => 'Formulartyp',
                        'ErrorMessageSubmission' => 'Übermittlung von Fehlermeldungen',
                        'SuccessMessageSubmission' => 'Übermittlung einer Erfolgsnachricht',
                        'EnableURLRedirection' => 'URL-Umleitung aktivieren',
                        'EnterRedirectURL' => 'Geben Sie die Weiterleitungs-URL ein',
                        'EnableGoogleCaptcha' => 'Aktivieren Sie Google Captcha',
                        'ChooseThirdPartyForm' => 'Wählen Sie das Formular eines Drittanbieters',
                        'ThirdPartyFormTitle' => 'Formulartitel eines Drittanbieters',
                        'Continue' => 'Fortsetzen',
                        'FormFields' => 'Formularfelder',
                        'UpdateForm' => 'Formular aktualisieren',
                        'Form Updated Successfully' => 'Formular erfolgreich aktualisiert',
                        'SHORTCODETITLE' => 'KURZCODE / TITEL',
                        'FORMTYPE' => 'FORMULART',
                        'MAPPING' => 'KARTIERUNG',
                        'ACTIONS' => 'AKTIONEN',
                        'DataBucketFieldssearchinForms' => 'Daten-Bucket-Feldsuche in Formularen',
                        'DataBucketFields' => 'Daten-Bucket-Felder',
                        'Conditions' => 'Bedingungen',
                        'FieldName' => 'Feldname',
                        'Fields' => 'Felder',
                        'Reset' => 'Zurücksetzen',
                        'FORMNAME' => 'FORMULARNAME',
                        'SUBMITTEDFORMSCOUNT' => 'ZUR EINREICHUNG VON FORMEN',
                        'FIRSTNAME' => 'VORNAME',
                        'LASTNAME' => 'NACHNAME',
                        'EMAIL' => 'EMAIL',
                        'SUBMITTED_DATE' => 'Eingereichtes Datum',
                        'SYNC_STATUS' => 'SYNC_STATUS',
                        'createnew' => 'Erstelle neu',
                        'Lists' => 'Listen',
                        'Back' => 'Zurück',
                        'Heading' => 'Überschrift',
                        'SelectPluginCustomFields' => 'Wählen Sie Plugin-Custom Fields',
                        'Optional' => 'Optional',
                        'ChooseoptiontoSync' => 'Wählen Sie die Option zum Synchronisieren',
                        'ConfigureMapping' => 'Mapping konfigurieren',
                        'CRM' => 'CRM',
                        'Helpdesk' => 'Beratungsstelle',
                        'DataBucket' => 'Daten-Bucket',
                        'DatabucketOnly' => 'Nur Daten-Bucket',
                        'WPUserAutoSync' => 'Automatische Synchronisierung des WP-Benutzers',
                        'OneTimeManualSync' => 'Einmalige manuelle Synchronisierung',
                        'ChooseWoocommerceProductsorOrderstobeSync' => 'Wählen Sie Woocommerce-Produkte oder Bestellungen, die synchronisiert werden sollen',
                        'SyncWoocommerceProductsOrdersas' => 'Woocommerce-Produkte bestellen Bestellungen als',
                        'WPWoocommerceAutoSync' => 'WP Woocommerce Auto Sync',
                        'Choosefromthelist' => 'Wähle CRM aus der Liste',
                        'ChooseHelpdeskfromthelist' => 'Wählen Sie Helpdesk aus der Liste',
                        'ZohoCRMConfiguration' => 'Zoho CRM-Konfiguration',
                        'ClientId' => 'Kunden ID',
                        'ClientSecret' => 'Kundengeheimnis',
                        'Callback' => 'Zurückrufen',
                        'AvailableDomains' => 'Verfügbare Domains',
                        'ResetConfiguration' => 'Konfiguration zurücksetzen',
                        'Activate' => 'aktivieren Sie',
                        'Configure' => 'Konfigurieren',
                        'EnteryourHelpdeskUrl' => 'Geben Sie Ihre Helpdesk-URL ein',
                        'EnteryourUsername' => 'Geben Sie Ihren Benutzernamen ein',
                        'EnterAccessKey' => 'Geben Sie den Zugangsschlüssel ein',
                        'DataBucketssettings' => 'Daten-Bucket-Einstellungen',
                        'GroupSettings' => 'Gruppeneinstellungen',
                        'ScheduleSettings' => 'Zeitplaneinstellungen',
                        'MigrationSettings' => 'Daten Bucket-Migrationseinstellungen',
                        'BASICINFORMATION' => 'GRUNDINFORMATION',
                        'FirstName' => 'Vorname',
                        'LastName' => 'Nachname',
                        'Email' => 'Email',
                        'Street' => 'Straße',
                        'City' => 'Stadt',
                        'State' => 'Zustand',
                        'Country' => 'Land',
                        'Zipcode' => 'Postleitzahl',
                        'DefaultFormLogandCaptchaSettings' => 'Standardeinstellungen für Formularprotokoll und Captcha',
                        'WhichLogDoYouNeed' => 'Welches Protokoll benötigen Sie?',
                        'None' => 'Keiner',
                        'Success' => 'Erfolg',
                        'Failure' => 'Fehler',
                        'Both' => 'Beide',
                        'SpecifyEmail' => 'Geben Sie E-Mail an',
                        'DoYouWanttoEnabletheCaptcha' => 'Möchten Sie das Captcha aktivieren?',
                        'GoogleRecaptchaPublicKey' => 'Öffentlicher Schlüssel von Google Recaptcha',
                        'GoogleRecaptchaPrivateKey' => 'Privater Google Recaptcha-Schlüssel',
                        'Save' => 'sparen',
                        'GROUPNAME' => 'GRUPPENNAME',
                        'ACTION' => 'AKTION',
                        'AddGroup' => 'Gruppe hinzufügen',
                        'EnterGroupName' => 'Geben Sie den Gruppennamen ein',
                        'BasicInformation' => 'Grundinformation',
                        'Schedule' => 'Zeitplan',
                        'EnableSchedule' => 'Zeitplan aktivieren',
                        'ScheduleTime' => 'Planmäßige Zeit',
                        'Migration' => 'Migration',
                        'Doyouwanttomigratedatabucket' => 'Möchten Sie den Daten-Bucket migrieren?',
                        'DataBucketFormsList' => 'Liste der Daten Bucket-Formulare',
                        'FORMNAME' => 'FORMULARNAME',
                        'SyncDataToCRM' => 'Daten mit CRM synchronisieren',
                        'ThankyouforyourPurchase!' => 'Danke für Ihren Einkauf!',
                        'Togetstartedyouneedtodownloadandactivatebyenteringthelicensekey' => 'Um zu beginnen, müssen Sie herunterladen und aktivieren, indem Sie den Lizenzschlüssel eingeben',
                        'EntertheLicenseKey' => 'Geben Sie den Lizenzschlüssel ein',
                        'BuyNow' => 'Kaufe jetzt',
                        
		);
                return $response;
        }
}
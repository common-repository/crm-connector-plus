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

class LangIT
{
        
        private static $italian_instance = null;

        /**
         * getInstance
         *
         * @return void
         */
        public static function getInstance() 
        {
            if (LangIT::$italian_instance == null) {
                LangIT::$italian_instance = new LangIT;
                return LangIT::$italian_instance;
            }
            return LangIT::$italian_instance;
        }

        /**
         * contents
         *
         * @return void
         */
        public static function contents()
        {
                $response = array(
                        'Submit' => 'Invia',
                        'FormManagement' => 'Gestione dei moduli',
                        'UserSync' => 'Sincronizzazione utente',
                        'UserSyncMethod' => 'Metodo di sincronizzazione utente',
                        'WoocommerceSync' => 'Sincronizzazione Woocommerce',
                        'WoocommerceSyncMethod' => 'Metodo di sincronizzazione di Woocommerce',
                        'CRMConfiguration' => 'Configurazione CRM',
                        'HelpdeskConfiguration' => 'Configurazione dellhelpdesk',
                        'Settings' => 'impostazioni',
                        'Marketplace' => 'Mercato',
                        'License' => 'Licenza',
                        'AddFormforSync' => 'Aggiungi modulo per sincronizzazione',
                        'ChooseFormType' => 'Scegli il tipo di modulo',
                        'ChooseAnyOneOftheForm' => 'Scegli uno qualsiasi del modulo',
                        'ShowMapping' => 'Mostra mappatura',
                        'DATABUCKETFIELD' => 'CAMPO DI SECCHIO DI DATI',
                        'FORMFIELD' => 'FORM CAMPO',
                        'SaveForm' => 'Salva modulo',
                        'ChoosetheModulewithLeadOwner' => 'Scegli il modulo con il proprietario principale',
                        'ChooseYourModule' => 'Scegli il tuo modulo',
                        'DuplicateHandling' => 'Gestione duplicata',
                        'AssigntoLeadOwner' => 'Assegna a Lead Owner',
                        'ADDONFIELD' => 'CAMPO ADDON',
                        'SelecttheFormFields' => 'Seleziona i campi modulo',
                        'MandatoryFields' => 'Campi obbligatori',
                        'FormSettings' => 'Impostazioni modulo',
                        'FormType' => 'Tipo di modulo',
                        'ErrorMessageSubmission' => 'Invio messaggio di errore',
                        'SuccessMessageSubmission' => 'Invio messaggio di successo',
                        'EnableURLRedirection' => 'Abilita reindirizzamento URL',
                        'EnterRedirectURL' => 'Inserisci l URL di reindirizzamento',
                        'EnableGoogleCaptcha' => 'Abilita Google Captcha',
                        'ChooseThirdPartyForm' => 'Scegli il modulo di terze parti',
                        'ThirdPartyFormTitle' => 'Titolo del modulo di terze parti',
                        'Continue' => 'Continua',
                        'FormFields' => 'Campi modulo',
                        'UpdateForm' => 'Modulo di aggiornamento',
                        'Form Updated Successfully' => 'Modulo aggiornato con successo',
                        'SHORTCODETITLE' => 'CODICE / TITOLO CORTO',
                        'FORMTYPE' => 'TIPO DI FORMA',
                        'MAPPING' => 'MAPPATURA',
                        'ACTIONS' => 'AZIONI',
                        'DataBucketFieldssearchinForms' => 'Ricerca campo bucket dati nei moduli',
                        'DataBucketFields' => 'Campi bucket dati',
                        'Conditions' => 'condizioni',
                        'FieldName' => 'Nome campo',
                        'Fields' => 'campi',
                        'Reset' => 'Ripristina',
                        'FORMNAME' => 'NOME FORM',
                        'SUBMITTEDFORMSCOUNT' => 'CONTE DELLE FORME PRESENTATE',
                        'FIRSTNAME' => 'NOME DI BATTESIMO',
                        'LASTNAME' => 'COGNOME',
                        'EMAIL' => 'E-MAIL',
                        'SUBMITTED_DATE' => 'DATA INVIATA',
                        'SYNC_STATUS' => 'STATO DI SINCRONIZZAZIONE',
                        'createnew' => 'creare nuovo',
                        'Lists' => 'elenchi',
                        'Back' => 'Indietro',
                        'Heading' => 'Intestazione',
                        'SelectPlugin-CustomFields' => 'Seleziona Campi personalizzati plug-in',
                        'Optional' => 'Opzionale',
                        'ChooseoptiontoSync' => 'Scegli lopzione Sincronizza',
                        'ConfigureMapping' => 'Configura mappatura',
                        'CRM' => 'CRM',
                        'Helpdesk' => 'Helpdesk',
                        'DataBucket' => 'Data Bucket',
                        'DatabucketOnly' => 'Solo bucket dati',
                        'WPUserAutoSync' => 'Sincronizzazione automatica utente WP',
                        'OneTimeManualSync' => 'Sincronizzazione manuale una tantum',
                        'ChooseWoocommerceProductsorOrderstobeSync' => 'Scegli i prodotti Woocommerce o gli ordini da sincronizzare',
                        'SyncWoocommerceProductsOrdersas' => 'Sincronizza gli ordini dei prodotti di Woocommerce come',
                        'WPWoocommerceAutoSync' => 'Sincronizzazione automatica di WP Woocommerce',
                        'Choosefromthelist' => 'Scegli CRM dalla lista',
                        'ChooseHelpdeskfromthelist' => "Scegli Helpdesk dall'elenco",
                        'ZohoCRMConfiguration' => 'Configurazione Zoho CRM',
                        'ClientId' => 'Identificativo cliente',
                        'ClientSecret' => 'Segreto del cliente',
                        'Callback' => 'Richiama',
                        'AvailableDomains' => 'Domini disponibili',
                        'ResetConfiguration' => 'Ripristina configurazione',
                        'Activate' => 'Attivare',
                        'Configure' => 'Configurazione',
                        'EnteryourHelpdeskUrl' => 'Inserisci il tuo URL dell helpdesk',
                        'EnteryourUsername' => 'Inserisci il tuo nome utente',
                        'EnterAccessKey' => 'Immettere la chiave di accesso',
                        'DataBucketssettings' => 'Impostazioni dei bucket di dati',
                        'GroupSettings' => 'Impostazioni di gruppo',
                        'ScheduleSettings' => 'Impostazioni programma',
                        'DataBucketMigrationSettings' => 'Impostazioni di migrazione del bucket dati',
                        'BASICINFORMATION' => 'INFORMAZIONI DI BASE',
                        'FirstName' => 'Nome di battesimo',
                        'LastName' => 'Cognome',
                        'Email' => 'E-mail',
                        'Street' => 'strada',
                        'City' => 'Città',
                        'State' => 'Stato',
                        'Country' => 'Nazione',
                        'Zipcode' => 'Cap',
                        'DefaultFormLogandCaptchaSettings' => 'Registro modulo predefinito e impostazioni captcha',
                        'WhichLogDoYouNeed' => 'Di quale registro hai bisogno?',
                        'None' => 'Nessuna',
                        'Success' => 'Successo',
                        'Failure' => 'Fallimento',
                        'Both' => 'Tutti e due',
                        'SpecifyEmail' => 'Specifica l e-mail',
                        'DoYouWanttoEnabletheCaptcha' => 'Vuoi abilitare il captcha',
                        'GoogleRecaptchaPublicKey' => 'Chiave pubblica di Google Recaptcha',
                        'GoogleRecaptchaPrivateKey' => 'Chiave privata di Google Recaptcha',
                        'Save' => 'Salva',
                        'GROUPNAME' => 'NOME DEL GRUPPO',
                        'ACTION' => 'AZIONE',
                        'AddGroup' => 'Aggiungere gruppo',
                        'EnterGroupName' => 'Inserisci il nome del gruppo',
                        'BasicInformation' => 'Informazioni di base',
                        'Schedule' => 'Programma',
                        'EnableSchedule' => 'Abilita pianificazione',
                        'ScheduleTime' => 'Orario',
                        'Migration' => 'Migrazione',
                        'Doyouwanttomigratedatabucket' => 'Vuoi migrare secchio di dati',
                        'DataBucketFormsList' => 'Elenco moduli secchio dati',
                        'FORMNAME' => 'NOME FORM',
                        'SyncDataToCRM' => 'Sincronizza i dati con CRM',
                        'ThankyouforyourPurchase!' => 'Grazie per il vostro acquisto!',
                        'Togetstartedyouneedtodownloadandactivatebyenteringthelicensekey' => 'Per iniziare, devi scaricare e attivare inserendo la chiave di licenza',
                        'EntertheLicenseKey' => 'Immettere la chiave di licenza',
                        'BuyNow' => 'Acquista ora',
                        
		);
                return $response;
        }
}
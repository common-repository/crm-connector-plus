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

class LangFR 
{
        
        private static $france_instance = null;

        /**
         * getInstance
         *
         * @return void
         */
        public static function getInstance() 
        {
                if (LangFR::$france_instance == null) {
                        LangFR::$france_instance = new LangFR;
                        return LangFR::$france_instance;
                }
                return LangFR::$france_instance;
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
                        'FormManagement' => 'Gestion des formulaires',
                        'UserSync' => 'Synchronisation utilisateur',
                        'UserSyncMethod' => 'Méthode de synchronisation utilisateur',
                        'WoocommerceSync' => 'Synchronisation de Woocommerce',
                        'WoocommerceSyncMethod' => 'Méthode de synchronisation de Woocommerce',
                        'CRMConfiguration' => 'Configuration CRM',
                        'HelpdeskConfiguration' => 'Configuration du helpdesk',
                        'Settings' => 'Paramètres',
                        'Marketplace' => 'Marketplace',
                        'License' => 'Licence',
                        'AddFormforSync' => 'Ajouter un formulaire pour la synchronisation',
                        'ChooseFormType' => 'Choisissez le type de formulaire',
                        'ChooseAnyOneOftheForm' => 'Choisissez lun des formulaires',
                        'ShowMapping' => 'Afficher le mappage',
                        'DATABUCKETFIELD' => 'CHAMP DE GODET DE DONNÉES',
                        'FORMFIELD' => 'CHAMP DE FORMULAIRE',
                        'SaveForm' => 'Enregistrer le formulaire',
                        'ChoosetheModulewithLeadOwner' => 'Choisissez le module avec le propriétaire principal',
                        'ChooseYourModule' => 'Choisissez votre module',
                        'DuplicateHandling' => 'Manipulation en double',
                        'AssigntoLeadOwner' => 'Attribuer au propriétaire principal',
                        'ADDONFIELD' => 'ADDON FIELD',
                        'SelecttheFormFields' => 'Sélectionnez les champs du formulaire',
                        'MandatoryFields' => 'Champs obligatoires',
                        'FormSettings' => 'Paramètres du formulaire',
                        'FormType' => 'Type de formulaire',
                        'ErrorMessageSubmission' => 'Soumission des messages de rreur',
                        'SuccessMessageSubmission' => 'Soumission du message de réussite',
                        'EnableURLRedirection' => 'Activer la redirection d URL',
                        'EnterRedirectURL' => 'Entrez l URL de redirection',
                        'EnableGoogleCaptcha' => 'Activer Google Captcha',
                        'ChooseThirdPartyForm' => 'Choisissez le formulaire tiers',
                        'ThirdPartyFormTitle' => 'Titre du formulaire tiers',
                        'Continue' => 'Continuer',
                        'FormFields' => 'Champs de formulaire',
                        'UpdateForm' => 'Formulaire de mise à jour',
                        'Form Updated Successfully' => 'Formulaire mis à jour avec succès',
                        'SHORTCODETITLE' => 'CODE / TITRE COURT',
                        'FORMTYPE' => 'TYPE DE FORMULAIRE',
                        'MAPPING' => 'CARTOGRAPHIE',
                        'ACTIONS' => 'ACTIONS',
                        'DataBucketFieldssearchinForms' => 'Recherche de champs de compartiment de données dans les formulaires',
                        'DataBucketFields' => 'Champs de compartiment de données',
                        'Conditions' => 'Conditions',
                        'FieldName' => 'Nom de domaine',
                        'Fields' => 'Des champs',
                        'Reset' => 'Réinitialiser',
                        'FORMNAME' => 'NOM DE FORME',
                        'SUBMITTEDFORMSCOUNT' => 'COMPTE DES FORMULAIRES SOUMIS',
                        'FIRSTNAME' => 'PRÉNOM',
                        'LASTNAME' => 'NOM DE FAMILLE',
                        'EMAIL' => 'EMAIL',
                        'SUBMITTED_DATE' => 'DATE PROPOSÉE',
                        'SYNC_STATUS' => 'ÉTAT DE SYNC',
                        'createnew' => 'créer un nouveau',
                        'Lists' => 'Listes',
                        'Back' => 'Arrière',
                        'Heading' => 'Titre',
                        'SelectPluginCustomFields' => 'Sélectionner des champs personnalisés pour les plugins',
                        'Optional' => 'Optionnel',
                        'ChooseoptiontoSync' => 'Choisissez une option pour synchroniser',
                        'ConfigureMapping' => 'Configurer le mappage',
                        'CRM' => 'CRM',
                        'Helpdesk' => 'Bureau daide',
                        'DataBucket' => 'Seau de données',
                        'DatabucketOnly' => 'Ensemble de données uniquement',
                        'WPUserAutoSync' => 'Synchronisation automatique de lutilisateur WP',
                        'OneTimeManualSync' => 'Synchronisation manuelle unique',
                        'ChooseWoocommerceProductsorOrderstobeSync' => 'Choisissez les produits ou les commandes Woocommerce à synchroniser',
                        'SyncWoocommerceProductsOrdersas' => 'Synchroniser les commandes de produits Woocommerce en tant que',
                        'WPWoocommerceAutoSync' => 'WP Woocommerce Auto Sync',
                        'Choosefromthelist' => 'Choisissez CRM dans la liste',
                        'ChooseHelpdeskfromthelist' => 'Choisissez Helpdesk dans la liste',
                        'ZohoCRMConfiguration' => 'Configuration de Zoho CRM',
                        'ClientId' => 'Identité du client',
                        'ClientSecret' => 'Secret client',
                        'Callback' => 'Rappeler',
                        'AvailableDomains' => 'Domaines disponibles',
                        'ResetConfiguration' => 'Réinitialiser la configuration',
                        'Activate' => 'Activer',
                        'Configure' => 'Configurer',
                        'EnteryourHelpdeskUrl' => 'Entrez votre URL Helpdesk',
                        'EnteryourUsername' => 'Entrez votre nom dutilisateur',
                        'EnterAccessKey' => 'Entrez la clé daccès',
                        'DataBucketssettings' => 'Paramètres des compartiments de données',
                        'GroupSettings' => 'Paramètres de groupe',
                        'ScheduleSettings' => 'Paramètres de planification',
                        'DataBucketMigrationSettings' => 'Paramètres de migration du compartiment de données',
                        'BASICINFORMATION' => 'INFORMATIONS DE BASE',
                        'FirstName' => 'Prénom',
                        'LastName' => 'Nom de famille',
                        'Email' => 'Email',
                        'Street' => 'rue',
                        'City' => 'Ville',
                        'State' => 'Etat',
                        'Country' => 'Pays',
                        'Zipcode' => 'Code postal',
                        'DefaultFormLogandCaptchaSettings' => 'Paramètres de journal de formulaire et de captcha par défaut',
                        'WhichLogDoYouNeed' => 'De quel journal avez-vous besoin?',
                        'None' => 'Aucun',
                        'Success' => 'Succès',
                        'Failure' => 'Échec',
                        'Both' => 'Tous les deux',
                        'SpecifyEmail' => 'Spécifiez lemail',
                        'DoYouWanttoEnabletheCaptcha' => 'Voulez-vous activer le captcha',
                        'GoogleRecaptchaPublicKey' => 'Clé publique Google Recaptcha',
                        'GoogleRecaptchaPrivateKey' => 'Clé privée Google Recaptcha',
                        'Save' => 'sauver',
                        'GROUPNAME' => 'NOM DE GROUPE',
                        'ACTION' => 'ACTION',
                        'AddGroup' => 'Ajouter un groupe',
                        'EnterGroupName' => 'Entrez le nom du groupe',
                        'BasicInformation' => 'Informations de base',
                        'Schedule' => 'Programme',
                        'EnableSchedule' => 'Activer la planification',
                        'ScheduleTime' => 'Horaire',
                        'Migration' => 'Migration',
                        'Doyouwanttomigratedatabucket' => 'Voulez-vous migrer le compartiment de données',
                        'DataBucketFormsList' => 'Liste des formulaires de regroupement de données',
                        'FORMNAME' => 'NOM DE FORME',
                        'SyncDataToCRM' => 'Synchroniser les données avec CRM',
                        'ThankyouforyourPurchase!' => 'Merci pour votre achat!',
                        'Togetstartedyouneedtodownloadandactivatebyenteringthelicensekey' => 'Pour commencer, vous devez télécharger et activer en entrant la clé de licence',
                        'EntertheLicenseKey' => 'Entrez la clé de licence',
                        'BuyNow' => 'Acheter maintenant',
		);
                return $response;
        }
}
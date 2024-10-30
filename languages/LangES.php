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

class LangES
{
        
        private static $spanish_instance = null;

        /**
         * getInstance
         *
         * @return void
         */
        public static function getInstance() 
        {
                if (LangES::$spanish_instance == null) {
                    LangES::$spanish_instance = new LangES;
                        return LangES::$spanish_instance;
                }
                return LangES::$spanish_instance;
        }

        /**
         * contents
         *
         * @return void
         */
        public static function contents()
        {
                $response = array(
                        'Submit' => 'Enviar',
                        'FormManagement' => 'Gestión de formularios',
                        'UserSync' => 'Sincronización de usuario',
                        'UserSyncMethod' => 'Método de sincronización de usuario',
                        'WoocommerceSync' => 'Sincronización de Woocommerce',
                        'WoocommerceSyncMethod' => 'Método de sincronización de Woocommerce',
                        'CRMConfiguration' => 'Configuración de CRM',
                        'HelpdeskConfiguration' => 'Configuración del servicio de asistencia',
                        'Settings' => 'Configuraciones',
                        'Marketplace' => 'Mercado',
                        'License' => 'Licencia',
                        'AddFormforSync' => 'Agregar formulario para sincronización',
                        'ChooseFormType' => 'Elegir tipo de formulario',
                        'ChooseAnyOneOftheForm' => 'Elija cualquiera de los formularios',
                        'ShowMapping' => 'Mostrar mapeo',
                        'DATABUCKETFIELD' => 'CAMPO DE CUBO DE DATOS',
                        'FORMFIELD' => 'CAMPO DE FORMULARIO',
                        'SaveForm' => 'Guardar formulario',
                        'ChoosetheModulewithLeadOwner' => 'Elija el módulo con el propietario principal',
                        'ChooseYourModule' => 'Elige tu módulo',
                        'DuplicateHandling' => 'Manejo duplicado',
                        'AssigntoLeadOwner' => 'Asignar al dueño principal',
                        'ADDONFIELD' => 'CAMPO DE ADDON',
                        'SelecttheFormFields' => 'Seleccione los campos del formulario',
                        'MandatoryFields' => 'Campos obligatorios',
                        'FormSettings' => 'Configuraciones de formulario',
                        'FormType' => 'Tipo de formulario',
                        'ErrorMessageSubmission' => 'Envío de mensaje de error',
                        'SuccessMessageSubmission' => 'Envío de mensaje de éxito',
                        'EnableURLRedirection' => 'Habilitar redireccionamiento de URL',
                        'EnterRedirectURL' => 'Ingrese la URL de redireccionamiento',
                        'EnableGoogleCaptcha' => 'Habilitar Google Captcha',
                        'ChooseThirdPartyForm' => 'Elegir formulario de terceros',
                        'ThirdPartyFormTitle' => 'Título del formulario de terceros',
                        'Continue' => 'Seguir',
                        'FormFields' => 'Campos de formulario',
                        'UpdateForm' => 'Formulario de actualización',
                        'Form Updated Successfully' => 'Formulario actualizado correctamente',
                        'SHORTCODETITLE' => 'CÓDIGO CORTO / TÍTULO',
                        'FORMTYPE' => 'TIPO DE FORMATO',
                        'MAPPING' => 'CARTOGRAFÍA',
                        'ACTIONS' => 'COMPORTAMIENTO',
                        'DataBucketFieldssearchinForms' => 'Búsqueda de campo de depósito de datos en formularios',
                        'DataBucketFields' => 'Campos de depósito de datos',
                        'Conditions' => 'Condiciones',
                        'FieldName' => 'Nombre del campo',
                        'Fields' => 'Campos',
                        'Reset' => 'Reiniciar',
                        'FORMNAME' => 'NOMBRE DEL FORMULARIO',
                        'SUBMITTEDFORMSCOUNT' => 'CUENTA DE FORMULARIOS PRESENTADOS',
                        'FIRSTNAME' => 'NOMBRE DE PILA',
                        'LASTNAME' => 'APELLIDO',
                        'EMAIL' => 'CORREO ELECTRÓNICO',
                        'SUBMITTED_DATE' => 'FECHA DE ENVÍO',
                        'SYNC_STATUS' => 'ESTADO DE SINCRONIZACIÓN',
                        'createnew' => 'Crear nuevo',
                        'Lists' => 'Liza',
                        'Back' => 'Espalda',
                        'Heading' => 'Bóveda',
                        'SelectPlugin-CustomFields' => 'Seleccionar campos personalizados de complementos',
                        'Optional' => 'Opcional',
                        'ChooseoptiontoSync' => 'Elija la opción para sincronizar',
                        'ConfigureMapping' => 'Configurar mapeo',
                        'CRM' => 'CRM',
                        'Helpdesk' => 'Mesa de ayuda',
                        'DataBucket' => 'Cubo de datos',
                        'DatabucketOnly' => 'Solo depósito de datos',
                        'WPUserAutoSync' => 'Sincronización automática de usuario de WP',
                        'OneTimeManualSync' => 'Sincronización manual única',
                        'ChooseWoocommerceProductsorOrderstobeSync' => 'Elija productos o pedidos de Woocommerce para sincronizar',
                        'SyncWoocommerceProductsOrdersas' => 'Sincronizar pedidos de productos de Woocommerce como',
                        'WPWoocommerceAutoSync' => 'WP Woocommerce Auto Sync',
                        'Choosefromthelist' => 'Escoge CRM de la lista',
                        'ChooseHelpdeskfromthelist' => 'Elija Helpdesk de la lista',
                        'ZohoCRMConfiguration' => 'Configuración de Zoho CRM',
                        'ClientId' => 'Identificación del cliente',
                        'ClientSecret' => 'Secreto del cliente',
                        'Callback' => 'Llamar de vuelta',
                        'AvailableDomains' => 'Dominios disponibles',
                        'ResetConfiguration' => 'Restablecer configuración',
                        'Activate' => 'Activar',
                        'Configure' => 'Configurar',
                        'EnteryourHelpdeskUrl' => 'Ingrese su URL de servicio de asistencia',
                        'EnteryourUsername' => 'Ingrese su nombre de usuario',
                        'EnterAccessKey' => 'Ingresar clave de acceso',
                        'DataBucketssettings' => 'Configuración de cubos de datos',
                        'GroupSettings' => 'Configuraciones de grupo',
                        'ScheduleSettings' => 'Configuraciones de horario',
                        'DataBucketMigrationSettings' => 'Configuración de migración del depósito de datos',
                        'BASICINFORMATION' => 'INFORMACIÓN BÁSICA',
                        'FirstName' => 'Nombre de pila',
                        'LastName' => 'Apellido',
                        'Email' => 'Email',
                        'Street' => 'Calle',
                        'City' => 'Ciudad',
                        'State' => 'Estado',
                        'Country' => 'País',
                        'Zipcode' => 'Código postal',
                        'DefaultFormLogandCaptchaSettings' => 'Configuración predeterminada de registro de formulario y Captcha',
                        'WhichLogDoYouNeed' => '¿Qué registro necesitas?',
                        'None' => 'Ninguno',
                        'Success' => 'Éxito',
                        'Failure' => 'Fracaso',
                        'Both' => 'Ambos',
                        'SpecifyEmail' => 'Especificar correo electrónico',
                        'DoYouWanttoEnabletheCaptcha' => '¿Desea habilitar el Captcha?',
                        'GoogleRecaptchaPublicKey' => 'Clave pública de Google Recaptcha',
                        'GoogleRecaptchaPrivateKey' => 'Clave privada de Google Recaptcha',
                        'Save' => 'Salvar',
                        'GROUPNAME' => 'NOMBRE DEL GRUPO',
                        'ACTION' => 'ACCIÓN',
                        'AddGroup' => 'Añadir grupo',
                        'EnterGroupName' => 'Ingrese el nombre del grupo',
                        'BasicInformation' => 'Información básica',
                        'Schedule' => 'Calendario',
                        'EnableSchedule' => 'Habilitar horario',
                        'ScheduleTime' => 'Tiempo programado',
                        'Migration' => 'Migración',
                        'Doyouwanttomigratedatabucket' => '¿Desea migrar el depósito de datos?',
                        'DataBucketFormsList' => 'Lista de formularios de depósito de datos',
                        'FORMNAME' => 'NOMBRE DEL FORMULARIO',
                        'SyncDataTocRM' => 'Sincronizar datos a CRM',
                        'ThankyouforyourPurchase!' => '¡Gracias por su compra!',
                        'Togetstartedyouneedtodownloadandactivatebyenteringthelicensekey' => 'Para comenzar, debe descargar y activar ingresando la clave de licencia',
                        'EntertheLicenseKey' => 'Ingrese la clave de licencia',
                        'BuyNow' => 'Compra ahora',
                        
		);
                return $response;
        }
}
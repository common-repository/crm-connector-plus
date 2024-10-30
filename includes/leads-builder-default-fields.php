<?php
/**
* CRM Connector Plus plugin file. 
*
* Copyright (C) 2010-2020, Smackcoders Inc - info@smackcoders.com 
*/

namespace Smackcoders\WPULB;

if ( ! defined( 'ABSPATH' ) )
exit; // Exit if accessed directly

global $default_fields;

$default_fields = [
    [
        'field_name' => 'first_name',
        'field_label' => 'First Name',
        'field_type' => 'Text',
        'is_mandatory' => 'No',
        'is_custom_field' => 'No',
    ],
    [
        'field_name' => 'last_name',
        'field_label' => 'Last Name',
        'field_type' => 'Text',
        'is_mandatory' => 'Yes',
        'is_custom_field' => 'No',
    ],
    [
        'field_name' => 'email',
        'field_label' => 'Email',
        'field_type' => 'Email',
        'is_mandatory' => 'No',
        'is_custom_field' => 'No',
    ],
    [
        'field_name' => 'street',
        'field_label' => 'Street',
        'field_type' => 'Text',
        'is_mandatory' => 'No',
        'is_custom_field' => 'No',
    ],
    [
        'field_name' => 'city',
        'field_label' => 'City',
        'field_type' => 'Text',
        'is_mandatory' => 'No',
        'is_custom_field' => 'No',
    ],
    [
        'field_name' => 'state',
        'field_label' => 'State',
        'field_type' => 'Text',
        'is_mandatory' => 'No',
        'is_custom_field' => 'No',
    ],
    [
        'field_name' => 'country',
        'field_label' => 'Country',
        'field_type' => 'Text',
        'is_mandatory' => 'No',
        'is_custom_field' => 'No',
    ],
    [
        'field_name' => 'zipcode',
        'field_label' => 'Zipcode',
        'field_type' => 'Text',
        'is_mandatory' => 'No',
        'is_custom_field' => 'No',
    ],
];
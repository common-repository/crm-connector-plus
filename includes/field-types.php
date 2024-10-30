<?php
/**
* CRM Connector Plus plugin file. 
*
* Copyright (C) 2010-2020, Smackcoders Inc - info@smackcoders.com 
*/

namespace Smackcoders\WPULB;

if ( ! defined( 'ABSPATH' ) )
exit; // Exit if accessed directly

global $field_types;

$field_types = [
    'Text',
    'Integer',
    'Email',
    'Select',
    'Multi-Select',
    'Date',
    'DateTime',
    'Decimal',
    'Boolean',
    'File',
    'Phone',
    'Currency',
    'Hidden'
];


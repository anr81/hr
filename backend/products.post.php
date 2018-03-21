<?php

use Tygh\Registry;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

if ($mode == 'update') {
    
    Registry::set('navigation.tabs.glazing_designer', array (
        'title' => __('types_glazing'),
        'js' => true
    ));
}
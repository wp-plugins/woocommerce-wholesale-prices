<?php
if(!defined('WP_UNINSTALL_PLUGIN'))
    exit();

// Perform Clean Up

global $wc_wholesale_prices;

if(is_a($wc_wholesale_prices,'WooCommerceWholeSalePrices')){
    $wc_wholesale_prices->terminate();
    $wc_wholesale_prices->uninstall();
}
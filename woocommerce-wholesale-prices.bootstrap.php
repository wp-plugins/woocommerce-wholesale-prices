<?php
/*
Plugin Name:    Woocommerce Wholesale Prices
Plugin URI:     https://wholesalesuiteplugin.com
Description:    WooCommerce Extension to Provide Wholesale Prices Functionality
Author:         Rymera Web Co
Version:        1.1.1
Author URI:     http://rymera.com.au/
Text Domain:    woocommerce-wholesale-prices
*/

// This file is the main plugin boot loader

/**
 * Check if WooCommerce is active
 **/
if ( in_array( 'woocommerce/woocommerce.php' , apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {

    // Include Necessary Files
    require_once ( 'woocommerce-wholesale-prices.options.php' );
    require_once ( 'woocommerce-wholesale-prices.plugin.php' );

    // Get Instance of Main Plugin Class
    $wc_wholesale_prices = WooCommerceWholeSalePrices::getInstance();
    $GLOBALS[ 'wc_wholesale_prices' ] = $wc_wholesale_prices;

    // Load Plugin Text Domain
    add_action( 'plugins_loaded' , array( $wc_wholesale_prices , 'loadPluginTextDomain' ) );

    // Register Activation Hook
    register_activation_hook( __FILE__ , array( $wc_wholesale_prices , 'init' ) );

    // Register Deactivation Hook
    register_deactivation_hook( __FILE__ , array( $wc_wholesale_prices , 'terminate' ) );

    //  Register AJAX Call Handlers
    add_action( 'init' , array( $wc_wholesale_prices , 'registerAJAXCAllHandlers' ) );

    // Load Backend CSS and JS
    add_action( 'admin_enqueue_scripts' , array( $wc_wholesale_prices , 'loadBackEndStylesAndScripts' ) );

    // Load Frontend CSS and JS
    add_action( "wp_enqueue_scripts" , array( $wc_wholesale_prices , 'loadFrontEndStylesAndScripts' ) );

    // Register Plugin Menu
    add_action( "admin_menu" , array( $wc_wholesale_prices , 'registerMenu' ) );




    // Code for adding custom wholesale fields on product pages ========================================================

    // Display Product Custom Fields (Simple Product)
    add_action( 'woocommerce_product_options_pricing' , array( $wc_wholesale_prices , 'addSimpleProductCustomFields' ) );

    // Display Product Custom Fields (Variable Product)
    add_action( 'woocommerce_product_after_variable_attributes' , array( $wc_wholesale_prices , 'addVariableProductCustomFields' ) , 10 , 3 );

    // Display Product Custom Fields (Variable Product) JS to add fields for new variations
    add_action( 'woocommerce_product_after_variable_attributes_js' , array( $wc_wholesale_prices , 'addVariableProductCustomFieldsJS' ) );

    // Save Product Custom Fields
    add_action( 'woocommerce_process_product_meta' , array( $wc_wholesale_prices , 'saveSimpleProductCustomFields' ) );

    // Save Product Custom Fields (Variable)
    add_action( 'woocommerce_process_product_meta_variable' , array( $wc_wholesale_prices , 'saveVariableProductCustomFields' ), 10, 1 );




    // Code for adding custom wholesale fields on quick edit option ====================================================

    // Add wholesale custom "FORM" fields on quick edit screen (The form fields we use to add new data)
    add_action( 'woocommerce_product_quick_edit_end' , array( $wc_wholesale_prices , 'addCustomWholesaleFieldsOnQuickEditScreen' ) );

    // Save wholesale custom fields on quick edit screen
    add_action( 'woocommerce_product_quick_edit_save' , array( $wc_wholesale_prices , 'saveCustomWholesaleFieldsOnQuickEditScreen' ) , 10 , 1 );

    // Add wholesale custom fields metadata on product listing columns
    // The purpose for this is to set the wholesale custom "FORM" fields the value of the existing wholesale custom fields value
    // This is utilized by the wwp-quick-edit.js file
    add_action( 'manage_product_posts_custom_column' , array( $wc_wholesale_prices , 'addCustomWholesaleFieldsMetaDataOnProductListingColumn' ) , 99 , 2 );




    // Code for adding wholesale column to product listing screen ======================================================

    add_filter( 'manage_product_posts_columns' , array( $wc_wholesale_prices , 'addWholesalePriceListingColumn' ) , 99 , 1 );
	add_action( 'manage_product_posts_custom_column' , array( $wc_wholesale_prices , 'addWholesalePriceListingColumnData' ) , 99 , 2 );




    // Code for integrating into woocommerce price =====================================================================

    // Apply wholesale price to archive and single product pages
    add_filter( 'woocommerce_get_price_html' , array( $wc_wholesale_prices , 'wholesalePriceHTMLFilter' ) , 10 , 2 );

    // Apply wholesale price whenever "get_html_price" function gets called inside a variation product
    // Variation product is the actual variation of a variable product
    // Variable product is the parent product which contains variations
    add_action( 'woocommerce_get_variation_price_html' , array( $wc_wholesale_prices , 'wholesaleSingleVariationPriceHTMLFilter' ) , 10 , 2 );

    // Apply wholesale price upon adding product to cart
    add_action( 'woocommerce_before_calculate_totals' , array( $wc_wholesale_prices , 'applyProductWholesalePrice' ) , 10 , 1 );

    // Apply wholesale price on WC Cart Widget.
    add_filter( 'woocommerce_cart_item_price' , array( $wc_wholesale_prices , 'applyProductWholesalePriceOnDefaultWCCartWidget' ) , 10 , 3 );

    // Add notice to WC Widget if the user (wholesale user) fails to avail the wholesale price requirements. Only applies to wholesale users.
    add_action( 'woocommerce_before_mini_cart' , array( $wc_wholesale_prices , 'beforeWCWidget' ) );




    // Add Custom Plugin Listing Action Links ===========================================================================

    // Settings
    add_filter( 'plugin_action_links' , array( $wc_wholesale_prices , 'addPluginListingCustomActionLinks' ) , 10 , 2 );




    // Default Prices Settings Content =================================================================================

    // If Premium Add On Isn't Present
    if ( !in_array( 'woocommerce-wholesale-prices-premium/woocommerce-wholesale-prices-premium.bootstrap.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {

        $wc_wholesale_prices->activatePluginSettings();
        add_filter( 'wwp_filter_settings_sections' , array( $wc_wholesale_prices, 'pluginSettingsSections' ), 10 , 1 );
        add_filter( 'wwof_settings_section_content' , array( $wc_wholesale_prices, 'pluginSettingsSectionContent' ), 10, 2 );

    }

}

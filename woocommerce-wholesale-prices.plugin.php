<?php
if (!defined('ABSPATH')) exit; // Exit if accessed directly

/**
 * This is the main plugin class. It's purpose generally is for "ALL PLUGIN RELATED STUFF ONLY".
 * This file or class may also serve as a controller to some degree but most if not all business logic is distributed
 * across include files.
 *
 * Class WooCommerceWholeSalePrices
 */

require_once ('includes/class-wwp-wholesale-roles.php');
require_once ('includes/class-wwp-custom-fields.php');
require_once ('includes/class-wwp-wholesale-prices.php');

class WooCommerceWholeSalePrices {

    /*
     |------------------------------------------------------------------------------------------------------------------
     | Class Members
     |------------------------------------------------------------------------------------------------------------------
     */

    private static $_instance;

    private $_wwp_wholesale_roles;
    private $_wwp_custom_fields;
    private $_wwp_wholesale_prices;

    const VERSION = '1.0.0';




    /*
     |------------------------------------------------------------------------------------------------------------------
     | Mesc Functions
     |------------------------------------------------------------------------------------------------------------------
     */

    /**
     * Class constructor.
     *
     * @since 1.0.0
     */
    public function __construct(){

        $this->_wwp_wholesale_roles = WWP_Wholesale_Roles::getInstance();
        $this->_wwp_custom_fields = WWP_Custom_Fields::getInstance();
        $this->_wwp_wholesale_prices = WWP_Wholesale_Prices::getInstance();

    }

    /**
     * Singleton Pattern.
     *
     * @since 1.0.0
     *
     * @return WooCommerceWholeSalePrices
     */
    public static function getInstance(){

        if(!self::$_instance instanceof self)
            self::$_instance = new self;
        return self::$_instance;

    }




    /*
     |------------------------------------------------------------------------------------------------------------------
     | Bootstrap/Shutdown Functions
     |------------------------------------------------------------------------------------------------------------------
     */

    /**
     * Plugin activation hook callback.
     *
     * @since 1.0.0
     */
    public function init(){

        // Add plugin custom roles and capabilities
        $this->_wwp_wholesale_roles->addCustomRole('wholesale_customer','Wholesale Customer');
        $this->_wwp_wholesale_roles->registerCustomRole('wholesale_customer',
                                                        'Wholesale Customer',
                                                        array(
                                                            'desc'  =>  'This is the main wholesale user role.',
                                                            'main'  =>  true
                                                        ));
        $this->_wwp_wholesale_roles->addCustomCapability('wholesale_customer','have_wholesale_price');

    }

    /**
     * Plugin deactivation hook callback.
     *
     * @since 1.0.0
     */
    public function terminate(){

        // Remove plugin custom roles and capabilities
        $this->_wwp_wholesale_roles->removeCustomCapability('wholesale_customer','have_wholesale_price');
        $this->_wwp_wholesale_roles->removeCustomRole('wholesale_customer');
        $this->_wwp_wholesale_roles->unregisterCustomRole('wholesale_customer');

    }




    /*
    |-------------------------------------------------------------------------------------------------------------------
    | Admin Functions
    |-------------------------------------------------------------------------------------------------------------------
    */

    /**
     * Load Admin or Backend Related Styles and Scripts.
     *
     * @since 1.0.0
     *
     * @param $handle
     */
    public function loadBackEndStylesAndScripts($handle){
        // Only plugin styles and scripts on the right time and on the right place

        // Styles
        wp_enqueue_style('wwp_wcoverrides_css', WWP_CSS_URL.'wwp-back-end-wcoverrides.css', array(), self::VERSION, 'all');

        // Scripts
        $screen = get_current_screen();

        // Products
        if ( in_array( $screen->id, array( 'edit-product' ) ) ) {
            wp_enqueue_script( 'wwp_quick_edit', WWP_JS_URL . 'wc/wwp-quick-edit.js', array('jquery'), self::VERSION );
        }

    }

    /**
     * Load Frontend Related Styles and Scripts.
     *
     * @param $handle
     *
     * @since 1.0.0
     */
    public function loadFrontEndStylesAndScripts($handle){
        // Only plugin styles and scripts on the right time and on the right place
    }

    /**
     * Register plugin menu.
     *
     * @since 1.0.0
     */
    public function registerMenu(){

    }




    /*
    |-------------------------------------------------------------------------------------------------------------------
    | Woocommerce Integration (Settings)
    |-------------------------------------------------------------------------------------------------------------------
    */

    /**
     * Activate plugin settings.
     *
     * @since 1.0.0
     */
    public function activatePluginSettings () {

        add_filter( "woocommerce_get_settings_pages" , array( self::getInstance() ,'initializePluginSettings' ) );

    }

    /**
     * Initialize plugin settings.
     *
     * @param $settings
     *
     * @return array
     * @since 1.0.0
     */
    public function initializePluginSettings ( $settings ) {

        $settings[] = include( WWP_INCLUDES_PATH."class-wwp-settings.php" );

        return $settings;

    }




    /*
    |-------------------------------------------------------------------------------------------------------------------
    | Woocommerce Integration (Custom Fields)
    |-------------------------------------------------------------------------------------------------------------------
    */

    /**
     * Add wholesale custom price field to single product edit page.
     *
     * @since 1.0.0
     */
    public function addSimpleProductCustomFields(){

        $this->_wwp_custom_fields->addSimpleProductCustomFields($this->_wwp_wholesale_roles->getAllRegisteredWholesaleRoles());

    }

    /**
     * Add wholesale custom price field to variable product edit page (on the variations section).
     *
     * @param $loop
     * @param $variation_data
     * @param $variation
     *
     * @since 1.0.0
     */
    public function addVariableProductCustomFields ( $loop , $variation_data , $variation ){

        $this->_wwp_custom_fields->addVariableProductCustomFields ( $loop , $variation_data , $variation , $this->_wwp_wholesale_roles->getAllRegisteredWholesaleRoles() );

    }

    /**
     * Add wholesale custom price field to variable product edit page (on the variations section) JS Version.
     *
     * @since 1.0.0
     */
    public function addVariableProductCustomFieldsJS(){

        $this->_wwp_custom_fields->addVariableProductCustomFieldsJS($this->_wwp_wholesale_roles->getAllRegisteredWholesaleRoles());

    }

    /**
     * Save wholesale custom price field on single products.
     *
     * @param $post_id
     *
     * @since 1.0.0
     */
    public function saveSimpleProductCustomFields($post_id){

        $this->_wwp_custom_fields->saveSimpleProductCustomFields($post_id,$this->_wwp_wholesale_roles->getAllRegisteredWholesaleRoles());

    }

    /**
     * Save wholesale custom price field on variable products.
     *
     * @param $post_id
     *
     * @since 1.0.0
     */
    public function saveVariableProductCustomFields( $post_id ) {

        $this->_wwp_custom_fields->saveVariableProductCustomFields($post_id,$this->_wwp_wholesale_roles->getAllRegisteredWholesaleRoles());

    }

    /**
     * Add wholesale custom form fields on the quick edit option.
     *
     * @since 1.0.0
     */
    public function addCustomWholesaleFieldsOnQuickEditScreen(){

        $this->_wwp_custom_fields->addCustomWholesaleFieldsOnQuickEditScreen($this->_wwp_wholesale_roles->getAllRegisteredWholesaleRoles());

    }

    /**
     * Save wholesale custom fields on the quick edit option.
     *
     * @param $product
     *
     * @since 1.0.0
     */
    public function saveCustomWholesaleFieldsOnQuickEditScreen($product){

        $this->_wwp_custom_fields->saveCustomWholesaleFieldsOnQuickEditScreen($product,$this->_wwp_wholesale_roles->getAllRegisteredWholesaleRoles());

    }

    /**
     * Add wholesale custom fields meta data on the product listing columns, this metadata is used to pre-populate the
     * wholesale custom form fields with the values of the meta data saved on the db.
     * This works in conjunction with wwp-quick-edit.js.
     *
     * @param $column
     * @param $post_id
     *
     * @since 1.0.0
     */
    public function addCustomWholesaleFieldsMetaDataOnProductListingColumn($column,$post_id){

        $this->_wwp_custom_fields->addCustomWholesaleFieldsMetaDataOnProductListingColumn($column,$post_id,$this->_wwp_wholesale_roles->getAllRegisteredWholesaleRoles());

    }




    /*
    |-------------------------------------------------------------------------------------------------------------------
    | Woocommerce Integration (Price)
    |-------------------------------------------------------------------------------------------------------------------
    */

    /**
     * Filter callback that alters the product price, it embeds the wholesale price of a product for a wholesale user.
     *
     * @param $price
     * @param $product
     *
     * @return mixed|string
     * @since 1.0.0
     */
    public function wholesalePriceHTMLFilter($price, $product){

        return $this->_wwp_wholesale_prices->wholesalePriceHTMLFilter($price,$product,$this->_wwp_wholesale_roles->getUserWholesaleRole());

    }

    /**
     * Filter to append wholesale price on variations of a variable product on single product page.
     *
     * @param $available_variations
     *
     * @return mixed
     * @since 1.0.0
     */
    public function wholesaleVariationPriceHTMLFilter($available_variations){

        return $this->_wwp_wholesale_prices->wholesaleVariationPriceHTMLFilter($available_variations,$this->_wwp_wholesale_roles->getUserWholesaleRole());

    }

    /**
     * Apply product wholesale price upon adding to cart.
     *
     * @param $cart_object
     *
     * @since 1.0.0
     */
    public function applyProductWholesalePrice($cart_object){

        $this->_wwp_wholesale_prices->applyProductWholesalePrice( $cart_object , $this->_wwp_wholesale_roles->getUserWholesaleRole() );

    }

    /**
     * Apply wholesale price on WC Cart Widget.
     *
     * @param $product_price
     * @param $cart_item
     * @param $cart_item_key
     * @return mixed
     *
     * @since 1.0.0
     */
    public function applyProductWholesalePriceOnDefaultWCCartWidget ( $product_price, $cart_item, $cart_item_key ) {

        return $this->_wwp_wholesale_prices->applyProductWholesalePriceOnDefaultWCCartWidget( $product_price , $cart_item , $cart_item_key , $this->_wwp_wholesale_roles->getUserWholesaleRole() );

    }

    /**
     * Add notice to WC Widget if the user (wholesale user) fails to avail the wholesale price requirements.
     * Only applies to wholesale users.
     *
     * @since 1.0.0
     */
    public function beforeWCWidget () {

        $this->_wwp_wholesale_prices->beforeWCWidget( $this->_wwp_wholesale_roles->getUserWholesaleRole() );

    }




    /*
     |------------------------------------------------------------------------------------------------------------------
     | AJAX Handlers
     |------------------------------------------------------------------------------------------------------------------
     */

    /**
     * Register AJAX interface callbacks.
     *
     * @since 1.0.0
     */
    public function registerAJAXCAllHandlers(){

    }




    /*
     |------------------------------------------------------------------------------------------------------------------
     | Utilities
     |------------------------------------------------------------------------------------------------------------------
     */

    /**
     * Write test log.
     *
     * @param      $msg
     * @param bool $append
     *
     * @since 1.0.0
     */
    public function writeTestLog($msg,$append = true){

        if($append === true)
            file_put_contents(WWP_LOGS_PATH.'test_logs.txt',$msg,FILE_APPEND);
        else
            file_put_contents(WWP_LOGS_PATH.'test_logs.txt',$msg);

    }

    /**
     * Write error log.
     *
     * @param      $msg
     * @param bool $append
     *
     * @since 1.0.0
     */
    public function writeErrorLog($msg,$append = true){

        if($append === true)
            file_put_contents(WWP_LOGS_PATH.'error_logs.txt',$msg,FILE_APPEND);
        else
            file_put_contents(WWP_LOGS_PATH.'error_logs.txt',$msg);

    }

}
<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class WWP_Custom_Fields {

    private static $_instance;

    public static function getInstance(){
        if(!self::$_instance instanceof self)
            self::$_instance = new self;
        return self::$_instance;
    }

    /**
     * Add wholesale custom price field to single product edit page.
     *
     * @param $registeredCustomRoles
     *
     * @since 1.0.0
     */
    public function addSimpleProductCustomFields($registeredCustomRoles){

        global $woocommerce, $post;

        echo '<div class="options_group">';
        echo '<h3 style="padding-bottom:0;">'.__('Wholesale Prices','woocommerce-wholesale-prices').'</h3>';
        echo '<p style="margin:0; padding:0 12px;">'.__('Wholesale Price for this product','woocommerce-wholesale-prices').'</p>';

        foreach($registeredCustomRoles as $roleKey => $role){

            $currencySymbol = get_woocommerce_currency_symbol();
            if(array_key_exists('currency_symbol',$role) && !empty($role['currency_symbol']))
                $currencySymbol = $role['currency_symbol'];

            woocommerce_wp_text_input(
                array(
                    'id'            =>  $roleKey.'_wholesale_price',
                    'label'         =>  __( $role['roleName']." (".$currencySymbol.")", 'woocommerce-wholesale-prices' ),
                    'placeholder'   =>  '',
                    'desc_tip'      =>  'true',
                    'description'   =>  __( 'Only applies to users with the role of "'.$role['roleName'].'"', 'woocommerce-wholesale-prices' ),
                    'data_type'     =>  'price'
                )
            );

        }

        echo '</div>';

    }

    /**
     * Add wholesale custom price field to variable product edit page (on the variations section).
     *
     * @param $loop
     * @param $variation_data
     * @param $registeredCustomRoles
     *
     * @since 1.0.0
     */
    public function addVariableProductCustomFields( $loop , $variation_data , $variation , $registeredCustomRoles ){

        global $woocommerce, $post;

        // Get the variable product data manually
        // Don't rely on the variation data woocommerce supplied
        // There is a logic change introduced on 2.3 series where they only send variation data (or variation meta)
        // That is built in to woocommerce, so all custom variation meta added to a variable product don't get passed along
        $variable_product_meta = get_post_meta( $variation->ID );

        ?>
        <tr>
            <td colspan="2">
                <?php
                echo '<hr>';
                echo '<h4 style="margin:0; padding:0; font-size:14px;">'.__('Wholesale Prices','woocommerce-wholesale-prices').'</h4>';
                echo '<p style="margin:0; padding:0;">'.__('Wholesale Price for this product','woocommerce-wholesale-prices').'</p>';
                ?>
            </td>
        </tr>
        <?php

        foreach($registeredCustomRoles as $roleKey => $role){

            $currencySymbol = get_woocommerce_currency_symbol();
            if(array_key_exists('currency_symbol',$role) && !empty($role['currency_symbol']))
                $currencySymbol = $role['currency_symbol'];

            ?>
            <tr>
                <td colspan="2">
                    <?php
                    woocommerce_wp_text_input(
                        array(
                            'id'                =>  $roleKey.'_wholesale_prices['.$loop.']',
                            'label'             =>  __( $role['roleName']." (".$currencySymbol.")", 'woocommerce-wholesale-prices' ),
                            'placeholder'       =>  '',
                            'desc_tip'      =>  'true',
                            'description'   =>  __( 'Only applies to users with the role of "'.$role['roleName'].'"', 'woocommerce-wholesale-prices' ),
                            'data_type'     =>  'price',
                            'value'         =>  $variable_product_meta[ $roleKey.'_wholesale_price' ][0]
                            //'value'         =>  $variation_data[$roleKey.'_wholesale_price'][0]
                        )
                    );
                    ?>
                </td>
            </tr>
        <?php
        }

    }

    /**
     * Add wholesale custom price field to variable product edit page (on the variations section) JS Version.
     *
     * @param $registeredCustomRoles
     *
     * @since 1.0.0
     */
    public function addVariableProductCustomFieldsJS($registeredCustomRoles){

        global $woocommerce, $post;

        ?>
        <tr>
            <td colspan="2">
                <?php
                echo '<hr>';
                echo '<h4 style="margin:0; padding:0; font-size:14px;">'.__('Wholesale Prices','woocommerce-wholesale-prices').'</h4>';
                echo '<p style="margin:0; padding:0;">'.__('Wholesale Price for this product','woocommerce-wholesale-prices').'</p>';
                ?>
            </td>
        </tr>
        <?php

        foreach($registeredCustomRoles as $roleKey => $role){

            $currencySymbol = get_woocommerce_currency_symbol();
            if(array_key_exists('currency_symbol',$role) && !empty($role['currency_symbol']))
                $currencySymbol = $role['currency_symbol'];

            ?>
            <tr>
                <td colspan="2">
                    <?php
                    woocommerce_wp_text_input(
                        array(
                            'id'                =>  $roleKey.'_wholesale_prices[ + loop + ]',
                            'label'             =>  __( $role['roleName']." (".$currencySymbol.")", 'woocommerce-wholesale-prices' ),
                            'placeholder'       =>  '',
                            'desc_tip'          =>  'true',
                            'description'       =>  __( 'Only applies to users with the role of "'.$role['roleName'].'"', 'woocommerce-wholesale-prices' ),
                            'data_type'         =>  'price',
                            'value'             =>  $variation_data[$roleKey.'_wholesale_price'][0]
                        )
                    );
                    ?>
                </td>
            </tr>
        <?php
        }

    }

    /**
     * Save wholesale custom price field on single products.
     *
     * @param $post_id
     * @param $registeredCustomRoles
     *
     * @since 1.0.0
     */
    public function saveSimpleProductCustomFields($post_id,$registeredCustomRoles){

        foreach($registeredCustomRoles as $roleKey => $role){

            $wholesalePrice = trim(esc_attr( $_POST[$roleKey.'_wholesale_price'] ));

            if(!empty($wholesalePrice)){
                if(!is_numeric($wholesalePrice))
                    $wholesalePrice = '';
                elseif($wholesalePrice < 0)
                    $wholesalePrice = 0;
                else
                    $wholesalePrice = wc_format_decimal( $wholesalePrice );
            }

            update_post_meta( $post_id, $roleKey.'_wholesale_price', wc_clean($wholesalePrice) );

        }

    }

    /**
     * Save wholesale custom price field on variable products.
     *
     * @param $post_id
     * @param $registeredCustomRoles
     *
     * @since 1.0.0
     */
    public function saveVariableProductCustomFields( $post_id, $registeredCustomRoles) {

        global $_POST;

        if (isset( $_POST['variable_sku'] ) ){

            $variable_sku = $_POST['variable_sku'];
            $variable_post_id = $_POST['variable_post_id'];

            foreach($registeredCustomRoles as $roleKey => $role){

                $wholesalePrices = $_POST[$roleKey.'_wholesale_prices'];

                for ( $i = 0; $i < sizeof( $variable_sku ); $i++ ){
                    $variation_id = (int) $variable_post_id[$i];
                    if ( isset( $wholesalePrices[$i] ) ) {

                        $wholesalePrices[$i] = trim(esc_attr( $wholesalePrices[$i] ));

                        if(!empty($wholesalePrices[$i])){
                            if(!is_numeric($wholesalePrices[$i]))
                                $wholesalePrices[$i] = '';
                            elseif($wholesalePrices[$i] < 0)
                                $wholesalePrices[$i] = 0;
                            else
                                $wholesalePrices[$i] = wc_format_decimal($wholesalePrices[$i]);
                        }

                        update_post_meta( $variation_id, $roleKey.'_wholesale_price', wc_clean( $wholesalePrices[$i] ) );
                    }
                }

            }
        }

    }

    /**
     * Add wholesale custom form fields on the quick edit option.
     *
     * @param $registeredCustomRoles
     *
     * @since 1.0.0
     */
    public function addCustomWholesaleFieldsOnQuickEditScreen($registeredCustomRoles){
        ?>
        <div class="quick_edit_wholesale_prices" style="float: none; clear: both; display: block;">
            <h4><?php _e( 'Wholesale Price', 'woocommerce' ); ?></h4>

            <?php
            foreach($registeredCustomRoles as $roleKey => $role){

                $currencySymbol = get_woocommerce_currency_symbol();
                if(array_key_exists('currency_symbol',$role) && !empty($role['currency_symbol']))
                    $currencySymbol = $role['currency_symbol'];

                ?>
                <label class="alignleft" style="width: 100%;">
                    <div class="title"><?php _e( $role['roleName'].' Price ('.$currencySymbol.')', 'woocommerce-wholesale-prices' ); ?></div>
                    <input type="text" name="<?php echo $roleKey; ?>_wholesale_price" class="text wholesale_price wc_input_price" value="">
                </label>
            <?php
            }
            ?>
            <div style="clear: both; float: none; display: block;"></div>
        </div>
    <?php

    }

    /**
     * Save wholesale custom fields on the quick edit option.
     *
     * @param $product
     * @param $registeredCustomRoles
     *
     * @since 1.0.0
     */
    public function saveCustomWholesaleFieldsOnQuickEditScreen($product, $registeredCustomRoles){

        if ( $product->is_type('simple') || $product->is_type('external') ) {

            $post_id = $product->id;

            foreach($registeredCustomRoles as $roleKey => $role){

                if ( isset( $_REQUEST[$roleKey.'_wholesale_price'] ) ) {

                    $wholesalePrice = trim(esc_attr( $_REQUEST[$roleKey.'_wholesale_price'] ));

                    if(!empty($wholesalePrice)){
                        if(!is_numeric($wholesalePrice))
                            $wholesalePrice = '';
                        elseif($wholesalePrice < 0)
                            $wholesalePrice = 0;
                        else
                            $wholesalePrice = wc_format_decimal( $wholesalePrice );
                    }

                    update_post_meta( $post_id, $roleKey.'_wholesale_price', wc_clean( $wholesalePrice ) );
                }
            }
        }

    }

    /**
     * Add wholesale custom fields meta data on the product listing columns, this metadata is used to pre-populate the
     * wholesale custom form fields with the values of the meta data saved on the db.
     * This works in conjunction with wwp-quick-edit.js.
     *
     * @param $column
     * @param $post_id
     * @param $registeredCustomRoles
     *
     * @since 1.0.0
     */
    public function addCustomWholesaleFieldsMetaDataOnProductListingColumn($column,$post_id,$registeredCustomRoles){

        switch ( $column ) {
            case 'name' :
                ?>
                <div class="hidden wholesale_prices_inline" id="wholesale_prices_inline_<?php echo $post_id; ?>">
                    <?php
                    foreach($registeredCustomRoles as $roleKey => $role){
                        ?>
                        <div id="<?php echo $roleKey; ?>_wholesale_price" class="whole_price"><?php echo get_post_meta($post_id,$roleKey.'_wholesale_price',true); ?></div>
                    <?php
                    }
                    ?>
                </div>
                <?php

                break;

            default :
                break;
        }

    }

}
<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class WWP_Custom_Fields {

    private static $_instance;

    public static function getInstance() {

        if( !self::$_instance instanceof self )
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
    public function addSimpleProductCustomFields( $registeredCustomRoles ) {

        global $woocommerce, $post;

        echo '<div class="options_group">';
        echo '<h3 style="padding-bottom:0;">' . __( 'Wholesale Prices' , 'woocommerce-wholesale-prices' ) . '</h3>';
        echo '<p style="margin:0; padding:0 12px;">' . __( 'Wholesale Price for this product' , 'woocommerce-wholesale-prices') . '</p>';

        foreach($registeredCustomRoles as $roleKey => $role){

            $currencySymbol = get_woocommerce_currency_symbol();
            if( array_key_exists( 'currency_symbol' , $role ) && !empty( $role[ 'currency_symbol' ] ) )
                $currencySymbol = $role[ 'currency_symbol' ];

            woocommerce_wp_text_input(
                array(
                    'id'            =>  $roleKey . '_wholesale_price',
                    'label'         =>  $role[ 'roleName' ] . " (" . $currencySymbol . ")",
                    'placeholder'   =>  '',
                    'desc_tip'      =>  'true',
                    'description'   =>  sprintf( __( 'Only applies to users with the role of %1$s' , 'woocommerce-wholesale-prices' ) , $role[ 'roleName' ] ),
                    'data_type'     =>  'price'
                )
            );

        }

        echo '</div>';

    }

    /**
     * Add wholesale price column to the product listing page.
     *
     * @param $columns
     * @return array
     *
     * @since 1.0.1
     */
    public function addWholesalePriceListingColumn ( $columns ) {

        $allKeys = array_keys( $columns );
        $priceIndex = array_search( 'price' , $allKeys);

        $newColumnsArray = array_slice( $columns , 0 , $priceIndex + 1 , true ) +
            array( 'wholesale_price' => 'Wholesale Price' ) +
            array_slice( $columns , $priceIndex + 1 , NULL , true );

        return $newColumnsArray;

    }

    /**
     * Add wholesale price column data for each product on the product listing page
     *
     * @param $column
     * @param $post_id
     * @param $registeredCustomRoles
     *
     * @since 1.0.1
     */
    public function addWholesalePriceListingColumnData ( $column , $post_id , $registeredCustomRoles ) {

        switch ( $column ) {
            case 'wholesale_price': ?>

                <div class="wholesale_prices" id="wholesale_prices_<?php echo $post_id; ?>">

                    <?php $product = wc_get_product($post_id);

                    foreach ( $registeredCustomRoles as $roleKey => $role ) {

                        $wholesalePrice = "";

                        if ( $product->product_type == 'simple' ) {

                            $wholesalePrice = get_post_meta( $post_id , $roleKey . '_wholesale_price' , true );

                            if ( $wholesalePrice )
                                $wholesalePrice = wc_price( $wholesalePrice );

                        } elseif ( $product->product_type == 'variable' ) {

                            $variations = $product->get_available_variations();
                            $minPrice = '';
                            $maxPrice = '';
                            $someVariationsHaveWholesalePrice = false;

                            foreach( $variations as $variation ) {

                                $variation = wc_get_product( $variation[ 'variation_id' ] );

                                $currVarWholesalePrice = get_post_meta( $variation->variation_id , $roleKey . '_wholesale_price' , true );

                                $currVarPrice = $variation->price;

                                if ( $variation->is_on_sale() )
                                    $currVarPrice = $variation->get_sale_price();

                                if ( strcasecmp( $currVarWholesalePrice , '' ) != 0 ) {

                                    $currVarPrice = $currVarWholesalePrice;

                                    if( !$someVariationsHaveWholesalePrice )
                                        $someVariationsHaveWholesalePrice = true;

                                }

                                if( strcasecmp( $minPrice , '' ) == 0 || $currVarPrice < $minPrice )
                                    $minPrice = $currVarPrice;

                                if( strcasecmp( $maxPrice , '' ) == 0 || $currVarPrice > $maxPrice )
                                    $maxPrice = $currVarPrice;

                            }

                            if ( $someVariationsHaveWholesalePrice && strcasecmp( $minPrice , '' ) != 0 && strcasecmp( $maxPrice , '' ) != 0 ) {

                                if ( $minPrice != $maxPrice && $minPrice < $maxPrice )
                                    $wholesalePrice = wc_price( $minPrice ) . ' - ' . wc_price( $maxPrice );
                                else
                                    $wholesalePrice = wc_price( $maxPrice );

                            }

                        } else
                            continue; ?>

                        <div id="<?php echo $roleKey; ?>_wholesale_price" class="wholesale_price">
                        <?php

                        // Print the wholesale price
                        if ( !empty( $wholesalePrice ) )
                            echo '<div class="wholesale_role">' . $role[ 'roleName' ] . '</div>' . $wholesalePrice;

                        ?>
                        </div>

                <?php } ?>

                </div>
                <?php

                break;

            default :
                break;
        }

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
    public function addVariableProductCustomFields( $loop , $variation_data , $variation , $registeredCustomRoles ) {

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
                echo '<h4 style="margin:0; padding:0; font-size:14px;">' . __( 'Wholesale Prices' , 'woocommerce-wholesale-prices' ) . '</h4>';
                echo '<p style="margin:0; padding:0;">' . __( 'Wholesale Price for this product' , 'woocommerce-wholesale-prices' ) . '</p>';
                ?>
            </td>
        </tr>
        <?php

        foreach( $registeredCustomRoles as $roleKey => $role ) {

            $currencySymbol = get_woocommerce_currency_symbol();
            if( array_key_exists( 'currency_symbol' , $role ) && !empty( $role[ 'currency_symbol' ] ) )
                $currencySymbol = $role[ 'currency_symbol' ];

            ?>
            <tr>
                <td colspan="2">
                    <?php
                    woocommerce_wp_text_input(
                        array(
                            'id'            =>  $roleKey . '_wholesale_prices[' . $loop . ']',
                            'label'         =>  $role[ 'roleName' ] . " (" . $currencySymbol . ")",
                            'placeholder'   =>  '',
                            'desc_tip'      =>  'true',
                            'description'   =>  sprintf( __( 'Only applies to users with the role of %1$s' , 'woocommerce-wholesale-prices' ) , $role[ 'roleName' ] ),
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
    public function addVariableProductCustomFieldsJS( $registeredCustomRoles ) {

        global $woocommerce, $post;

        ?>
        <tr>
            <td colspan="2">
                <?php
                echo '<hr>';
                echo '<h4 style="margin:0; padding:0; font-size:14px;">' . __( 'Wholesale Prices' , 'woocommerce-wholesale-prices' ) . '</h4>';
                echo '<p style="margin:0; padding:0;">' . __( 'Wholesale Price for this product' , 'woocommerce-wholesale-prices' ) . '</p>';
                ?>
            </td>
        </tr>
        <?php

        foreach( $registeredCustomRoles as $roleKey => $role ) {

            $currencySymbol = get_woocommerce_currency_symbol();
            if( array_key_exists( 'currency_symbol' , $role ) && !empty( $role[ 'currency_symbol' ] ) )
                $currencySymbol = $role[ 'currency_symbol' ];

            ?>
            <tr>
                <td colspan="2">
                    <?php
                    woocommerce_wp_text_input(
                        array(
                            'id'                =>  $roleKey . '_wholesale_prices[ + loop + ]',
                            'label'             =>  $role[ 'roleName' ] . " (" . $currencySymbol . ")",
                            'placeholder'       =>  '',
                            'desc_tip'          =>  'true',
                            'description'       =>  sprintf( __( 'Only applies to users with the role of %1$s' , 'woocommerce-wholesale-prices' ) , $role[ 'roleName' ] ),
                            'data_type'         =>  'price',
                            'value'             =>  $variation_data[ $roleKey . '_wholesale_price' ][ 0 ]
                        )
                    );
                    ?>
                </td>
            </tr>
        <?php
        }

    }

    /**
     * Save wholesale custom price field on simple products.
     *
     * @param $post_id
     * @param $registeredCustomRoles
     *
     * @since 1.0.0
     */
    public function saveSimpleProductCustomFields ( $post_id , $registeredCustomRoles ) {

        foreach ( $registeredCustomRoles as $roleKey => $role ) {

            $wholesalePrice = trim( esc_attr( $_POST[ $roleKey . '_wholesale_price' ] ) );

            $thousand_sep = get_option( 'woocommerce_price_thousand_sep' );
            $decimal_sep = get_option( 'woocommerce_price_decimal_sep' );

            if ( $thousand_sep )
                $wholesalePrice = str_replace( $thousand_sep , '' , $wholesalePrice );

            if ( $decimal_sep )
                $wholesalePrice = str_replace( $decimal_sep , '.' , $wholesalePrice );

            if ( !empty( $wholesalePrice ) ) {

                if( !is_numeric( $wholesalePrice ) )
                    $wholesalePrice = '';
                elseif ( $wholesalePrice < 0 )
                    $wholesalePrice = 0;
                else
                    $wholesalePrice = wc_format_decimal( $wholesalePrice );

                if ( is_numeric( $wholesalePrice ) && $wholesalePrice > 0 )
                    update_post_meta( $post_id , $roleKey . '_have_wholesale_price' , 'yes' );
                else
                    update_post_meta( $post_id , $roleKey . '_have_wholesale_price' , 'no' );

            } else {

                update_post_meta( $post_id , $roleKey . '_have_wholesale_price' , 'no' );

                $terms = get_the_terms( $post_id , 'product_cat' );
                if ( !is_array( $terms ) )
                    $terms = array();

                foreach ( $terms as $term ) {

                    $category_wholesale_prices = get_option( 'taxonomy_' . $term->term_id );

                    if ( is_array( $category_wholesale_prices ) && array_key_exists( $roleKey . '_wholesale_discount' , $category_wholesale_prices ) ) {

                        $curr_discount = $category_wholesale_prices[ $roleKey . '_wholesale_discount' ];

                        if ( !empty( $curr_discount ) ) {

                            update_post_meta( $post_id , $roleKey . '_have_wholesale_price' , 'yes' );
                            break;

                        }

                    }

                }

            }

            $wholesalePrice = wc_clean( apply_filters( 'wwp_filter_before_save_wholesale_price' , $wholesalePrice , $roleKey , $post_id , 'simple' ) );
            update_post_meta( $post_id , $roleKey . '_wholesale_price' , $wholesalePrice );

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
    public function saveVariableProductCustomFields( $post_id , $registeredCustomRoles ) {

        global $_POST;

        if (isset( $_POST[ 'variable_sku' ] ) ) {

            // We delete this meta in the beggining coz we are using add_post_meta, not update_post_meta below
            // If we dont delete this, the values will be stacked with the old values
            // Note: per role
            foreach( $registeredCustomRoles as $roleKey => $role )
                delete_post_meta( $_POST[ 'post_ID' ] , $roleKey . '_variations_with_wholesale_price' );

            $variable_sku = $_POST['variable_sku'];
            $variable_post_id = $_POST['variable_post_id'];

            $thousand_sep = get_option( 'woocommerce_price_thousand_sep' );
            $decimal_sep = get_option( 'woocommerce_price_decimal_sep' );

            foreach( $registeredCustomRoles as $roleKey => $role ) {

                $wholesalePrices = $_POST[$roleKey.'_wholesale_prices'];

                for ( $i = 0; $i < sizeof( $variable_sku ); $i++ ){
                    $variation_id = (int) $variable_post_id[$i];
                    if ( isset( $wholesalePrices[$i] ) ) {

                        $wholesalePrices[$i] = trim(esc_attr( $wholesalePrices[$i] ));

                        if ( $thousand_sep )
                            $wholesalePrices[$i] = str_replace( $thousand_sep , '' ,  $wholesalePrices[$i] );

                        if ( $decimal_sep )
                            $wholesalePrices[$i] = str_replace( $decimal_sep , '.' ,  $wholesalePrices[$i] );

                        if(!empty($wholesalePrices[$i])){
                            if(!is_numeric($wholesalePrices[$i]))
                                $wholesalePrices[$i] = '';
                            elseif($wholesalePrices[$i] < 0)
                                $wholesalePrices[$i] = 0;
                            else
                                $wholesalePrices[$i] = wc_format_decimal($wholesalePrices[$i]);
                        }

                        $wholesalePrices[ $i ] = wc_clean( apply_filters( 'wwp_filter_before_save_wholesale_price' , $wholesalePrices[ $i ] , $roleKey , $variation_id , 'variation' ) );
                        update_post_meta( $variation_id, $roleKey.'_wholesale_price', $wholesalePrices[ $i ] );

                        // If it has a valid wholesale price, attach a meta to the parent product that specifies
                        // what are the variation id of the variations that has valid wholesale price
                        // Note: per role
                        if ( is_numeric( $wholesalePrices[$i] ) && $wholesalePrices[$i] > 0 )
                            add_post_meta( $_POST[ 'post_ID' ] , $roleKey . '_variations_with_wholesale_price' , $variation_id );

                    }
                }

            }

            // Universal meta to use to mark if a product ( variable or not ) has a valid wholesale price
            // If product is variable, if even only one variation has a valid product price, the parent product is automatically
            // marked as having a valid wholesale price.
            foreach( $registeredCustomRoles as $roleKey => $role ) {

                $postMeta = get_post_meta( $_POST[ 'post_ID' ] , $roleKey . '_variations_with_wholesale_price' );

                if ( !empty( $postMeta ) )
                    update_post_meta( $_POST[ 'post_ID' ] , $roleKey . '_have_wholesale_price' , 'yes' );
                else {

                    update_post_meta( $_POST[ 'post_ID' ] , $roleKey . '_have_wholesale_price' , 'no' );

                    $terms = get_the_terms( $_POST[ 'post_ID' ] , 'product_cat' );
                    if ( !is_array( $terms ) )
                        $terms = array();

                    foreach ( $terms as $term ) {

                        $category_wholesale_prices = get_option( 'taxonomy_' . $term->term_id );

                        if ( is_array( $category_wholesale_prices ) && array_key_exists( $roleKey . '_wholesale_discount' , $category_wholesale_prices ) ) {

                            $curr_discount = $category_wholesale_prices[ $roleKey . '_wholesale_discount' ];

                            if ( !empty( $curr_discount ) ) {

                                update_post_meta( $_POST[ 'post_ID' ] , $roleKey . '_have_wholesale_price' , 'yes' );
                                break;

                            }

                        }

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
    public function addCustomWholesaleFieldsOnQuickEditScreen( $registeredCustomRoles ) {
        ?>

        <div class="quick_edit_wholesale_prices" style="float: none; clear: both; display: block;">
            <h4><?php _e( 'Wholesale Price', 'woocommerce-wholesale-prices' ); ?></h4>

            <?php
            foreach ( $registeredCustomRoles as $roleKey => $role ) {

                $currencySymbol = get_woocommerce_currency_symbol();
                if( array_key_exists( 'currency_symbol' , $role ) && !empty( $role[ 'currency_symbol' ] ) )
                    $currencySymbol = $role['currency_symbol']; ?>

                <label class="alignleft" style="width: 100%;">
                    <div class="title"><?php echo sprintf( __( '%1$s Price (%2$s)' , 'woocommerce-wholesale-prices' ) , $role[ 'roleName' ] , $currencySymbol ); ?></div>
                    <input type="text" name="<?php echo $roleKey; ?>_wholesale_price" class="text wholesale_price wc_input_price" value="">
                </label>

            <?php } ?>
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
    public function saveCustomWholesaleFieldsOnQuickEditScreen( $product , $registeredCustomRoles ) {

        if ( $product->is_type( 'simple' ) || $product->is_type( 'external' ) ) {

            $post_id = $product->id;

            foreach ( $registeredCustomRoles as $roleKey => $role ) {

                if ( isset( $_REQUEST[ $roleKey . '_wholesale_price' ] ) ) {

                    $wholesalePrice = trim( esc_attr( $_REQUEST[ $roleKey . '_wholesale_price' ] ) );

                    $thousand_sep = get_option( 'woocommerce_price_thousand_sep' );
                    $decimal_sep = get_option( 'woocommerce_price_decimal_sep' );

                    if ( $thousand_sep )
                        $wholesalePrice = str_replace( $thousand_sep , '' , $wholesalePrice );

                    if ( $decimal_sep )
                        $wholesalePrice = str_replace( $decimal_sep , '.' , $wholesalePrice );

                    if ( !empty( $wholesalePrice ) ) {

                        if( !is_numeric( $wholesalePrice ) )
                            $wholesalePrice = '';
                        elseif( $wholesalePrice < 0 )
                            $wholesalePrice = 0;
                        else
                            $wholesalePrice = wc_format_decimal( $wholesalePrice );

                        if ( is_numeric( $wholesalePrice ) && $wholesalePrice > 0 )
                            update_post_meta( $post_id , $roleKey . '_have_wholesale_price' , 'yes' );
                        else
                            update_post_meta( $post_id , $roleKey . '_have_wholesale_price' , 'no' );

                    } else {

                        update_post_meta( $post_id , $roleKey . '_have_wholesale_price' , 'no' );

                        $terms = get_the_terms( $post_id , 'product_cat' );
                        if ( !is_array( $terms ) )
                            $terms = array();

                        foreach ( $terms as $term ) {

                            $category_wholesale_prices = get_option( 'taxonomy_' . $term->term_id );

                            if ( is_array( $category_wholesale_prices ) && array_key_exists( $roleKey . '_wholesale_discount' , $category_wholesale_prices ) ) {

                                $curr_discount = $category_wholesale_prices[ $roleKey . '_wholesale_discount' ];

                                if ( !empty( $curr_discount ) ) {

                                    update_post_meta( $post_id , $roleKey . '_have_wholesale_price' , 'yes' );
                                    break;

                                }

                            }

                        }

                    }

                    $wholesalePrice = wc_clean( apply_filters( 'wwp_filter_before_save_wholesale_price' , $wholesalePrice , $roleKey , $post_id , 'simple' ) );
                    update_post_meta( $post_id , $roleKey . '_wholesale_price' , $wholesalePrice );

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
    public function addCustomWholesaleFieldsMetaDataOnProductListingColumn( $column , $post_id , $registeredCustomRoles ) {

        switch ( $column ) {
            case 'name' : ?>

                <div class="hidden wholesale_prices_inline" id="wholesale_prices_inline_<?php echo $post_id; ?>">
                    <?php foreach ( $registeredCustomRoles as $roleKey => $role ) { ?>
                        <div id="<?php echo $roleKey; ?>_wholesale_price" class="whole_price"><?php echo wc_format_localized_price( get_post_meta( $post_id , $roleKey . '_wholesale_price' , true ) ); ?></div>
                    <?php } ?>
                </div>

                <?php
                break;

            default :
                break;
        }

    }

}

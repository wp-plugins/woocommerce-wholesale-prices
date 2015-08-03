<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class WWP_Wholesale_Prices {

    private static $_instance;

    public static function getInstance() {

        if( !self::$_instance instanceof self )
            self::$_instance = new self;

        return self::$_instance;

    }

    /**
     * Return product wholesale price for a given wholesale user role.
     *
     * @param $product_id
     * @param $userWholesaleRole
     *
     * @return string
     * @since 1.0.0
     */
    public function getUserProductWholesalePrice( $product_id , $userWholesaleRole ) {

        if ( empty( $userWholesaleRole ) ) {

            return '';

        } else {

            $wholesalePrice = get_post_meta( $product_id , $userWholesaleRole[0] . '_wholesale_price' , true );
            return apply_filters( 'wwp_filter_wholesale_price' , $wholesalePrice , $product_id , $userWholesaleRole );

        }

    }

    /**
     * Filter callback that alters the product price, it embeds the wholesale price of a product for a wholesale user.
     *
     * @param $price
     * @param $product
     * @param $userWholesaleRole
     *
     * @return mixed|string
     * @since 1.0.0
     */
    public function wholesalePriceHTMLFilter( $price , $product , $userWholesaleRole ) {

        if ( !empty( $userWholesaleRole ) ) {

            $wholesalePrice = '';

            if ( $product->product_type == 'simple' ) {

                $wholesalePrice = trim( $this->getUserProductWholesalePrice( $product->id , $userWholesaleRole ) );

                $wholesalePrice = apply_filters( 'wwp_filter_wholesale_price_shop' , $wholesalePrice , $product->id , $userWholesaleRole );

                if ( strcasecmp( $wholesalePrice , '' ) != 0 )
                    $wholesalePrice = wc_price( $wholesalePrice ) . $product->get_price_suffix();

            } elseif ( $product->product_type == 'variable' ) {

                $variations = $product->get_available_variations();
                $minPrice = '';
                $maxPrice = '';
                $someVariationsHaveWholesalePrice = false;

                foreach ( $variations as $variation ) {

                    if ( !$variation[ 'is_purchasable' ] )
                        continue;

                    $variation = wc_get_product( $variation[ 'variation_id' ] );

                    $currVarWholesalePrice = trim( $this->getUserProductWholesalePrice( $variation->variation_id , $userWholesaleRole ) );

                    $currVarWholesalePrice = apply_filters( 'wwp_filter_wholesale_price_shop' , $currVarWholesalePrice, $variation->variation_id, $userWholesaleRole );

                    $currVarPrice = $variation->get_display_price();

                    if ( strcasecmp( $currVarWholesalePrice , '' ) != 0 ) {
                        $currVarPrice = $currVarWholesalePrice;

                        if ( !$someVariationsHaveWholesalePrice )
                            $someVariationsHaveWholesalePrice = true;
                    }

                    if ( strcasecmp( $minPrice , '' ) == 0 || $currVarPrice < $minPrice )
                        $minPrice = $currVarPrice;

                    if ( strcasecmp( $maxPrice , '' ) == 0 || $currVarPrice > $maxPrice )
                        $maxPrice = $currVarPrice;

                }

                // Only alter price html if, some/all variations of this variable product have sale price and
                // min and max price have valid values
                if( $someVariationsHaveWholesalePrice && strcasecmp( $minPrice , '' ) != 0 && strcasecmp( $maxPrice , '' ) != 0 ) {

                    if ( $minPrice != $maxPrice && $minPrice < $maxPrice ) {
                        $wholesalePrice = wc_price( $minPrice ) . $product->get_price_suffix() . ' - ';
                        $wholesalePrice .= wc_price( $maxPrice ) . $product->get_price_suffix();
                    } else
                        $wholesalePrice = wc_price( $maxPrice ) . $product->get_price_suffix();

                }

                $wholesalePrice = apply_filters( 'wwp_filter_variable_product_wholesale_price_range' , $wholesalePrice , $price , $product , $userWholesaleRole , $minPrice , $maxPrice );

            }

            if ( strcasecmp( $wholesalePrice , '' ) != 0 ) {

                // Crush out existing prices, regular and sale
                if ( strpos( $price , 'ins') !== false ) {
                    $wholesalePriceHTML = str_replace( 'ins' , 'del' , $price );
                } else {
                    $wholesalePriceHTML = str_replace( '<span' , '<del><span' , $price );
                    $wholesalePriceHTML = str_replace( '</span>' , '</span></del>' , $wholesalePriceHTML );
                }

                $wholesalePriceTitleText = __( 'Wholesale Price:' , 'woocommerce-wholesale-prices' );
                $wholesalePriceTitleText = apply_filters( 'wwp_filter_wholesale_price_title_text' , $wholesalePriceTitleText );

                $wholesalePriceHTML .= '<div class="wholesale_price_container">
                                            <span class="wholesale_price_title">'.$wholesalePriceTitleText.'</span>
                                            <ins>' . $wholesalePrice . '</ins>
                                        </div>';

                return apply_filters( 'wwp_filter_wholesale_price_html' , $wholesalePriceHTML , $price , $product , $userWholesaleRole );

            }

        }

        // Only do this, if WooCommerce Wholesale Prices Premium plugin is installed
        if ( in_array( 'woocommerce-wholesale-prices-premium/woocommerce-wholesale-prices-premium.bootstrap.php' , apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {

            // Variable product price range calculation for none wholesale users -------------------------------------------

            // Fix for the product price range if some variations are only to be displayed to certain wholesale roles
            // If code below is not present, woocommerce will include in the min and max price calculation the variations
            // that are not supposed to be displayed outside the set exclusive wholesale roles.
            // Therefore giving misleading min and max price range.
            if ( $product->product_type == 'variable' ) {

                $variations = $product->get_available_variations();

                $regularPriceRange = $this->_generateRegularVariableProductPriceRange( $product , $variations , 'regular' );
                $salePriceRange = $this->_generateRegularVariableProductPriceRange( $product , $variations , 'sale' );

                if ( $salePriceRange[ 'has_sale_price' ] )
                    $price = '<del>' . $regularPriceRange[ 'price_range' ] . '</del> <ins>' . $salePriceRange[ 'price_range' ] . '</ins>';
                else
                    $price = $regularPriceRange[ 'price_range' ];

            }

        }

        return $price;

    }

    /**
     * The purpose for this helper function is to generate price range for none wholesale users for variable product.
     * You see, default WooCommerce calculations include all variations of a product to general min and max price range.
     *
     * Now some variations have filters to be only visible to certain wholesale users ( Set by WWPP ). But WooCommerce
     * Don't have an idea about this, so it will still include those variations to the min and max price range calculations
     * thus giving incorrect price range.
     *
     * This is the purpose of this function, to generate a correct price range that recognizes the custom visibility filter
     * of each variations.
     *
     * @param $product
     * @param $variations
     * @param string $range_type
     * @return array
     *
     * @since 1.0.9
     */
    private function _generateRegularVariableProductPriceRange ( $product , $variations , $range_type = 'regular' ) {

        $hasSalePrice = false;
        $minPrice = '';
        $maxPrice = '';

        foreach( $variations as $variation ) {

            if ( !$variation[ 'is_purchasable' ] )
                continue;

            $variation = wc_get_product( $variation[ 'variation_id' ] );

            if ( $range_type == 'regular' )
                $currVarPrice = $variation->get_display_price( $variation->get_regular_price() );
            elseif ( $range_type == 'sale' ) {

                // No point of going forward if no sale price
                if ( !$hasSalePrice && $variation->get_regular_price() != $variation->get_price() )
                    $hasSalePrice = true;

                $currVarPrice = $variation->get_display_price( $variation->get_price() );

            }

            if( strcasecmp( $minPrice , '' ) == 0 || $currVarPrice < $minPrice )
                $minPrice = $currVarPrice;

            if( strcasecmp( $maxPrice , '' ) == 0 || $currVarPrice > $maxPrice )
                $maxPrice = $currVarPrice;

        }

        // Only alter price html if, some/all variations of this variable product have sale price and
        // min and max price have valid values
        if ( strcasecmp( $minPrice , '' ) != 0 && strcasecmp( $maxPrice , '' ) != 0 ) {

            if ( $minPrice != $maxPrice && $minPrice < $maxPrice ) {
                $priceRange =  wc_price( $minPrice ) . $product->get_price_suffix() . ' - ';
                $priceRange .= wc_price( $maxPrice ) . $product->get_price_suffix();
            } else {
                $priceRange = wc_price( $maxPrice ) . $product->get_price_suffix();
            }

        }

        $priceRange = apply_filters( 'wwp_filter_variable_product_price_range' , $priceRange , $product , $variations , $range_type , $minPrice , $maxPrice );

        return array(
                    'price_range'       =>  $priceRange,
                    'has_sale_price'    =>  $hasSalePrice
                );

    }

    /**
     * Apply wholesale price whenever "get_html_price" function gets called inside a variation product.
     * Variation product is the actual variation of a variable product.
     * Variable product is the parent product which contains variations.
     *
     * @param $price
     * @param $variation
     * @param $userWholesaleRole
     * @return mixed
     *
     * @since 1.0.3
     */
    public function wholesaleSingleVariationPriceHTMLFilter ( $price , $variation , $userWholesaleRole ) {

        if( !empty( $userWholesaleRole ) ) {

            $currVarWholesalePrice = trim( $this->getUserProductWholesalePrice( $variation->variation_id , $userWholesaleRole ) );
            $currVarWholesalePrice = apply_filters( 'wwp_filter_wholesale_price_shop' , $currVarWholesalePrice , $variation->variation_id , $userWholesaleRole );

            $currVarPrice = $variation->price;

            if($variation->is_on_sale())
                $currVarPrice = $variation->get_sale_price();

            if(strcasecmp($currVarWholesalePrice,'') != 0)
                $currVarPrice = $currVarWholesalePrice;

            $wholesalePrice = wc_price( $currVarPrice ) . $variation->get_price_suffix();

            if ( strcasecmp( $currVarWholesalePrice , '' ) != 0 ) {

                // Crush out existing prices, regular and sale
                if ( strpos( $price , 'ins' ) !== false ) {
                    $wholesalePriceHTML = str_replace( 'ins' , 'del' , $price );
                } else {
                    $wholesalePriceHTML = str_replace( '<span' , '<del><span',$price );
                    $wholesalePriceHTML = str_replace( '</span>' , '</span></del>' , $wholesalePriceHTML );
                }

                $wholesalePriceTitleText = __( 'Wholesale Price:' , 'woocommerce-wholesale-prices' );
                $wholesalePriceTitleText = apply_filters( 'wwp_filter_wholesale_price_title_text' , $wholesalePriceTitleText );

                $wholesalePriceHTML .= '<div class="wholesale_price_container">
                                            <span class="wholesale_price_title">'.$wholesalePriceTitleText.'</span>
                                            <ins>' . $wholesalePrice . '</ins>
                                        </div>';

                return apply_filters( 'wwp_filter_wholesale_price_html' , $wholesalePriceHTML , $price , $variation , $userWholesaleRole );

            } else {

                // If wholesale price is empty (""), means that this product has no wholesale price set
                // Just return the regular price
                return $price;

            }

        } else {

            // If $userWholeSaleRole is an empty array, meaning current user is not a wholesale customer,
            // just return original $price html
            return $price;

        }

    }

    /**
     * Apply product wholesale price upon adding to cart.
     *
     * @param $cart_object
     * @param $userWholesaleRole
     *
     * @since 1.0.0
     */
    public function applyProductWholesalePrice ( $cart_object , $userWholesaleRole ) {

        $apply_wholesale_price = $this->checkIfApplyWholesalePrice( $cart_object , $userWholesaleRole );

        if ( !empty( $userWholesaleRole ) && $apply_wholesale_price === true ) {

            foreach ( $cart_object->cart_contents as $cart_item_key => $value ) {

                $apply_wholesale_price_product_level = $this->checkIfApplyWholesalePricePerProductLevel( $value , $cart_object , $userWholesaleRole );

                if ( $apply_wholesale_price_product_level === true ) {

                    $wholesalePrice = '';
                    if ( $value['data']->product_type == 'simple' ) {

                        $wholesalePrice = trim( $this->getUserProductWholesalePrice( $value['data']->id , $userWholesaleRole ) );
                        $wholesalePrice = apply_filters( 'wwp_filter_wholesale_price_cart' , $wholesalePrice , $value[ 'data' ]->id , $userWholesaleRole , $value );

                    } elseif ( $value['data']->product_type == 'variation' ) {

                        $wholesalePrice = trim( $this->getUserProductWholesalePrice( $value['data']->variation_id , $userWholesaleRole ) );
                        $wholesalePrice = apply_filters( 'wwp_filter_wholesale_price_cart' , $wholesalePrice , $value[ 'data' ]->variation_id , $userWholesaleRole , $value );

                    }

                    if( strcasecmp ( $wholesalePrice , '' ) != 0 ) {

                        do_action( 'wwp_action_before_apply_wholesale_price' , $wholesalePrice );
                        $value['data']->price = $wholesalePrice;

                    }

                } else
                    if ( is_cart() )
                        $this->printWCNotice( $apply_wholesale_price_product_level );

            }

        } else
            if ( is_cart() )
                $this->printWCNotice( $apply_wholesale_price );

    }

    /**
     * Add notice to WC Widget if the user (wholesale user) fails to avail the wholesale price requirements.
     * Only applies to wholesale users.
     *
     * @param $userWholesaleRole
     *
     * @since 1.0.0
     */
    public function beforeWCWidget ( $userWholesaleRole ) {

        // We have to explicitly call this.
        // You see, WC Widget uses get_sub_total() to for its total field displayed on the widget.
        // This function gets only synced once calculate_totals() is triggered.
        // calculate_totals() is only triggered on the cart and checkout page.
        // So if we don't trigger calculate_totals() manually, there will be a scenario where the cart widget total isn't
        // synced with the cart page total. The user will have to go to the cart page, which triggers calculate_totals,
        // which synced get_sub_total(), for the user to have the cart widget synched the price.
        WC()->cart->calculate_totals();

        $applyWholesalePrice = $this->checkIfApplyWholesalePrice( WC()->cart , $userWholesaleRole );

        // Only display notice if user is a wholesale user.
        if ( !empty( $userWholesaleRole ) && $applyWholesalePrice === true ) {

            foreach ( WC()->cart->get_cart() as $cart_item_key => $values ) {

                $apply_wholesale_price_product_level = $this->checkIfApplyWholesalePricePerProductLevel( $values , WC()->cart , $userWholesaleRole );

                if ( $apply_wholesale_price_product_level !== true )
                    $this->printWCNotice( $apply_wholesale_price_product_level );

            }

        } else
            $this->printWCNotice( $applyWholesalePrice );

    }

    /**
     * Apply wholesale price on WC Cart Widget.
     *
     * @param $product_price
     * @param $cart_item
     * @param $cart_item_key
     * @param $userWholesaleRole
     * @return mixed
     *
     * @since 1.0.0
     */
    public function applyProductWholesalePriceOnDefaultWCCartWidget ( $product_price , $cart_item , $cart_item_key ,  $userWholesaleRole ) {

        $apply_wholesale_price = $this->checkIfApplyWholesalePrice( WC()->cart , $userWholesaleRole );

        if ( !empty( $userWholesaleRole ) && $apply_wholesale_price === true ) {

            $apply_wholesale_price_product_level = $this->checkIfApplyWholesalePricePerProductLevel( $cart_item , WC()->cart , $userWholesaleRole );

            if ( $apply_wholesale_price_product_level === true ) {

                $wholesalePrice = '';

                if ( $cart_item[ 'data' ]->product_type == 'simple' ) {

                    $wholesalePrice = trim( $this->getUserProductWholesalePrice( $cart_item[ 'data' ]->id , $userWholesaleRole ) );
                    $wholesalePrice = apply_filters( 'wwp_filter_wholesale_price_cart' , $wholesalePrice , $cart_item[ 'data' ]->id , $userWholesaleRole , $cart_item );

                } elseif ( $cart_item['data']->product_type == 'variation' ) {

                    $wholesalePrice = trim( $this->getUserProductWholesalePrice( $cart_item[ 'data' ]->variation_id , $userWholesaleRole ) );
                    $wholesalePrice = apply_filters( 'wwp_filter_wholesale_price_cart' , $wholesalePrice , $cart_item[ 'data' ]->variation_id , $userWholesaleRole , $cart_item );

                }

                if( strcasecmp( $wholesalePrice , '' ) != 0 ) {

                    do_action( 'wwp_action_before_apply_wholesale_price' , $wholesalePrice );
                    return wc_price( $wholesalePrice );

                }

            }

        }

        return $product_price;

    }

    /**
     * Check if we are good to apply wholesale price. Returns boolean true if we are ok to apply it.
     * Else returns an array of error message.
     *
     * @param $cart_object
     * @param $userWholesaleRole
     * @return bool
     *
     * @since 1.0.0
     */
    public function checkIfApplyWholesalePrice ( $cart_object , $userWholesaleRole ) {

        $apply_wholesale_price = true;
        $apply_wholesale_price = apply_filters( 'wwp_filter_apply_wholesale_price_flag' , $apply_wholesale_price , $cart_object , $userWholesaleRole );
        return $apply_wholesale_price;

    }

    /**
     * Check if we are good to apply wholesale price per product basis.
     *
     * @param $value
     * @param $cart_object
     * @param $userWholesaleRole
     * @return bool
     *
     * @since 1.0.7
     */
    public function checkIfApplyWholesalePricePerProductLevel ( $value , $cart_object , $userWholesaleRole ) {

        $apply_wholesale_price = true;
        $apply_wholesale_price = apply_filters( 'wwp_filter_apply_wholesale_price_per_product_basis' , $apply_wholesale_price , $value , $cart_object , $userWholesaleRole );
        return $apply_wholesale_price;

    }

    /**
     * Print WP Notices.
     *
     * @param $notices
     *
     * @since 1.0.7
     */
    public function printWCNotice ( $notices ) {

        if ( is_array( $notices ) && array_key_exists( 'message' , $notices ) && array_key_exists( 'type' , $notices ) ) {
            // Pre Version 1.2.0 of wwpp where it sends back single dimension array of notice

            // Print notice why a wholesale user is not qualified for a wholesale price
            // Only print on cart page ( Have some side effects when printed on checkout page )
            wc_print_notice( $notices[ 'message' ] , $notices[ 'type' ] );

        } elseif ( is_array( $notices ) ) {
            // Version 1.2.0 of wwpp where it sends back multiple notice via multi dimensional arrays

            foreach ( $notices as $notice ) {

                if ( array_key_exists( 'message' , $notice ) && array_key_exists( 'type' , $notice ) )
                    wc_print_notice( $notice[ 'message' ] , $notice[ 'type' ] );

            }

        }

    }

}
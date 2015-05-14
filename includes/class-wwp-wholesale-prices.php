<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class WWP_Wholesale_Prices {

    private static $_instance;

    public static function getInstance(){
        if(!self::$_instance instanceof self)
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
    public function getUserProductWholesalePrice($product_id, $userWholesaleRole){

        if ( empty( $userWholesaleRole ) ) {

            return '';

        } else {

            $wholesalePrice = get_post_meta($product_id,$userWholesaleRole[0].'_wholesale_price',true);
            return apply_filters( 'wwp_filter_wholesale_price', $wholesalePrice, $product_id, $userWholesaleRole );

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
    public function wholesalePriceHTMLFilter($price, $product, $userWholesaleRole){

        if(!empty($userWholesaleRole)){

            $wholesalePrice = '';

            if ($product->product_type == 'simple') {

                $wholesalePrice = trim($this->getUserProductWholesalePrice($product->id,$userWholesaleRole));

                $wholesalePrice = apply_filters( 'wwp_filter_wholesale_price_shop' , $wholesalePrice, $product->id, $userWholesaleRole );

                if(strcasecmp($wholesalePrice,'') != 0)
                    $wholesalePrice = '<span class="amount">'.wc_price($wholesalePrice) . $product->get_price_suffix().'</span>';

            } elseif ($product->product_type == 'variable'){

                $variations = $product->get_available_variations();
                $minPrice = '';
                $maxPrice = '';
                $someVariationsHaveWholesalePrice = false;

                foreach($variations as $variation){

                    $variation = wc_get_product($variation['variation_id']);

                    $currVarWholesalePrice = trim($this->getUserProductWholesalePrice($variation->variation_id,$userWholesaleRole));

                    $currVarWholesalePrice = apply_filters( 'wwp_filter_wholesale_price_shop' , $currVarWholesalePrice, $variation->variation_id, $userWholesaleRole );

                    $currVarPrice = $variation->price;

                    if($variation->is_on_sale())
                        $currVarPrice = $variation->get_sale_price();

                    if(strcasecmp($currVarWholesalePrice,'') != 0){
                        $currVarPrice = $currVarWholesalePrice;

                        if(!$someVariationsHaveWholesalePrice)
                            $someVariationsHaveWholesalePrice = true;
                    }

                    if(strcasecmp($minPrice,'') == 0 || $currVarPrice < $minPrice)
                        $minPrice = $currVarPrice;

                    if(strcasecmp($maxPrice,'') == 0 || $currVarPrice > $maxPrice)
                        $maxPrice = $currVarPrice;

                }

                // Only alter price html if, some/all variations of this variable product have sale price and
                // min and max price have valid values
                if($someVariationsHaveWholesalePrice && strcasecmp($minPrice,'') != 0 && strcasecmp($maxPrice,'') != 0){

                    if($minPrice != $maxPrice && $minPrice < $maxPrice){
                        $wholesalePrice = '<span class="amount">'.wc_price($minPrice) . $product->get_price_suffix().'</span> - ';
                        $wholesalePrice .= '<span class="amount">'.wc_price($maxPrice) . $product->get_price_suffix().'</span>';
                    }else{
                        $wholesalePrice = '<span class="amount">'.wc_price($maxPrice) . $product->get_price_suffix().'</span>';
                    }

                }

            }

            if(strcasecmp($wholesalePrice,'') != 0){

                // Crush out existing prices, regular and sale
                if (strpos($price,'ins') !== false){
                    $wholesalePriceHTML = str_replace('ins','del',$price);
                }else{
                    $wholesalePriceHTML = str_replace('<span','<del><span',$price);
                    $wholesalePriceHTML = str_replace('</span>','</span></del>',$wholesalePriceHTML);
                }

                $wholesalePriceTitleText = 'Wholesale Price:';
                $wholesalePriceTitleText = apply_filters('wwp_filter_wholesale_price_title_text',$wholesalePriceTitleText);

                $wholesalePriceHTML .= '<div class="wholesale_price_container">
                                            <span class="wholesale_price_title">'.$wholesalePriceTitleText.'</span>
                                            <ins>' . $wholesalePrice . '</ins>
                                        </div>';

                return apply_filters( 'wwp_filter_wholesale_price_html', $wholesalePriceHTML, $price, $product, $userWholesaleRole );

            }else{

                // If wholesale price is empty (""), means that this product has no wholesale price set
                // Just return the regular price
                return $price;

            }

        }else{

            // If $userWholeSaleRole is an empty array, meaning current user is not a wholesale customer,
            // just return original $price html
            return $price;

        }

    }

    /**
     * Filter to append wholesale price on variations of a variable product on single product page.
     *
     * @param $available_variations
     * @param $userWholesaleRole
     *
     * @return mixed
     * @since 1.0.0
     */
    public function wholesaleVariationPriceHTMLFilter($available_variations,$userWholesaleRole){

        $variation = wc_get_product($available_variations['variation_id']);

        $wholesalePrice = trim($this->getUserProductWholesalePrice($variation->variation_id,$userWholesaleRole));

        $wholesalePrice = apply_filters( 'wwp_filter_wholesale_price_shop' , $wholesalePrice, $variation->variation_id, $userWholesaleRole );

        if(strcasecmp($wholesalePrice,'') != 0){

            $price_html = $available_variations['price_html'];

            if (strpos($price_html,'ins') !== false){
                $price_html = str_replace('ins','del',$price_html);
            }else{
                $price_html = str_replace('<span class="amount">','<del><span class="amount">',$price_html);
                $price_html = str_replace('</span></span>','</span></del></span>',$price_html);
            }

            $wholesalePriceTitleText = 'Wholesale Price:';
            $wholesalePriceTitleText = apply_filters('wwp_filter_wholesale_price_title_text',$wholesalePriceTitleText);

            $price_html .=  '<div class="wholesale_price_container">';
            $price_html .=      '<span class="wholesale_price_title">'.$wholesalePriceTitleText.'</span>';
            $price_html .=      '<span class="price"><span class="amount">'.wc_price($wholesalePrice) . $variation->get_price_suffix().'</span></span>';
            $price_html .=  '</div>';

            $price_html =  apply_filters( 'wwp_filter_variation_wholesale_price_html', $price_html, $available_variations, $userWholesaleRole );

            $available_variations['price_html'] = $price_html;
        }

        return $available_variations;

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

        do_action('wwp_action_before_apply_wholesale_price_cart_loop', $apply_wholesale_price, $cart_object, $userWholesaleRole);

        if ( !empty( $userWholesaleRole ) && $apply_wholesale_price === true ) {

            foreach ( $cart_object->cart_contents as $cart_item_key => $value ) {

                $wholesalePrice = '';
                if($value['data']->product_type == 'simple'){

                    $wholesalePrice = trim($this->getUserProductWholesalePrice($value['data']->id,$userWholesaleRole));
                    $wholesalePrice = apply_filters( 'wwp_filter_wholesale_price_cart' , $wholesalePrice, $value['data']->id, $userWholesaleRole );

                } elseif ($value['data']->product_type == 'variation'){

                    $wholesalePrice = trim($this->getUserProductWholesalePrice($value['data']->variation_id,$userWholesaleRole));
                    $wholesalePrice = apply_filters( 'wwp_filter_wholesale_price_cart' , $wholesalePrice, $value['data']->variation_id, $userWholesaleRole );

                }

                if(strcasecmp($wholesalePrice,'') != 0){

                    do_action('wwp_action_before_apply_wholesale_price',$wholesalePrice);

                    $value['data']->price = $wholesalePrice;
                }

            }

        } else {

            if ( is_cart() ) {

                if ( is_array( $apply_wholesale_price ) && array_key_exists( 'message' , $apply_wholesale_price ) && array_key_exists( 'type' , $apply_wholesale_price ) ) {

                    // Print notice why a wholesale user is not qualified for a wholesale price
                    // Only print on cart page ( Have some side effects when printed on checkout page )
                    wc_print_notice( $apply_wholesale_price['message'] , $apply_wholesale_price['type'] );

                }

            }

        }

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
        if ( $applyWholesalePrice !== true && !empty( $userWholesaleRole ) ) {

            if ( is_array( $applyWholesalePrice ) && array_key_exists( 'message' , $applyWholesalePrice ) && array_key_exists( 'type' , $applyWholesalePrice ) ) {

                // Print notice why a wholesale user is not qualified for a wholesale price
                wc_print_notice( $applyWholesalePrice['message'] , $applyWholesalePrice['type'] );

            }

        }

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

        if ( $this->checkIfApplyWholesalePrice( WC()->cart , $userWholesaleRole ) === true ) {

            $wholesalePrice = '';

            if ( $cart_item[ 'data' ]->product_type == 'simple' ) {

                $wholesalePrice = trim( $this->getUserProductWholesalePrice( $cart_item[ 'data' ]->id , $userWholesaleRole ) );
                $wholesalePrice = apply_filters( 'wwp_filter_wholesale_price_cart' , $wholesalePrice , $cart_item[ 'data' ]->id , $userWholesaleRole );

            } elseif ( $cart_item['data']->product_type == 'variation' ) {

                $wholesalePrice = trim( $this->getUserProductWholesalePrice( $cart_item[ 'data' ]->variation_id , $userWholesaleRole ) );
                $wholesalePrice = apply_filters( 'wwp_filter_wholesale_price_cart' , $wholesalePrice , $cart_item[ 'data' ]->variation_id , $userWholesaleRole );

            }

            if( strcasecmp( $wholesalePrice , '' ) != 0 ) {

                do_action( 'wwp_action_before_apply_wholesale_price' , $wholesalePrice );
                return wc_price( $wholesalePrice );

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

}
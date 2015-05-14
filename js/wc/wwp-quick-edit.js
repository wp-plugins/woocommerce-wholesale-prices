jQuery(function(){
    jQuery('#the-list').on('click', '.editinline', function(){

        /**
         * Bottom Line:
         *
         * We add the wholesale custom fields metadata on the product listing column (on the name column) - Added via php
         * We extract the metadata value - We do it here
         * We populate the value to the wholesale custom form fields on the quick edit option - We do it here
         */


        /**
         * Extract the wholesale price custom field data and put it as a value for the wholesale price custom form field
         */
        inlineEditPost.revert();

        var post_id = jQuery(this).closest('tr').attr('id');

        post_id = post_id.replace("post-", "");

        var $wwop_inline_data = jQuery('#wholesale_prices_inline_' + post_id),
            $wc_inline_data = jQuery('#woocommerce_inline_' + post_id );

        $wwop_inline_data.find(".whole_price")
            .each(function(index){

                jQuery('input[name="'+jQuery(this).attr('id')+'"]', '.inline-edit-row').val(jQuery(this).text());

            });

        /**
         * Only show wholesale price custom field for appropriate types of products (simple)
         */
        var product_type = $wc_inline_data.find('.product_type').text();

        if (product_type=='simple' || product_type=='external') {
            jQuery('.quick_edit_wholesale_prices', '.inline-edit-row').show();
        } else {
            jQuery('.quick_edit_wholesale_prices', '.inline-edit-row').hide();
        }

    });
});

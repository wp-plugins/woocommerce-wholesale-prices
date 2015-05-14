<?php
/**
 * Woocommerce Wholesale Prices Settings
 *
 * @author      Rymera Web
 * @version     1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

if ( ! class_exists( 'WWP_Settings' ) ) {

    class WWP_Settings extends WC_Settings_Page {

        /**
         * Constructor.
         */
        public function __construct() {

            $this->id    = 'wwp_settings';
            $this->label = __( 'Wholesale Prices', 'woocommerce-wholesale-prices' );

            add_filter( 'woocommerce_settings_tabs_array', array( $this, 'add_settings_page' ), 30 ); // 30 so it is after the emails tab
            add_action( 'woocommerce_settings_' . $this->id, array( $this, 'output' ) );
            add_action( 'woocommerce_settings_save_' . $this->id, array( $this, 'save' ) );
            add_action( 'woocommerce_sections_' . $this->id, array( $this, 'output_sections' ) );

        }

        /**
         * Get sections.
         *
         * @return array
         * @since 1.0.0
         */
        public function get_sections() {

            $generalSettingsSectionTitle = __( '' , 'woocommerce-wholesale-prices' );

            $sections = array(
                            ''  =>  apply_filters( 'wwp_filter_settings_general_section_title' , $generalSettingsSectionTitle )
                        );

            $sections = apply_filters( 'wwp_filter_settings_sections' , $sections );

            return apply_filters( 'woocommerce_get_sections_' . $this->id, $sections );

        }

        /**
         * Output the settings.
         *
         * @since 1.0.0
         */
        public function output() {

            global $current_section;

            $settings = $this->get_settings( $current_section );
            WC_Admin_Settings::output_fields( $settings );

        }

        /**
         * Save settings.
         *
         * @since 1.0.0
         */
        public function save() {

            global $current_section;

            $settings = $this->get_settings( $current_section );
            WC_Admin_Settings::save_fields( $settings );

        }

        /**
         * Get settings array.
         *
         * @param string $current_section
         *
         * @return mixed
         * @since 1.0.0
         */
        public function get_settings( $current_section = '' ) {

            $settings = array();
            $settings = apply_filters( 'wwof_settings_section_content' , $settings, $current_section );

            return apply_filters( 'woocommerce_get_settings_' . $this->id, $settings, $current_section );

        }

    }

}

return new WWP_Settings();
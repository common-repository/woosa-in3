<?php
/**
 * This is responsible for enqueuing JS and CSS files
 *
 * @since 1.0.0
 */

namespace Woosa_IN3;


//prevent direct access data leaks
defined( 'ABSPATH' ) || exit;


class Assets{


   /**
    * Enqueue files
    *
    * @since 1.0.0
    * @return void
    */
   public static function enqueue(){

      //---- CSS
      add_action('admin_enqueue_scripts', __CLASS__ . '::admin_styles', 9999);
      add_action('wp_enqueue_scripts', __CLASS__ . '::frontend_styles', 9999);

      //---- JS
      add_action('admin_enqueue_scripts', __CLASS__ . '::admin_scripts', 9999);
      add_action('wp_enqueue_scripts', __CLASS__ . '::frontend_scripts', 9999);
   }



   /**
    * Enqueue styles in admin area
    *
    * @since 1.0.0
    * @return void
    */
   public static function admin_styles(){

      wp_enqueue_style(
         __NAMESPACE__ . '_admin',
         PLUGIN_URL .'/assets/css/admin.min.css',
         array(),
         PLUGIN_VERSION
      );

   }



   /**
    * Enqueue styles in frontend
    *
    * @since 1.0.0
    * @return void
    */
   public static function frontend_styles(){

      wp_enqueue_style(
         __NAMESPACE__ . '_jquery-ui-css',
         PLUGIN_URL .'/assets/css/jquery-ui.css',
         array(),
         PLUGIN_VERSION
      );

      wp_enqueue_style(
         __NAMESPACE__ . '_frontend',
         PLUGIN_URL .'/assets/css/frontend.min.css',
         array(),
         PLUGIN_VERSION
      );

   }



   /**
    * Enqueue scripts in admin area
    *
    * @since 1.0.0
    * @return void
    */
    public static function admin_scripts(){

      wp_enqueue_script(
         __NAMESPACE__ . '_admin',
         PLUGIN_URL .'/assets/js/admin.min.js',
         array('jquery'),
         PLUGIN_VERSION,
         true
      );
   }



   /**
    * Enqueue scripts in frontend
    *
    * @since 1.0.0
    * @return void
    */
   public static function frontend_scripts(){

      wp_register_script(
         __NAMESPACE__ . '_frontend',
         PLUGIN_URL .'/assets/js/frontend.min.js',
         array('jquery'),
         PLUGIN_VERSION,
         true
      );

      wp_enqueue_script('jquery-ui-datepicker');

      wp_enqueue_script(
         __NAMESPACE__ . '_frontend',
         PLUGIN_URL .'/assets/js/frontend.min.js',
         array('jquery'),
         PLUGIN_VERSION,
         true
      );

      if(is_checkout()){
         wp_enqueue_script(
            __NAMESPACE__ . '_checkout',
            PLUGIN_URL .'/assets/js/checkout.min.js',
            array('jquery'),
            PLUGIN_VERSION,
            true
         );
      }

      $settings = get_option('woocommerce_in3_settings', []);
      $custom_company_field = Utility::rgars($settings, 'company_name');

      wp_localize_script( __NAMESPACE__ . '_frontend', __NAMESPACE__, array(
         'ajax' => array(
            'url' => admin_url( 'admin-ajax.php' ),
            'nonce' => wp_create_nonce( 'wsa-nonce' )
         ),
         'prefix' => PREFIX,
         'translation' => array(
            'tooltip_msg' => __('Pay your first installments directly via iDeal. The second and third installment are paid after respectively 30 and 60 days. No interest, no BKR registrations.', 'woosa-in3'),
            'tooltip_info' => __('The first term will be paid via iDEAL the 2nd and 3rd term via invoice.', 'woosa-in3'),
         ),
         'in3_range' => array(
            'in3_min_price' => Gateway::min_price(),
            'in3_max_price' => Gateway::max_price(),
         ),
         'custom_company_field' => $custom_company_field,
      ));

   }

}
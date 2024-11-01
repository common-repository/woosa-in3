<?php
/**
 * This is responsible for processing AJAX or other requests
 *
 * @since 1.0.0
 */

namespace Woosa_IN3;


//prevent direct access data leaks
defined( 'ABSPATH' ) || exit;


class Requests{


   /**
    * This init AJAX listeners
    *
    * @since 1.0.0
    */
   public static function ajax(){

      // this is just a demo, please remove it (including `process_demo_func()`) when you work here!!!

      // add_action('wp_ajax_nopriv_demo_ajax_action', __CLASS__ . '::process_demo_func');
      // add_action('wp_ajax_demo_ajax_action', __CLASS__ . '::process_demo_func');
   }


   public static function process_demo_func(){

      //do your stuff
   }

}
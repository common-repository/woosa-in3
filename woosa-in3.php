<?php
/**
 * Plugin Name: Woosa - in3 payment for WooCommerce
 * Description: Accept payment in three (3) terms to your customers via your WooCommerce webshop.
 * Version: 1.3.1
 * Author: Woosa
 * Author URI:  https://woosa.nl
 * Text Domain: woosa-in3
 * Domain Path: /languages
 * Network: false
 *
 * WC requires at least: 3.5.0
 * WC tested up to: 3.5.4
 *
 * Copyright: © 2009-2015 WooCommerce.
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */


namespace Woosa_IN3;


//prevent direct access data leaks
defined( 'ABSPATH' ) || exit;


define(__NAMESPACE__ . '\PREFIX', 'in3');

define(__NAMESPACE__ . '\PLUGIN_VERSION', '1.3.1');

define(__NAMESPACE__ . '\PLUGIN_NAME', __('Woosa - in3 payment for WooCommerce', 'woosa-in3'));

define(__NAMESPACE__ . '\PRODUCT_ID', 'in3-for-woocommerce');

define(__NAMESPACE__ . '\PLUGIN_URL', untrailingslashit(plugin_dir_url(__FILE__)));

define(__NAMESPACE__ . '\PLUGIN_DIR', untrailingslashit(plugin_dir_path(__FILE__)));

define(__NAMESPACE__ . '\PLUGIN_BASENAME', plugin_basename(PLUGIN_DIR) . '/woosa-in3.php');

define(__NAMESPACE__ . '\PLUGIN_FOLDER', plugin_basename(PLUGIN_DIR));

define(__NAMESPACE__ . '\PLUGIN_INSTANCE', sanitize_title(crypt($_SERVER['SERVER_NAME'], $salt = PLUGIN_FOLDER)));

define(__NAMESPACE__ . '\PLUGIN_SETTINGS_URL', admin_url('admin.php?page=wc-settings&tab=checkout&section=in3'));

define(__NAMESPACE__ . '\ERROR_PATH', plugin_dir_path(__FILE__) . 'error.log');


//init
if(!class_exists( __NAMESPACE__ . '\Core')){
	include_once PLUGIN_DIR . '/includes/class-core.php';
}

register_activation_hook( __FILE__, __NAMESPACE__ . '\Core::on_activation');
register_deactivation_hook( __FILE__, __NAMESPACE__ . '\Core::on_deactivation');
register_uninstall_hook(__FILE__, __NAMESPACE__ . '\Core::on_uninstall');

//load translation, make sure this hook runs before all, so we set priority to 1
add_action('init', function(){
      load_textdomain( 'woosa-in3', plugin_dir_path(__FILE__) . 'languages/woosa-in3-' . get_locale() . '.mo' );
}, 1);
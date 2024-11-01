<?php
/**
 * Main class which sets all together
 *
 * @since 1.0.0
 */

namespace Woosa_IN3;


//prevent direct access data leaks
defined( 'ABSPATH' ) || exit;


class Core{

   /**
    * The instance of this class
    *
    * @since 1.0.0
    * @var null|object
    */
   protected static $instance = null;


	/**
	 * Return an instance of this class.
	 *
	 * @since     1.0.0
	 *
	 * @return    object    A single instance of this class.
	 */
	public static function instance() {

		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
   }



   /**
    * @since 1.1.0 - added background processes and events.
    * @since 1.0.0
    */
   public function __construct(){

      //autoload files from `/autoload`
      spl_autoload_register( __CLASS__ . '::autoload' );

      //check plugin dependencies
      if(self::has_dependency(array(
         'woocommerce/woocommerce.php' => 'WooCommerce',
      )) === false){
         return;
      }


      //include files from `/includes`
      self::includes();

      //enqueue css and js files
      Assets::enqueue();

      //process ajax requests
      Requests::ajax();

      //--- hooks
      self::run_hooks();

      //--- init background process handler
      Handle_Background_Process::instance();

      //--- add custom event actions
      Events::init_actions();



      add_action('init', __CLASS__.'::run_on_init');

   }



   /**
    * Runs after WordPress has finished loading but before any headers are sent
    *
    * @since 1.0.0
    * @return void
    */
   public static function run_on_init(){

      //add plugin action and meta links
      self::plugin_links(array(
         'actions' => array(
            PLUGIN_SETTINGS_URL => __('Settings', 'woosa-in3'),
            admin_url('admin.php?page=wc-status&tab=logs') => __('Logs', 'woosa-in3'),
         ),
         'meta' => array(
            // '#1' => __('Docs', 'woosa-in3'),
            // '#2' => __('Visit website', 'woosa-in3')
         ),
      ));

   }



   /**
    * Encrypts / decrypts given string
    *
    * @since 1.0.6
    * @param string $string
    * @param string $action - `e` for encryption and `d` for decryption
    * @return tring
    */
   public static function crypt( $string, $action = 'e' ) {

      $secret_key = PREFIX.'_key';
      $secret_iv = PREFIX.'_iv';

      $output = false;
      $encrypt_method = "AES-256-CBC";
      $key = hash( 'sha256', $secret_key );
      $iv = substr( hash( 'sha256', $secret_iv ), 0, 16 );

      if( $action == 'e' ) {
         $output = base64_encode( openssl_encrypt( $string, $encrypt_method, $key, 0, $iv ) );
      }
      else if( $action == 'd' ){
         $output = openssl_decrypt( base64_decode( $string ), $encrypt_method, $key, 0, $iv );
      }

      return $output;
   }



   /**
    * Include files
    *
    * @since 1.0.0
    * @return void
    */
   public static function includes(){

      include_once PLUGIN_DIR . '/vendor/autoload.php';

      include_once PLUGIN_DIR . '/includes/woocommerce/class-woocommerce.php';
   }



   /**
    * Runs general hooks.
    *
    * @since 1.1.0
    * @return void
    */
   public static function run_hooks(){

      add_filter('cron_schedules', [Events::class, 'cron_schedules']);

      add_action('upgrader_process_complete', [__CLASS__, 'on_update'], 10, 2);

      add_action( 'init', [ Order::class, 'register_manual_payment_check_order_status' ] );

      add_filter( 'wc_order_statuses',  [ Order::class, 'add_manual_payment_check_to_order_statuses'] );

      add_action('admin_head', [ Order::class, 'styling_admin_order_list'] );

   }


   /**
    * Get hostname of the site
    *
    * @since 1.0.0
    * @return void
    */
   public static function shop_name(){
      return parse_url(home_url())['host'];
   }



   /**
    * Get IP address
    *
    * @since 1.0.0
    * @return void
    */
   public static function get_ip() {

      $ip = 'undefined';

      if (isset($_SERVER)) {

         $ip = $_SERVER['REMOTE_ADDR'];
         if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
         elseif (isset($_SERVER['HTTP_CLIENT_IP'])) $ip = $_SERVER['HTTP_CLIENT_IP'];

      } else {
         $ip = getenv('REMOTE_ADDR');
         if (getenv('HTTP_X_FORWARDED_FOR')) $ip = getenv('HTTP_X_FORWARDED_FOR');
         elseif (getenv('HTTP_CLIENT_IP')) $ip = getenv('HTTP_CLIENT_IP');
      }

      $ip = htmlspecialchars($ip, ENT_QUOTES, 'UTF-8');

      return $ip;

   }



   /**
    * List of available countries
    *
    * @since 1.0.0
    * @return array
    */
   public static function available_countries(){
      return apply_filters(PREFIX.'_available_countries', array('NL'));
   }



   /**
    * Whether or not the given country is in the available list
    *
    * @since 1.0.0
    * @param string $country
    * @return boolean
    */
   public static function is_allowed_country($country){
      return in_array($country, self::available_countries());
   }



   /**
    * Get payment method settings
    *
    * @since 1.0.0
    * @param string $name
    * @return void
    */
   public static function get_setting($name, $default = ''){

      $settings = get_option('woocommerce_in3_settings', array());

      if(!empty($settings[$name])){
         return $settings[$name];
      }

      return $default;
   }



   /**
	 * Inlcude all files with "class-" prefix
	 *
    * @since 1.1.3 Removed GLOB_BRACE glob() match
    * @since 1.0.0
	 * @param  string $file_name
	 */
	public static function autoload($filename){

		$dir = PLUGIN_DIR . '/autoload/class-*.php';
		$paths = glob($dir);

		if( is_array($paths) && count($paths) > 0 ){
			foreach( $paths as $file ) {
				if ( file_exists( $file ) ) {
					include_once $file;
				}
			}
		}
   }



   /**
    * Add plugin action and meta links
    *
    * @since 1.0.0
    * @param array $sections
    * @return void
    */
   public static function plugin_links($sections = array()) {

      //actions
      if(isset($sections['actions'])){

         $actions = $sections['actions'];
         $links_hook = is_multisite() ? 'network_admin_plugin_action_links_' : 'plugin_action_links_';

         add_filter($links_hook.PLUGIN_BASENAME, function($links) use ($actions){

            foreach(array_reverse($actions) as $url => $label){
               $link = '<a href="'.esc_url( $url ).'">'.$label.'</a>';
               array_unshift($links, $link);
            }

            return $links;

         });
      }

      //meta row
      if(isset($sections['meta'])){

         $meta = $sections['meta'];

         add_filter( 'plugin_row_meta', function($links, $file) use ($meta){

            if(PLUGIN_BASENAME == $file){

               foreach($meta as $url => $label){
                  $link = '<a href="'.esc_url( $url ).'">'.$label.'</a>';
                  array_push($links, $link);
               }
            }

            return $links;

         }, 10, 2 );
      }

   }



   /**
    * Check whether the required dependencies are met
    *
    * @since 1.0.7
    * @param array $plugins - an array with `path => name` of the pplugin
    * @param boolean $show_msg
    * @return boolean
    */
   public static function has_dependency($plugins = array(), $show_msg = true){

      $valid = true;

      $active_plugins = self::get_active_plugins();

      foreach($plugins as $path => $name){

         if(!in_array($path, $active_plugins)){

            if($show_msg){
               Utility::show_notice(sprintf(
                  __('This plugin requires %s plugin to be installed and active!', 'woosa-in3'),
                  "<b>{$name}</b>"
               ), 'error');
            }

            $valid = false;
         }
      }

      return $valid;

   }



   /**
    * Get active plugins
    *
    * @since 1.0.7
    * @return array
    * */
   public static function get_active_plugins(){

      $active_plugins = apply_filters('active_plugins', get_option('active_plugins'));

      if (is_multisite()) {
         $active_sitewide_plugins = get_site_option('active_sitewide_plugins');

         foreach ($active_sitewide_plugins as $path => $item) {
            $active_plugins[] = $path;
         }
      }

      return $active_plugins;
   }



   /**
    * Clears all caches
    *
    * @since 1.0.8
    * @return void
    */
   public static function clear_all_caches(){

      //clear WP cache
      wp_cache_flush();

      //clear WP Rocket cache
      if( function_exists( 'rocket_clean_domain' ) ) {
         rocket_clean_domain();
      }

   }



   /**
    * Run on plugin activation
    *
    * @since 1.1.0 - added events schedule.
    * @since 1.0.0
    * @return void
    */
   public static function on_activation(){

      if(version_compare(phpversion(), '7.0', '<')){
         wp_die(sprintf(
            __('Your server must have at least PHP 7.0! Please upgrade! %sGo back%s', 'woosa-in3'),
            '<a href="'.admin_url('plugins.php').'">',
            '</a>'
         ));
      }

      if(version_compare(get_bloginfo('version'), '4.5', '<')){
         wp_die(sprintf(
            __('You need at least Wordpress 4.5! Please upgrade! %sGo back%s', 'woosa-in3'),
            '<a href="'.admin_url('plugins.php').'">',
            '</a>'
         ));
      }

      //Add background event schedule
      Events::schedule();
   }



   /**
    * Run on plugin deactivation
    *
    * @since 1.1.0 - added events clear.
    * @since 1.0.0
    * @return void
    */
   public static function on_deactivation(){

      self::clear_all_caches();

      //Clear plugin events
      Events::clear();
   }



   /**
    * Run on plugin update process
    *
    * @since 1.1.0
    * @param object $upgrader_object
    * @param array $options
    * @return void
    */
    public static function on_update( $upgrader_object, $options ) {

      if($options['action'] == 'update' && $options['type'] == 'plugin' ){

         foreach($options['plugins'] as $plugin){

            if($plugin == PLUGIN_BASENAME){

               //Add events schedule
               Events::schedule();

            }
         }
      }
   }




   /**
    * Run when plugin is deleting
    *
    * @since 1.0.0
    * @return void
    */
   public static function on_uninstall(){

      //payment settings
      delete_option('woocommerce_in3_settings');
   }


}
Core::instance();
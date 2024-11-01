<?php
/**
 * This is responsible for extending WooCommerce
 *
 * @since 1.0.0
 */

namespace Woosa_IN3;


//prevent direct access data leaks
defined( 'ABSPATH' ) || exit;


class Woocommerce{

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
    * @since 1.0.0
    */
   public function __construct(){

      add_action('init', __CLASS__.'::run_on_init');

      add_action('init', __CLASS__.'::process_order');

      add_action('woocommerce_cart_totals_order_total_html', __CLASS__.'::filter_cart_total_price');

      add_action('woocommerce_get_order_item_totals', __CLASS__.'::filter_order_item_totals', 99);

      //display the in3 option in product category page
      add_action( 'product_cat_edit_form_fields', [__CLASS__, 'product_cat_options'], 10, 2 );
      //save the category meta
      add_action( 'edit_product_cat', [__CLASS__, 'save_product_cat_options'] );
   }


   /**
    * Runs after WordPress has finished loading but before any headers are sent
    *
    * @since 1.0.0
    * @return void
    */
   public static function run_on_init(){

      include_once PLUGIN_DIR . '/includes/woocommerce/gateway/class-gateway.php';

      add_filter('woocommerce_payment_gateways', __CLASS__ . '::add_payment_gateway');
      add_filter('woocommerce_get_price_html', __CLASS__.'::filter_product_price', 10, 3);
   }



   /**
    * Add Mempay gateway to Woocommerce payments
    *
    * @since 1.0.0
    * @param array $gateways
    * @return void
    */
    public static function add_payment_gateway($gateways) {

      $gateways[] = __NAMESPACE__ . '\Gateway';

      return $gateways;
   }



   /**
    * Process order according to the payment status
    *
    * @since 1.0.3 - check status only from the API result
    * @since 1.0.0
    * @return void
    */
   public static function process_order(){

      if(is_admin()) return;


      if(isset($_GET[PREFIX.'_process'])){

         $id = (int) Core::crypt( sanitize_text_field( $_GET[PREFIX.'_process'] ), 'd' );
         $abandoned = Utility::rgar($_GET, PREFIX.'_abandoned');

         $order = wc_get_order($id);

         if($order instanceof \WC_Order){

            $page_id = 0;
            $transaction_number = get_post_meta($order->get_id(), PREFIX.'_transaction_number', true);
            $get_status = wp_remote_get(API::base_payment_url("payinstallment/status/{$transaction_number}"));
            $status = strtolower(Utility::rgar($get_status, 'body'));


            if(is_wp_error($get_status)){

               Utility::wc_error_log($get_status, __FILE__, __LINE__);

               $url = add_query_arg( array(
                  PREFIX.'_process' => Core::crypt( $order->get_id() ),
               ), home_url() );

               $order->add_order_note( sprintf(
                  __('An error has occurred while trying to get the payment status, %sclick here%s to try again manually', 'woosa-in3'),
                  '<a href="'.esc_url( $url ).'" target="_blank">',
                  '</a>'
               ));

            }else{

               if( '1' === $abandoned ){

                  $order->update_status('cancelled');
                  $order->add_order_note(__('The payment has been abandoned by the customer', 'woosa-in3'));

               }else{

                  if('paid' === $status){

                     $page_id = (int) Core::get_setting('paid_page_id');
                     $order->payment_complete();
                     $order->add_order_note(__('The payment has been done successfully', 'woosa-in3'));

                  }elseif('cancelled' === $status){

                     $page_id = (int) Core::get_setting('cancelled_page_id');
                     $order->update_status('cancelled');
                     $order->add_order_note(__('The payment has been cancelled by the customer', 'woosa-in3'));

                  }elseif('waiting' === $status){

                     $order->update_status('on-hold');
                     $order->add_order_note(__('We\'re waiting for the payment to be processed', 'woosa-in3'));

                  }
               }

            }

            $return_url = $page_id ? get_permalink($page_id) : $order->get_checkout_order_received_url();


            wp_redirect($return_url);
            exit;
         }
      }

      if(Utility::rgar($_GET, PREFIX.'_creditcheck') == 'unaccepted'){
         wp_die(__('This credit check is not accepted. Please contact us if this problem persists!', 'woosa-in3'));
      }
   }


   /**
    * Add price amount per term to the product
    *
    * @since 1.0.0
    * @param string $price
    * @param \WC_Product $product
    * @return string
    */
   public static function filter_product_price($price, $current_product){

      if( is_admin() || Core::get_setting('show_3terms_amount', 'yes') != 'yes') return $price;

      if ( self::product_category_in3_disabled( $current_product ) ) return $price;

      return self::show_offer( $price, $current_product );

   }



   /**
    * Check if the current product category disables the display of the in3 offer
    *
    * @since 1.2.1
    * @param WC_Product $current_product
    * @return boolean true - disable in3 offer display | false - don't disable in3 offer display
    */
   public static function product_category_in3_disabled( $current_product ) {
      $categories = wp_get_post_terms( $current_product->get_id(), 'product_cat', ['fields' => 'ids'] );
      foreach ( $categories as $category ) {
         if ( get_term_meta( $category, PREFIX . '_disable_in3_display', true ) === 'yes' ) {
            return true;
         }
      }
      return false;
   }

   /**
    * Create the offer display and enable it only where the options allow it
    *
    * @param string $price - html string of price
    * @param \WC_Product $product
    * @return string html of the offer to be displayed or the simple price
    */
   public static function show_offer( $price, $current_product ) {

      $gateway = new Gateway();
      if ( $gateway->enabled !== 'yes' ) {
         return $price;
      }

      $in3_img = '<img src="' . PLUGIN_URL . '/assets/images/in3-logo.png' . '" alt="In3" class="in3-logo" />';


      if ( method_exists( $current_product, 'get_bundle_price' ) ) {
         $amount = wc_price( wc_get_price_to_display( $current_product, ['qty' => 1, 'price' => $current_product->get_bundle_price() ] ) / 3 );
      } else {
         $amount = wc_price( wc_get_price_to_display($current_product) / 3 );
      }
      if ( Gateway::product_supports_gateway( $current_product ) ) {
         $price .= '<br/><em class="payin3-tooltip">' . sprintf(__('or %s in 3 terms', 'woosa-in3'), $amount). $in3_img .'</em>';
      } else {
         $price .= '<br/><em class="payin3-tooltip" style="display: none;">' . sprintf(__('or %s in 3 terms', 'woosa-in3'), $amount). $in3_img .'</em>';
      }
      return $price;

   }


   /**
    * Add text below cart/checkout total
    *
    * @since 1.0.5 - remove `number_format`
    * @since 1.0.0
    * @param string $html
    * @return string
    */
   public static function filter_cart_total_price($html){

      $country = Utility::rgar($_POST, 'country', Utility::rgar($_POST, 'billing_country'));

      $gateway = new Gateway();

      if((is_cart() && $gateway->enabled && Gateway::cart_supports_gateway()) || (is_checkout() && $gateway->is_available() && Gateway::cart_supports_gateway() && Core::is_allowed_country($country))){
         $total = WC()->cart->get_totals()['total'];
         $amount = wc_price($total/3);

         $html .= '<br/><em class="payin3-tooltip">'.sprintf(__('or %s in 3 terms via %s', 'woosa-in3'), $amount, '<a href="https://www.payin3.nl" target="_blank">in3</a>').'</em>';

      }

      return $html;
   }



   /**
    * Add text below order total on checkout in `pay for order` mode
    *
    * @since 1.0.5 - remove formation price and use the raw price directly from the order
    * @since 1.0.3 - format numbers with `,` as decimal separator
    * @since 1.0.0
    * @param array $total_rows
    * @return array
    */
   public static function filter_order_item_totals($total_rows){

      global $wp_query;

      if(isset($wp_query->query) && isset($wp_query->query['view-order'])){

         $order = wc_get_order($wp_query->query['view-order']);

         if($order instanceof \WC_Order){
            if(Gateway::order_supports_gateway($order)){
               $total = $order->get_total();
               $amount = wc_price($total/3);
               $total_rows['order_total']['value'] = $total_rows['order_total']['value'].'<br/><em class="payin3-tooltip">'.sprintf(__('or %s in 3 terms via %s', 'woosa-in3'), $amount, '<a href="https://www.payin3.nl" target="_blank">in3</a>').'</em>';
            }
         }
      }

      return $total_rows;
   }

   /**
    * Save the user product_cat custom options
    *
    * @since 1.2.1
    * @return void
    */
    public static function save_product_cat_options() {
      delete_term_meta( $_POST['tag_ID'], PREFIX . '_disable_in3_display' );
      if ( !empty( $_POST[PREFIX . '_disable_in3_display'] ) && $_POST[PREFIX . '_disable_in3_display'] === 'yes' ) {
         update_term_meta( $_POST['tag_ID'], PREFIX . '_disable_in3_display', 'yes' );
      }
   }

   /**
    * Display custom option fields for product_cat
    *
    * @since 1.2.1
    * @param object $tag - wordpress term object
    * @param object $taxonomy - wordpress taxonomy object
    * @return void
    */
   public static function product_cat_options( $tag = '', $taxonomy = '' ) {
      $in3_display = '';
      if ( !empty( $tag ) ) {
         $in3_display = (get_term_meta( $tag->term_id, PREFIX . '_disable_in3_display', true ) === 'yes' ? 'checked="checked"' : '' );
      }
      ?>
      <tr class="form-field">
         <th scope="row" valign="top"><label for="<?php echo PREFIX . '_disable_in3_display'; ?>"><?php _e("Disable in3 Offer display", 'woosa-in3' ); ?></label></th>
         <td>
            <input type="checkbox" id="<?php echo PREFIX . '_disable_in3_display'; ?>" name="<?php echo PREFIX . '_disable_in3_display'; ?>" value="yes" <?php echo $in3_display; ?> />
            <br />
            <span class="description"><?php _e('Weather or not to display in3 price per term for this product category.', 'woosa-in3' ); ?></span>
         </td>
      </tr>
      <?php
   }


}
Woocommerce::instance();
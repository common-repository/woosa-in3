<?php
/**
 * This extends WooCommerce Payments
 *
 * @since 1.0.0
 */

namespace Woosa_IN3;

use VIISON\AddressSplitter\AddressSplitter;
use VIISON\AddressSplitter\Exceptions\SplittingException;

//prevent direct access data leaks

defined( 'ABSPATH' ) || exit;


class Gateway extends \WC_Payment_Gateway{

   /**
    * @since 1.0.0
    */
   public function __construct(){


      $this->id                 = 'in3';
      $this->title              = $this->get_option('title');
      $this->description        = $this->get_option('description');
      $this->icon               = PLUGIN_URL . '/assets/images/payment-icon.png';
      $this->has_fields         = true;
      $this->enabled            = 'no';
      $this->method_title       = __('in3', 'woosa-in3');
      $this->method_description = __('Take payments via in3 platform.', 'woosa-in3');

      $this->supports = array(
         'products',
         'refunds',
         // 'subscriptions',
         // 'subscription_cancellation',
         // 'subscription_suspension',
         // 'subscription_reactivation',
         // 'subscription_amount_changes',
         // 'subscription_date_changes',
         // 'subscription_payment_method_change',
         // 'subscription_payment_method_change_customer',
         // 'subscription_payment_method_change_admin',
         // 'multiple_subscriptions',
         // 'gateway_scheduled_payments',
      );

      // Load the settings.
      $this->init_form_fields();
      $this->init_settings();

      add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
      add_filter( 'woocommerce_gateway_icon', [__CLASS__, 'payment_info'], 10, 2 );

   }



   /**
	 * Check if the gateway is available for use
	 *
	 * @return bool
	 */
	public function is_available(){

      $is_available = ( 'yes' === $this->enabled );
      $country = Utility::rgar($_POST, 'country', Utility::rgar($_POST, 'billing_country'));

      if(!Core::is_allowed_country($country) && !isset($_GET['pay_for_order'])) $is_available = false;

      if( $is_available && (is_cart() || (is_checkout()))){
         if(isset($_GET['pay_for_order']) && isset($_GET['key'])){
            $is_available = self::order_supports_gateway(wc_get_order(wc_get_order_id_by_order_key($_GET['key'])));
         }else{
            $is_available = self::cart_supports_gateway();
         }
      }

		return $is_available;
	}


   /**
    * Checks if cart products can be payed by this gateway
    *
    * @return bool
    * @since 1.0.9
    */
   public static function cart_supports_gateway(){
      if (empty(WC()->cart) || WC()->cart->get_totals()['total'] < self::min_price() || WC()->cart->get_totals()['total'] > self::max_price() ){
         return false;
      }

      foreach (WC()->cart->get_cart() as $item){
         $product = wc_get_product($item['product_id']);
         if (!in_array($product->get_type(), self::get_supported_product_types())){
            return false;
         }
      }

      return true;
   }


   /**
    * Checks if product supports gateway
    *
    * @param \WC_Product $product
    * @return bool
    * @since 1.0.9
    */
   public static function product_supports_gateway($product){
      $product_price = wc_get_price_to_display( $product );
      return $product_price >= self::min_price() && $product_price <= self::max_price() && in_array($product->get_type(), self::get_supported_product_types());
   }


   /**
    * Checks if order can be payed by this gateway
    *
    * @param \WC_Order $order
    * @return bool
    * @since 1.0.9
    */
   public static function order_supports_gateway($order){
      if(!$order instanceof \WC_Order){
         return false;
      }

      if($order->get_total() < self::min_price()) {
         return false;
      }

      foreach ($order->get_items() as $item){
         $product = $item->get_product();
         /**@var \WC_Product $product*/
         if(!in_array($product->get_type(), self::get_supported_product_types() )){
            return false;
         }
      }


      return true;
   }

   /**
    * An array of supported product types
    *
    * @return array
    * @since 1.0.9
    */
   protected static function get_supported_product_types(){
      return ['simple', 'variation', 'variable', 'bundle'];
   }

   /**
    * Add extra payment fields
    *
    * @since 1.0.0
    * @return void
    */
   public function payment_fields() {

      parse_str(Utility::rgar($_POST, 'post_data'), $post_data);

      //show message if ship to different address is checked
      if(Utility::rgar($post_data, 'ship_to_different_address') == '1'){
         echo '<span style="color: #a33">'.__('You can not pay in 3 terms when ship to a different address.', 'woosa-in3').'</span>';

         return;
      }

      echo wpautop( wptexturize( $this->get_description() ) );

      /**
       * only for main checkout page
       * - for checkout + pay for order, we already have the data saved on order
       */
      if(is_checkout() && !isset($_GET['pay_for_order']) && !isset($_GET['key'])){
         include_once plugin_dir_path(__FILE__) . 'checkout-fields.php';
      }
   }



   /**
    * Process the payment
    *
    * @since 1.0.0
    * @param int $order_id
    * @return array
    */
   public function process_payment( $order_id ) {

      $order = wc_get_order( $order_id );
      $redirect_url = $this->get_return_url( $order );

      //pay an existing unpaid order - redirect straight to payment url
      if((bool) Utility::rgar($_POST, 'woocommerce_pay') === true){
         return array(
            'result'   => 'success',
            'redirect' => get_post_meta($order->get_id(), PREFIX.'_payment_url', true)
         );
      }

      $credit = $this->_credit_check( $order, $_POST );

      if ( $credit['error'] === true ) {

         Utility::wc_error_log( $credit, __FILE__, __LINE__ );

         //change order status on fail
         $order->update_status( 'failed' );
         $order->add_order_note( __( 'The payment process failed', 'woosa-in3' ) );

         return array(
            'result'   => 'failure',
            'redirect' => $redirect_url
         );
      }

      if($credit['accepted'] === true){
         try {
            $invoice = $this->_invoice($order, $_POST);
         } catch(\Exception $e){

            wc_add_notice($e->getMessage(), 'error');

            return array(
               'result'   => 'failure',
               'redirect' => $redirect_url
            );
         }

         if(isset($invoice['payment_url'])){
            $redirect_url = $invoice['payment_url'];
         }else{
            return array(
               'result'   => 'failure',
               'redirect' => $redirect_url
            );
         }

      }else{

         wc_add_notice(__('At the moment you can\'t pay via in3. Please choose a different payment method or contact in3 for more information.', 'woosa-in3'), 'error');

         return array(
            'result'   => 'failure',
            'redirect' => $redirect_url
         );
      }

      //empty the cart
      WC()->cart->empty_cart();

      return array(
         'result'   => 'success',
         'redirect' => $redirect_url
      );

   }



   /**
    * Process a credit check
    *
    * @since 1.0.2 - fixed houser number issue
    * @since 1.0.0
    * @param object $order
    * @param array $data
    * @return object
    */
   private function _credit_check($order, $data){

      $error = true;
      $accepted = false;

      $birth_day = Utility::rgar($data, PREFIX.'_birth_day');
      $birth_month = Utility::rgar($data, PREFIX.'_birth_month');
      $birth_year = Utility::rgar($data, PREFIX.'_birth_year');
      $birth_date = new \DateTime( $birth_year . '-' . $birth_month . '-' . $birth_day );
      $birth_date = $birth_date->format('Y-m-d');

      $coc_number = Utility::rgar($data, PREFIX.'_coc_number');
      $gender     = Utility::rgar($data, PREFIX.'_gender');

      $address_1 = self::get_data_field($order, 'address_1', $data);
      $house_number = self::get_data_field($order, 'house_number', $data);
      $street_name = $address_1;

      if( empty( $house_number ) ){

         $address = $this->split_address($address_1);
         $house_number = Utility::rgars($address, 'houseNumberParts/base');
         $house_number_suffix = Utility::rgars($address, 'houseNumberParts/extension');
         $street_name = Utility::rgar($address, 'streetName');

      }else{

         $house_number_suffix = preg_replace('/[0-9]+/', '', $house_number);
         $house_number = preg_replace('/[^0-9]/', '', $house_number);
      }

      $args = array(
         'ShopName' => Core::shop_name(),
         'CreditCheckRequest'   => array(
            'LastName'          => self::get_data_field($order, 'last_name', $data),
            'Initials'          => substr(self::get_data_field($order, 'first_name', $data), 0, 1), //take first letter
            'Gender'            => $gender,
            'BirthDate'         => $birth_date,
            'StreetName'        => $street_name,
            'HouseNumber'       => $house_number,
            'HouseNumberSuffix' => $house_number_suffix,
            'ZipCode'           => self::get_data_field($order, 'postcode', $data),
            'City'              => self::get_data_field($order, 'city', $data),
            'CountryCode'       => self::get_data_field($order, 'country', $data),
            'PhoneNumber'       => self::get_data_field($order, 'phone', $data),
            'EmailAddress'      => self::get_data_field($order, 'email', $data),
            'IpAddress'         => Core::get_ip(),
            'IsFinal'           => true,
            'IsCorporation'     => empty(self::get_data_field($order, 'company', $data)) ? false : true,
            'ClaimAmount'       => number_format($order->get_total(), 2, '', ''),
            'PaymentMethod'     => 2,//garant method
            'HasDifferentShippingAddress' => false,
         )
      );

      $co_args = array(
         'CorporationName' => self::get_data_field($order, 'company', $data),
         'IsSoleProprietor' => true,
         'CoCNumber'     => $coc_number,
      );

      if(!empty(self::get_data_field($order, 'company', $data))){
         $args['CreditCheckRequest'] = array_merge($args['CreditCheckRequest'], $co_args);
      }


      //save additional info
      update_user_meta($order->get_user_id(), PREFIX.'_birth_day', $birth_day);
      update_user_meta($order->get_user_id(), PREFIX.'_birth_month', $birth_month);
      update_user_meta($order->get_user_id(), PREFIX.'_birth_year', $birth_year);
      update_user_meta($order->get_user_id(), PREFIX.'_gender', $gender);
      update_user_meta($order->get_user_id(), PREFIX.'_house_number', $house_number);
      update_user_meta($order->get_user_id(), PREFIX.'_coc_number', $coc_number);

      $request = API::request('creditcheck', $args);

      if($request->code == 200){

         $error = false;
         $response = $request->body;

         if($response->IsAccepted){

            $accepted = true;

            if ( empty( $response->TransactionNumber ) ) {

               $error = false;
               $order->add_order_note( 'Missing transaction number - unable to continue', 'woosa-in3' );

            }else{

               update_post_meta($order->get_id(), PREFIX.'_transaction_number', $response->TransactionNumber);
            }

         } else {

            $order->add_order_note(API::ignore_reason($response->RefuseReason));
            $order->update_status('cancelled');

         }

      }

      if($request->code == 400){
         wc_add_notice(__('Something went wrong while creating the payment.', 'woosa-in3'), 'error');

         Utility::wc_error_log($request->body, __FILE__, __LINE__);
      }

      return array(
         'error' => $error,
         'accepted' => $accepted,
      );
   }


   /**
    * Process invoice
    *
    * @param object $order
    * @param array $data - additional data ($_POST)
    * @return bool|array
    * @throws \Exception
    * @since 1.0.3 - changed cryptation method
    * @since 1.0.0
    */
   public function _invoice($order, $data){

      $transaction_number = get_post_meta($order->get_id(), PREFIX.'_transaction_number', true);
      $product_lines = [];
      $total_lines = [];

      foreach($order->get_items() as $item){

         $product = $item->get_product();
         /**@var \WC_Product $product*/
         if(!in_array($product->get_type(), self::get_supported_product_types())){
            throw new \Exception('Order contains unsupported product! Product id = ' . $product->get_id());
         }

         $price = number_format(($product->get_price()/3), 2); //in 3 terms
         $order_line_total = number_format(($price * $item->get_quantity()), 2);
         $product_lines[] = array(
            "ProductCode" => $product->get_sku(),
            "ProductName" => $product->get_title(),
            "Quantity" => $item->get_quantity(),
            "Price" => $price,
            "LineTotal" => $order_line_total
         );
      }

      //shipping line
      if(!empty($order->get_shipping_total())){
         $order_shipping_total = number_format(($order->get_shipping_total()), 2);
         $total_lines[] = array(
            'name' => __('Shipping', 'woosa-in3'),
            'value' => $order_shipping_total,
            'isTotal' => false,
         );
      }

      //taxes line
      foreach($order->get_tax_totals() as $tax){
         $order_tax_amount = number_format(($tax->amount), 2);
         $total_lines[] = array(
            'name' => $tax->label,
            'value' => $order_tax_amount,
            'isTotal' => false,
         );
      }

      //add total line
      $order_total = number_format(($order->get_total()), 2);
      $total_lines[] = array(
         'name' => __('Total', 'woosa-in3'),
         'value' => $order_total,
         'isTotal' => true,
      );

      $request = API::request('invoice', array(
         'ShopName' => Core::shop_name(),
         'InvoiceRequest' => array(
            'TransactionNumber' => $transaction_number,
            'InvoiceNumber' => $order->get_id(),
            'InvoiceDate' => $order->get_date_created()->format('c'),
            'InvoiceAmount' => number_format($order->get_total(), 2, '', ''),
            'InvoiceDescription' => sprintf('Generated invoice for order #%s', $order->get_id()),
            'InvoicePdfSubmitType' => 2,
            'CultureCode' => self::get_data_field($order, 'country', $data),
            'InvoicePdfData' => array(
               'ProductLines' => $product_lines,
               'TotalLines' => $total_lines
            )
         )
      ));


      if($request->code == 200){

         $response = $request->body;

         if(isset($response->PaymentUrl)){

            $data = array(
               'payment_url' => add_query_arg( array(
                  'returnUrl' => add_query_arg( array(
                     PREFIX.'_process' => Core::crypt( $order->get_id() ),
                  ), home_url() ),
                  'shopOrderExchangeUrl' => add_query_arg( array(
                     PREFIX.'_process' => Core::crypt( $order->get_id() ),
                     PREFIX.'_abandoned' => '1',
                  ), home_url() ),
              ), $response->PaymentUrl )
            );

            update_post_meta($order->get_id(), PREFIX.'_payment_url', $data['payment_url']);

            return $data;

         }else{

            wc_add_notice(__('Something went wrong while getting payment URL.', 'woosa-in3'), 'error');

            Utility::wc_error_log($request->body, __FILE__, __LINE__);
         }
      }

      if($request->code == 400){
         wc_add_notice(__('Something went wrong while creating the invoice.', 'woosa-in3'), 'error');

         Utility::wc_error_log($request->body, __FILE__, __LINE__);
      }

      return false;
   }



   /**
	 * Process a refund if supported.
	 *
    * @since 1.0.0
	 * @param  int    $order_id Order ID.
	 * @param  float  $amount Refund amount.
	 * @param  string $reason Refund reason.
	 * @return bool|WP_Error
	 */
	public function process_refund( $order_id, $amount = null, $reason = '' ) {

      $order = wc_get_order( $order_id );
      $transaction_number = get_post_meta($order->get_id(), PREFIX.'_transaction_number', true);
      $return_number = substr(md5(mt_rand()), 0, 20);

      $request = API::request('invoicecredit', array(
         'ShopName' => Core::shop_name(),
         'InvoiceCreditRequest' => array(
            'TransactionNumber' => $transaction_number,
            'ReturnNumber' => $return_number,
            'CreditAmount' => number_format($amount, 2, '', ''),
         )
      ));


      if($request->code == 200){
         $order->add_order_note( sprintf( __( 'The refund amount has been sent to in3 successfully - referecence number: %s', 'woocommerce' ), $return_number ) );
      }

      if($request->code == 400){
         return new \WP_Error( 'error', __('Something went wrong while refunding the payment amount.', 'woosa-in3' ) );
         Utility::wc_error_log($request->body, __FILE__, __LINE__);
      }

      return true;
   }



   /**
    * Get custom field value
    *
    * @since 1.0.1
    * @param array $data - from where to extract the field value
    * @param string $field_name
    * @return void
    */
   public static function get_custom_field($data, $field_name){

      $key = Core::get_setting($field_name);

      if(isset($data[$key])){
         return $data[$key];
      }
   }


   /**
    * Split address
    *
    *
    * @since 1.0.2
    * @param string $address
    * @return array Splited address
    */
   private function split_address($address) {
      try {
         $splitted = AddressSplitter::splitAddress($address);
      } catch (SplittingException $e) {
         $splitted = [];
      }

      return $splitted;
   }



   /**
    * Return an order field value
    *
    * @since 1.0.1
    * @param object $order
    * @param string $field_name
    * @param array $data - additional data ($_POST)
    * @return string
    */
   public static function get_data_field($order, $field_name, $data){

      $value = '';
      $billing = "get_billing_{$field_name}";
      $shipping = "get_shipping_{$field_name}";

      if($order instanceof \WC_Order){

         if(method_exists($order, $shipping)) $value = $order->$shipping();

         if(method_exists($order, $billing) && empty($value)) $value = $order->$billing();

         if(empty($value)) $value = self::get_custom_field($data, $field_name);
      }

      return $value;
   }



   /**
    * Validate fields
    *
    * @since 1.0.0
    * @return bool
    */
   public function validate_fields() {

      $is_valid = parent::validate_fields();

      $gender = Utility::rgar($_POST, PREFIX.'_gender');
      $birth_day = (int) Utility::rgar($_POST, PREFIX.'_birth_day');
      $birth_month = (int) Utility::rgar($_POST, PREFIX.'_birth_month');
      $birth_year = (int) Utility::rgar($_POST, PREFIX.'_birth_year');
      $coc_number = Utility::rgar($_POST, PREFIX.'_coc_number');
      $address_1 = Utility::rgar($_POST, 'billing_address_1', self::get_custom_field($_POST, 'address_1'));
      $house_number = Utility::rgar( $_POST, 'house_number', self::get_custom_field( $_POST, 'house_number'));

      if( empty( $house_number ) ){
         $address = $this->split_address($address_1);
         $house_number = Utility::rgar($address, 'houseNumber');
      }

      if(Utility::rgar($_POST, 'ship_to_different_address') == '1'){
         $is_valid = false;
         wc_add_notice(__('You can not pay in 3 terms when ship to a different address.', 'woosa-in3'), 'error');
      }

      /**
       * only for main checkout page
       * - for checkout + pay for order, we already have the data saved on order
       */
      if(is_checkout() && !isset($_GET['pay_for_order']) && !isset($_GET['key'])){

         if($gender ==''){
            $is_valid = false;
            wc_add_notice(sprintf(__('%sGender%s is required.', 'woosa-in3'), '<b>','</b>'), 'error');
         }

         $is_valid = self::validate_birth_date($is_valid, $birth_day, $birth_month, $birth_year);

         $is_valid = $this->validate_house_number($is_valid, $house_number);

         if( !empty(Utility::rgar($_POST, 'billing_company')) && empty($coc_number) ){
            $is_valid = false;
            wc_add_notice(sprintf(__('%sCoC Number%s is required for the company name you provided.', 'woosa-in3'), '<b>', '</b>'), 'error');
         }
         $is_valid = false;
         //store in cart session
         WC()->session->set( PREFIX.'_gender', $gender );
         WC()->session->set( PREFIX.'_birth_day', $birth_day );
         WC()->session->set( PREFIX.'_birth_day', $birth_month );
         WC()->session->set( PREFIX.'_birth_day', $birth_year );
         WC()->session->set( PREFIX.'_house_number', $house_number );
         WC()->session->set( PREFIX.'_coc_number', $coc_number );

      }

      return $is_valid;
   }



   /**
    * Validates birth date
    *
    * @since 1.0.6
    * @param bool $is_valid
    * @param string $birth_day
    * @param string $birth_month
    * @param string $birth_year
    * @return bool
    */
   public static function validate_birth_date($is_valid, $birth_day, $birth_month, $birth_year){

      if( empty( $birth_day ) || 1 > $birth_day || 31 < $birth_day ) {
         $is_valid = false;
         wc_add_notice(sprintf(__('%sBirth date day%s is invalid.', 'woosa-in3'), '<b>', '</b>'), 'error');
      } else if ( empty( $birth_month ) || 1 > $birth_month || 12 < $birth_month ) {
         $is_valid = false;
         wc_add_notice(sprintf(__('%sBirth date month%s is invalid.', 'woosa-in3'), '<b>', '</b>'), 'error');
      } else if ( empty( $birth_year ) || 1900 > $birth_year ) {
         $is_valid = false;
         wc_add_notice(sprintf(__('%sBirth date year%s is invalid.', 'woosa-in3'), '<b>', '</b>'), 'error');
      } else if ( date('Y') - $birth_year < 18 ) {
         $is_valid = false;
         wc_add_notice(__('Too young. You must be at least 18 years old.', 'woosa-in3'), 'error');
      } else if ( $birth_day > cal_days_in_month( CAL_GREGORIAN, $birth_month, $birth_year ) ) {
         $is_valid = false;
         wc_add_notice(sprintf(__('%sBirth date%s is invalid.', 'woosa-in3'), '<b>', '</b>'), 'error');
      }

      return $is_valid;
   }



   /**
    * Validate house number
    *
    * @since 1.0.2
    * @param bool $is_valid
    * @param string $house_number
    * @return bool
    */
   protected function validate_house_number($is_valid, $house_number){

      if( empty($house_number) ){

         $is_valid = false;
         wc_add_notice(__('House number is required.', 'woosa-in3'), 'error');

      }elseif( ! preg_match('~[0-9]~', $house_number) ){

         $is_valid = false;
         wc_add_notice(__('House number must contain at least one number.', 'woosa-in3'), 'error');
      }

      return $is_valid;
   }



   /**
    * Gateway settings
    *
    * @since 1.0.0
    * @return void
    */
   public function init_form_fields() {

      $pages = [];
      $pages[] = __('Select', 'woosa-in3');

      foreach(get_pages() as $page){
         $pages[$page->ID] = $page->post_title;
      }

      $this->form_fields = include 'setting-fields.php';
   }



   /**
    * Adds info tootltip in checkout page
    *
    * @since 1.2.1
    * @param string $payment_icon
    * @param string $payment_id
    * @return string $payment_icon - the payment icon and if on checkout the tooltip
    */
   public static function payment_info( $payment_icon, $payment_id ) {
      if ( is_checkout() && $payment_id === 'in3' ) {
         $payment_icon = '<img src="' . PLUGIN_URL . '/assets/images/payment-icon.png' . '" id="' . PREFIX . '-gateway-icon" />' . '<img src="' . PLUGIN_URL . '/assets/images/info-box.svg' . '" id="payment_method_in3_info" />';
      }

      return $payment_icon;
   }

   /**
    * Get the min price for displaying the in3 offer
    *
    * @return float $min_price
    */
   public static function min_price() {
      $min_price = ( empty( Core::get_setting( 'min_in3_price', 100 ) ) ) ? 100 : Core::get_setting( 'min_in3_price', 100 );
      $min_price = apply_filters( PREFIX . 'gateway-min-price', $min_price );
      return $min_price;
   }

   /**
    * Get the max price for displaying the in3 offer
    *
    * @return float $max_price
    */
   public static function max_price() {
      $max_price = ( empty( Core::get_setting( 'max_in3_price', 3000 ) ) ) ? 3000 : Core::get_setting( 'max_in3_price', 3000 );
      $max_price = apply_filters( PREFIX . '-gateway-max-price', $max_price );
      return $max_price;
   }

}

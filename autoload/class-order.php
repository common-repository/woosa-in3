<?php
/**
 * This is responsible for extending WooCommernce orders
 *
 * @since 1.1.0
 */

namespace Woosa_IN3;


//prevent direct access data leaks
defined( 'ABSPATH' ) || exit;

class Order{


    /**
     * The instance of this class
     *
     * @since 1.1.0
     * @var null|object
     */
    protected static $instance = null;



	/**
	 * Return an instance of this class.
	 *
	 * @since     1.1.0
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
     * Register new status
     */
    public static function register_manual_payment_check_order_status() {
        register_post_status( 'wc-in3-payment-issue', array(
            'label'                     => __( 'Payment issue', 'woosa-in3' ),
            'public'                    => false,
            'exclude_from_search'       => true,
            'show_in_admin_all_list'    => true,
            'show_in_admin_status_list' => true,
            'label_count'               => _n_noop( 'Payment issue (%s)', 'Payment issue (%s)' )
            )
        );
    }

   /**
    *
    */

   public static function add_manual_payment_check_to_order_statuses( $order_statuses ) {
      $new_order_statuses = array();

      // add new order status after processing
      foreach ( $order_statuses as $key => $status ) {

         $new_order_statuses[ $key ] = $status;

         if ( 'wc-processing' === $key ) {
            $new_order_statuses['wc-in3-payment-issue'] = __( 'Payment issue', 'woosa-in3' );
         }
      }

      return $new_order_statuses;
   }

   public static function styling_admin_order_list() {
      global $pagenow, $post;

      if( $pagenow != 'edit.php') return; // Exit
      if( get_post_type($post->ID) != 'shop_order' ) return; // Exit

      // HERE we set your custom status
      $order_status = 'in3-payment-issue'; // <==== HERE
      ?>
      <style>
         .order-status.status-<?php echo sanitize_title( $order_status ); ?> {
               background: #f8dda7;
               color: #94660c;
         }
      </style>
      <?php
   }



    /**
    * Get the list of waiting orders to be checked.
    *
    * @since 1.1.0
    * @return $waiting_orders
    */
    public static function get_waiting_orders(){

        $args = array(
            'status' => array('pending', 'on-hold'),
            'payment_method' => 'in3'
        );

        $waiting_orders = wc_get_orders( $args );

        return $waiting_orders;

    }



    /**
    * Update the order status by checking the payment status on in3.
    *
    * @since 1.1.0
    * @param int $order_id
    * @return
    */
    public static function check_payment_status( $order_id ){

        $order = wc_get_order($order_id);

        $transaction_number = get_post_meta($order_id, PREFIX.'_transaction_number', true);

        if(empty($transaction_number)){
            return $order->add_order_note(__('The payment status cannot be processed because the transaction number is missing! Please check with in3 and process manually this order.', 'woosa-in3'));
        }

        $get_status = wp_remote_get(API::base_payment_url("payinstallment/status/{$transaction_number}"));
        $status = strtolower(Utility::rgar($get_status, 'body'));

        if ( is_wp_error( $get_status ) ) {

            $url = add_query_arg( array(
                PREFIX.'_process' => Core::crypt( $order->get_id() ),
            ), home_url() );

            $order->add_order_note( sprintf(
                __('An error has occurred while trying to get the payment status, %sclick here%s to try again manually', 'woosa-in3'),
                '<a href="'.esc_url( $url ).'" target="_blank">',
                '</a>'
            ));

        } else {

            //check response code
            if ( isset( $get_status['response']['code'] ) && $get_status['response']['code'] == 500 ) {
                $order->add_order_note( __( 'The transaction number failed to updated the payment satutus', 'woosa-in3' ) );
            }

            if ( 'paid' === $status ) {

                //$page_id = (int) Core::get_setting('paid_page_id');
                $order->payment_complete();
                $order->add_order_note(__('The payment has been done successfully', 'woosa-in3'));

            } elseif ( 'cancelled' === $status ) {

                //$page_id = (int) Core::get_setting('cancelled_page_id');
                $order->update_status('cancelled');
                $order->add_order_note(__('The payment has been cancelled by the customer', 'woosa-in3'));

            } elseif ( 'waiting' === $status ) {

               $payment_waiting = ( int ) get_post_meta( $order_id, PREFIX . '_payment_waiting', true );

                if ( empty( $payment_waiting ) ) {
                    $payment_waiting = 0;
                }

                if ( 'on-hold' === $order->get_status() && $payment_waiting > 0 ) {

                    update_post_meta( $order_id, PREFIX . '_payment_waiting', ++$payment_waiting );

                    $date_created_dt = $order->get_date_created(); // Get order date created WC_DateTime Object
                    $timezone        = $date_created_dt->getTimezone(); // Get the timezone
                    $date_created_ts = $date_created_dt->getTimestamp(); // Get the timestamp in seconds

                    $now_dt = new \WC_DateTime(); // Get current WC_DateTime object instance
                    $now_dt->setTimezone( $timezone ); // Set the same time zone
                    $now_ts = $now_dt->getTimestamp(); // Get the current timestamp in seconds

                    $week_in_seconds = 7 * 24 * 60 * 60; // 1 week in seconds

                    $diff_seconds = $now_ts - $date_created_ts; // Get the difference (in seconds)

                    if ( $diff_seconds > $week_in_seconds ) {
                        // more then a week has passed, change to custom status and update order note.
                        $order->update_status('in3-payment-issue');
                        $order->add_order_note(__('The payment hasn\'t been processed yet and more than a week has passed. Please check with in3 and process manually this order.', 'woosa-in3'));
                    }
                    return;
                }

                update_post_meta( $order_id, PREFIX . '_payment_waiting', 1 );

                $order->update_status('on-hold');
                $order->add_order_note(__('We\'re waiting for the payment to be processed', 'woosa-in3'));

            } elseif ( 'not started' === $status ) {
                $payment_not_started = ( int ) get_post_meta( $order_id, PREFIX . '_payment_not_started', true );
                if ( empty( $payment_not_started ) ) {
                    $payment_not_started = 0;
                }

                if ( $payment_not_started > 0 ) {
                    update_post_meta( $order_id, PREFIX . '_payment_not_started', ++$payment_not_started );

                    $date_created_dt = $order->get_date_created(); // Get order date created WC_DateTime Object
                    $timezone        = $date_created_dt->getTimezone(); // Get the timezone
                    $date_created_ts = $date_created_dt->getTimestamp(); // Get the timestamp in seconds

                    $now_dt = new \WC_DateTime(); // Get current WC_DateTime object instance
                    $now_dt->setTimezone( $timezone ); // Set the same time zone
                    $now_ts = $now_dt->getTimestamp(); // Get the current timestamp in seconds

                    $days_in_seconds = 3 * 24 * 60 * 60; // 3 days in seconds

                    $diff_seconds = $now_ts - $date_created_ts; // Get the difference (in seconds)

                    if ( $diff_seconds > $days_in_seconds ) {
                        $order->update_status('cancelled');
                        $order->add_order_note(__('The payment hasn\'t been finalized.', 'woosa-in3'));
                    }
                    return;
                }
                update_post_meta( $order_id, PREFIX . '_payment_not_started', 1 );

            }
        }

    }


}
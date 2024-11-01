<?php
/**
 * Check waiting orders action
 *
 * This runs the payment status check task for the orders paid using in3 that have on-hold or paymant waiting status.
 *
 * @since 1.1.0
 */

namespace Woosa_IN3;


//prevent direct access data leaks
defined( 'ABSPATH' ) || exit;


class Check_Waiting_Orders extends Abstract_Background_Process {


	/**
	 * Unique action.
    *
    * @since 1.1.0
	 */
   protected $action = 'check_waiting_orders';



	/**
	 * Task
	 *
	 * Return the modified item for further processing
	 * in the next pass through. Or, return false to remove the
	 * item from the queue.
	 *
     * @since 1.1.0
	 * @param int $item - order id
	 * @return false
	 */
	protected function task( $item ) {

        Order::check_payment_status($item);

        $this->update_progress($item);

		return false;
	}



	/**
	 * Cancel Process
	 *
	 * Stop processing queue items.
	 *
	 * @since 1.1.0
	 */
	public function cancel_process(){
		parent::cancel_process();

	}



	/**
	 * Complete
	 *
    * @since 1.1.0
	 */
	protected function complete() {
      parent::complete();

	}

}

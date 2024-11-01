<?php
/**
 * Abstract background process
 *
 * @since 1.1.0
 */

namespace Woosa_IN3;

defined( 'ABSPATH' ) || exit;


abstract class Abstract_Background_Process extends \WP_Background_Process {

   /**
    * Prefix
    *
    * @since 1.1.6
    * @var string
    * @access protected
    */
   protected $prefix = PREFIX;


   /**
    * Initiate new background process
    */
   public function __construct() {
      parent::__construct();

      add_action( $this->identifier . '_cron_interval', [ $this, 'change_cron_interval' ] );
   }



   /**
    * Schedule cron healthcheck.
    *
    * @since 1.1.1
    * @param int $interval
    * @return int
    */
   public function change_cron_interval( $interval ) {
      $interval = 3;//3 minutes
      return $interval;
   }


   /**
    * Gets the unique action of the process.
    *
    * @since 1.1.1
    * @return string
    */
   public function get_action(){
      return $this->action;
   }



   /**
    * Fix - if it's ajax background process request ($this->dispatch()), it dies after 20 seconds
    * @since 1.1.1
    */
   public function maybe_handle() {
      ignore_user_abort( true );
      parent::maybe_handle();
   }



   /**
    * Save queue
    *
    * @since 1.1.1
    * @return $this
    */
   public function save() {
      $key = $this->generate_key();

      // add only one queue per time
      if ( ! empty( $this->data ) && $this->is_queue_empty() ) {
         update_site_option( $key, $this->data );
      }

      return $this;
   }



   /**
    * This function fixes max_input_vars problem: $this->data may consist of a lot of items and we don't need it.
    * Parent function is used in dispatch() method to call new background process.
    *
    * @since 1.1.6
    * @see \WP_Async_Request::dispatch()
    * @return array
    */
   protected function get_post_args(){
      $this->data = [];
      return parent::get_post_args();
   }



   /**
    * Update queue progress.
    *
    * @since 1.1.6
    * @param int $item - index key
    * @return void
    */
   protected function update_progress($item){

      $items = get_option($this->prefix.'_'.$this->action.'_progress', []);

      if( ! isset($items[$item]) ){
         $items[$item] = $item;
         update_option($this->prefix.'_'.$this->action.'_progress', $items);
      }

   }



   /**
    * Calculates queue progress.
    *
    * @since 1.1.6
    * @param int $total
    * @param string $progress
    * @return int
    */
   public function calculate_progress($total, $progress = ''){

      if(empty($progress)){
         $progress = count(get_option($this->prefix.'_'.$this->action.'_progress', []));
      }

      if($progress < 1 || $total < 1) return 0;

      $left = $progress / $total * 100;

      return intval(floor($left));
   }



	/**
	 * Cancel Process
	 *
	 * Stop processing queue items.
	 *
	 * @since 1.1.6
    * @return void
	 */
	public function cancel_process(){
		parent::cancel_process();

		//remove progress
      delete_option($this->prefix.'_'.$this->action.'_progress');

	}



   /**
    * Complete Process
    *
	 * This runs once the queue is complete.
	 *
	 * @since 1.1.6
    * @return void
	 */
	protected function complete() {
      parent::complete();

      //remove progress
      delete_option($this->prefix.'_'.$this->action.'_progress');

   }

}

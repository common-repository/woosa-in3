<?php
/**
 * Handle Background Process
 *
 * This is the handler for the existing background processes.
 *
 * @author Woosa Team
 * @since 1.1.0
 */

namespace Woosa_IN3;


//prevent direct access data leaks
defined( 'ABSPATH' ) || exit;


class Handle_Background_Process{

   /**
    * The instance of this class.
    *
    * @since 1.0.9
    * @var null|object
    */
   protected static $instance = null;


   /**
    * Instance of check waiting orders class
    *
    * @since 1.1.0
    * @var Check_Waiting_Orders $check_waiting_orders
    */
   public static $check_waiting_orders;


	/**
	 * Returns an instance of this class.
	 *
	 * @since 1.1.0
	 * @return object A single instance of this class.
	 */
	public static function instance() {

		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
   }



   /**
    * Initialization
    *
    * @return void
    */
   public function __construct(){

      self::$check_waiting_orders = new Check_Waiting_Orders();

   }



   /**
    * Runs check waiting orders queue.
    *
    * @since 1.1.0
    * @return void
    */
   public static function check_waiting_orders(){

      $waiting_orders = Order::get_waiting_orders();

      if(count($waiting_orders) > 0){

         foreach($waiting_orders as $item){

            self::$check_waiting_orders->push_to_queue( $item->id );
         }

         self::$check_waiting_orders->save()->dispatch();
      }

   }



   /**
    * List of task runners
    *
    * @since 1.1.0
    * @return array
    */
   public static function task_runners(){

      return [
      ];

   }



   /**
    * Display the progress of the queue.
    *
    * @since 1.1.0
    * @return string
    */
   public static function show_queue_progress(){

      if(defined('DOING_AJAX') && DOING_AJAX){
         return;
      }

      foreach(self::task_runners() as $key => $item){

         if($item['started'] !== false){

            $output = "<span>{$item['message']}</span>";
            $progress_bar = Utility::rgar($item, 'progress_bar');

            if(is_callable($progress_bar)){
               $percentage = $progress_bar();
               $show_percentage = $percentage > 8 ? $percentage.'%' : '';

               ob_start();
               ?>

               <div class="<?php echo PREFIX.'-progress-bar';?>">
                  <div style="width: <?php echo $percentage?>%" class="<?php echo PREFIX.'-progress-bar__inner';?>"><span><?php echo $show_percentage?></span></div>
               </div>

               <?php
               $output .= ob_get_clean();
            }

            Utility::show_notice($output, 'warning', true);
         }

      }

   }

}
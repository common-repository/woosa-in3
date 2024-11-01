<?php
/**
 * Events
 *
 * @author Woosa Team
 * @since 1.1.0
 */

namespace Woosa_IN3;


//prevent direct access data leaks
defined( 'ABSPATH' ) || exit;


class Events{


   /**
    * Add custom schedule intervals
    *
    * @since 1.1.0
    * @param array $schedules
    * @return void
    */
    public static function cron_schedules($schedules) {

      $minutes = 15;
      $seconds = \MINUTE_IN_SECONDS * $minutes;

      $schedules[PREFIX."_{$minutes}minutes"] = array(
         'interval' => $seconds,
         'display' => sprintf( _n( 'Every %s minute', 'Every %s minutes', $minutes, 'woosa-in3' ), $minutes )
      );

      return $schedules;
   }



   /**
    * Adds custom even actions.
    *
    * @since 1.1.0
    * @return void
    */
   public static function init_actions(){

      add_action(PREFIX.'_check_waiting_orders', [Handle_Background_Process::class, 'check_waiting_orders']);

   }



   /**
    * Schedule the custom event actions.
    *
    * @since 1.1.0
    * @return void
    */
   public static function schedule(){

      if ( ! wp_next_scheduled ( PREFIX.'_check_waiting_orders' )) {
         wp_schedule_event(time() + \MINUTE_IN_SECONDS * 15, PREFIX.'_15minutes', PREFIX.'_check_waiting_orders');
      }

   }



   /**
    * Clears scheduled events.
    *
    * @since 1.1.0
    * @return void
    */
   public static function clear(){

      wp_clear_scheduled_hook(PREFIX.'_check_waiting_orders');

   }
}

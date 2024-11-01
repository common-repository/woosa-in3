<?php
/**
 * This for communication with IN3
 *
 * @since 1.0.0
 */

namespace Woosa_IN3;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ClientException;


//prevent direct access data leaks
defined( 'ABSPATH' ) || exit;


class API{



   /**
    * Guzzle API client
    *
    * @since 1.0.4
    * @param array $headers
    * @return object
    */
    public static function client($options = []){

      if(isset($options['headers'])){
         $options['headers'] = self::headers( $options['headers'] );
      }else{
         $options['headers'] = self::headers();
      }


      $client = new Client( $options );

      return $client;
   }


   /**
    * Base API url
    *
    * @since 1.0.0
    * @param string $endpoint
    * @return void
    */
   private static function endpoint($endpoint){

      if(Core::get_setting('test_env') == 'yes') return 'https://capayable-api-test.tritac.com/v2/partner/'.trim($endpoint, '/');//test environment

      return 'https://capayable-api.tritac.com/v2/partner/'.trim($endpoint, '/');
   }



   /**
    * Base payment url
    *
    * @since 1.0.0
    * @return void
    */
   public static function base_payment_url($arg = ''){

      if(Core::get_setting('test_env') == 'yes') return 'https://capayable-payment-test.tritac.com/'.trim($arg, '/');//test environment

      return 'https://capayable-payment.tritac.com/'.trim($arg, '/');
   }



   /**
    * Request headers
    *
    * @since 1.0.0
    * @param array $items
    * @return void
    */
   public static function headers($items = array()){

      $apikey = Core::get_setting('test_env') == 'yes' ? '403f263c3923ed5a55b0ae894e4c8304609cc4bc' : '070bf3ea187f2436efc37e588daab49d092ab397';

      $default = array(
         'apikey' => $apikey,
         'Content-Type' => 'application/json',
      );

      if(is_array($items)) return array_merge($default, $items);

      return $default;
   }



   /**
    * Sends the request.
    *
    * @since 1.0.4 - changed to Guzzle API client
    * @since 1.0.0
    * @param string $endpoint
    * @param array $data
    * @param string $method
    * @param array $options - options for API client (headers, timeout, etc)
    * @return object
    */
    public static function request($endpoint, $data = array(), $method = 'POST', $options = []){

      try{

         $request = API::client( $options )->request($method, self::endpoint($endpoint), ['body' => json_encode($data)]);
         $body = json_decode($request->getBody()->getContents());
         $code = $request->getStatusCode();

         if(\WP_DEBUG){
            Utility::wc_debug_log([
               '_METHOD' => $method,
               '_ENDPOINT' => self::endpoint($endpoint),
               '_HEADERS' => self::headers(),
               '_REQUEST_PAYLOAD' => $data,
               '_REQUEST_RESPONSE' => $body
            ], __FILE__, __LINE__);
         }

      }catch(ClientException $e){

         $body = $e->getResponse()->getBody()->getContents();
         $code = $e->getResponse()->getStatusCode();

         Utility::wc_debug_log([
            '_METHOD' => $method,
            '_CODE' => $code,
            '_ENDPOINT' => self::endpoint($endpoint),
            '_HEADERS' => self::headers(),
            '_REQUEST_PAYLOAD' => $data,
            '_REQUEST_RESPONSE' => json_decode($body),
         ], __FILE__, __LINE__);
      }

      return (object) array(
         'code' => $code,
         'body' => $body,
      );
   }



   /**
    * List of ignored transaction
    *
    * @since 1.0.0
    * @return array
    */
   public static function ignore_reason($number){

      $list = array(
         1 => __('Amount exceeds limit! There is an order amount limit above which pay after delivery by in3 is not available.', 'woosa-in3'),
         2 => __('Balance exceeds limit! There is a limit on the unpaid sum of all orders using in3. ', 'woosa-in3'),
         3 => __('Not creditworthy! The customer is not accepted for credit by this service.  in3 uses services for the actual credit check (B2C: Experian, B2B: Graydon). ', 'woosa-in3'),
         4 => __('Credit check unavailable! The extern credit check service is not available.', 'woosa-in3'),
         5 => __('Not found! The corporation could not be found based on its name and/or its address(B2B only).', 'woosa-in3'),
         6 => __('Address blocked! The invoice address is blocked by in3 ', 'woosa-in3'),
         7 => __('Too young! The customer is under 18 age', 'woosa-in3'),
         8 => __('Different shipping address! The shipping address must be the same as the invoice address', 'woosa-in3'),
         9 => __('Shipping address blocked! The shipping address is blocked by in3', 'woosa-in3'),
         10 => __('API address blocked! The IP address is blocked by in3', 'woosa-in3'),
         11 => __('Country blocked! The country of the invoice address is blocked by in3', 'woosa-in3'),
         12 => __('Shipping country blocked! The country of the shipping address is blocked by in3', 'woosa-in3'),
         13 => __('Amount too low! The invoice amount is to low.', 'woosa-in3'),
         14 => __('Too many open invoices! The customer has too still open invoices with Capyable', 'woosa-in3'),
         15 => __('IP address block due to multiple orders within 24 hours!', 'woosa-in3'),
      );

      if(isset($list[$number])) return $list[$number];
   }



}
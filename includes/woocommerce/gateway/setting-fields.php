<?php
/**
 * Payment gateway settings
 *
 * @since 1.0.0
 */

namespace Woosa_IN3;


//prevent direct access data leaks
defined( 'ABSPATH' ) || exit;


return array(
   array(
      'title'       => __('Settings', 'woosa-in3'),
      'type'        => 'title',
      'description' => '',
   ),
   'enabled'        => array(
      'title'       => __('in3 Payments', 'woosa-in3'),
      'type'        => 'checkbox',
      'label'       => __('Enable/Disable', 'woosa-in3'),
      'default'     => 'no'
   ),
   'title'          => array(
      'title'       => __('Title', 'woosa-in3'),
      'desc_tip'    => __('The title which the user sees during checkout.', 'woosa-in3' ),
      'default'     => __('Pay in 3 terms - 0% interest', 'woosa-in3'),
      'type'        => 'text',
   ),
   'description'    => array(
      'title'       => __('Description', 'woosa-in3'),
      'type'        => 'text',
      'desc_tip'    => __('The description which the user sees during checkout.', 'woosa-in3'),
      'default'     => __('Pay in 3 terms via in3 platformss.', 'woosa-in3'),
   ),
   'show_3terms_amount'    => array(
      'title'       => __('Price amount per term', 'woosa-in3'),
      'label'       => __('Yes', 'woosa-in3'),
      'type'        => 'checkbox',
      'default'     => 'yes',
      'desc_tip'    => __('Whether or not to show the price amount per term for products', 'woosa-in3'),
   ),
   'min_in3_price'    => array(
      'title'       => __('Minimum in3 price', 'woosa-in3'),
      'type'        => 'text',
      'default'     => '100',
      'desc_tip'    => __('The minimum price to display the in3 offer', 'woosa-in3'),
   ),
   'max_in3_price'    => array(
      'title'       => __('Maximum in3 price', 'woosa-in3'),
      'type'        => 'text',
      'default'     => '3000',
      'desc_tip'    => __('The maximum price to display the in3 offer', 'woosa-in3'),
   ),
   'paid_page_id'    => array(
      'title'       => __('Paid payment page', 'woosa-in3'),
      'desc_tip'    => __('Select on which page customer will be redirected when payment has satus paid.', 'woosa-in3'),
      'type'        => 'select',
      'options' => $pages
   ),
   'cancelled_page_id'    => array(
      'title'       => __('Cancelled payment page', 'woosa-in3'),
      'desc_tip'    => __('Select on which page customer will be redirected when payment has satus cancelled.', 'woosa-in3'),
      'type'        => 'select',
      'options' => $pages
   ),
   'test_env'        => array(
      'title'       => __('Test mode', 'woosa-in3'),
      'type'        => 'checkbox',
      'label'       => __('Yes', 'woosa-in3'),
   ),
   array(
      'title'       => __('Checkout form fields', 'woosa-in3'),
      'type'        => 'title',
      'description' => __('If you are using a custom checkout form, please provide here the field name for each of the following:', 'woosa-in3'),
   ),
   'first_name'     => array(
      'title'       => __( 'First Name', 'woosa-in3' ),
      'desc_tip'    => __( 'Specify the checkout field name which will be used as First Name. Default is "billing_first_name".', 'woosa-in3' ),
      'type'        => 'text',
      'placeholder' => 'billing_first_name',
   ),
   'last_name'      => array(
      'title'       => __( 'Last Name', 'woosa-in3' ),
      'desc_tip'    => __( 'Specify the checkout field name which will be used as Last Name. Default is "billing_last_name".', 'woosa-in3' ),
      'type'        => 'text',
      'placeholder' => 'billing_last_name',
   ),
   'gender'      => array(
      'title'       => __( 'Gender', 'woosa-in3' ),
      'desc_tip'    => __( 'Specify the checkout field name which will be used as Gender. There is no default".', 'woosa-in3' ),
      'type'        => 'text',
      'placeholder' => 'billing_last_name',
   ),
   'email'          => array(
      'title'       => __( 'Email', 'woosa-in3' ),
      'desc_tip'    => __( 'Specify the checkout field name which will be used as Email Address. Default is "billing_email".', 'woosa-in3' ),
      'type'        => 'text',
      'placeholder' => 'billing_email',
   ),
   'company_name'   => array(
      'title'       => __( 'Company Name', 'woosa-in3' ),
      'desc_tip'    => __( 'Specify the checkout field name which will be used as Company name. Default is "billing_company".', 'woosa-in3' ),
      'type'        => 'text',
      'placeholder' => 'billing_company',
   ),
   'phone'          => array(
      'title'       => __( 'Phone', 'woosa-in3' ),
      'desc_tip'    => __( 'Specify the checkout field name which will be used as Phone. Default is "billing_phone".', 'woosa-in3' ),
      'type'        => 'text',
      'placeholder' => 'billing_phone',
   ),
   'address_1'      => array(
      'title'       => __( 'Street Address', 'woosa-in3' ),
      'desc_tip'    => __( 'Specify the checkout field name which will be used as Street Address. Default is "billing_address_1".', 'woosa-in3' ),
      'type'        => 'text',
      'placeholder' => 'billing_address_1',
   ),
   'house_number'      => array(
       'title'       => __( 'House Number', 'woosa-in3' ),
       'desc_tip'    => __( 'Specify the checkout field name which will be used as House number. Default is "billing_address_1".', 'woosa-in3' ),
       'type'        => 'text',
       'placeholder' => 'billing_house_number',
   ),
   'postcode'       => array(
      'title'       => __( 'Postcode', 'woosa-in3' ),
      'desc_tip'    => __( 'Specify the checkout field name which will be used as Postcode. Default is "billing_postcode".', 'woosa-in3' ),
      'type'        => 'text',
      'placeholder' => 'billing_postcode',
   ),
   'country'        => array(
      'title'       => __( 'Country', 'woosa-in3' ),
      'desc_tip'    => __( 'Specify the checkout field name which will be used as Country. Default is "billing_country".', 'woosa-in3' ),
      'type'        => 'text',
      'placeholder' => 'billing_country',
   ),
   'city'           => array(
      'title'       => __( 'City', 'woosa-in3' ),
      'desc_tip'    => __( 'Specify the checkout field name which will be used as City. Default is "billing_city".', 'woosa-in3' ),
      'type'        => 'text',
      'placeholder' => 'billing_city',
   ),
);
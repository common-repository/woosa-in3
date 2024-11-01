<?php
/**
 * Add extra payment fields
 *
 * @since 1.0.0
 */

namespace Woosa_IN3;

//prevent direct access data leaks
defined( 'ABSPATH' ) || exit;

$gender = WC()->session->get( PREFIX.'_gender');
$birth_day = WC()->session->get( PREFIX.'_birth_day');
$birth_month = WC()->session->get( PREFIX.'_birth_month');
$birth_year = WC()->session->get( PREFIX.'_birth_year');
$coc_number = WC()->session->get( PREFIX.'_coc_number');

if( is_user_logged_in() ) {
   $gender = get_user_meta(WC()->customer->get_id(), PREFIX.'_gender', true);
   $birth_day = get_user_meta(WC()->customer->get_id(), PREFIX.'_birth_day', true);
   $birth_month = get_user_meta(WC()->customer->get_id(), PREFIX.'_birth_month', true);
   $birth_year = get_user_meta(WC()->customer->get_id(), PREFIX.'_birth_year', true);
   $coc_number = get_user_meta(WC()->customer->get_id(), PREFIX.'_coc_number', true);
}

?>

<div class="<?php echo PREFIX;?>-extra-fields">

   <div class="field-wrapper form-row validate-required">
      <label for="<?php echo PREFIX;?>_gender"><?php _e('Gender', 'woosa-in3');?> <abbr class="required" title="required">*</abbr></label>
      <label style="display:inline-block;"><input type="radio" value="1" name="<?php echo PREFIX;?>_gender" <?php checked($gender, '1');?> /> <?php _e('Male', 'woosa-in3');?></label>
      <label style="display:inline-block;"><input type="radio" value="2" name="<?php echo PREFIX;?>_gender" <?php checked($gender, '2');?> /> <?php _e('Female', 'woosa-in3');?></label>
   </div>

   <?php /*
   <div class="field-wrapper field-wrapper--birthdate form-row validate-required">
      <label for="<?php echo PREFIX;?>_birth_date"><?php _e('Birth date', 'woosa-in3');?> <abbr class="required" title="required">*</abbr></label>
      <input type="text" class="input-text" name="<?php echo PREFIX;?>_birth_date" id="<?php echo PREFIX;?>_birth_date" value="<?php echo esc_attr( $birth_date );?>">
   </div>
   */ ?>
   <div class="field-wrapper field-wrapper--birthdate form-row validate-required">
      <div>
         <span><?php _e('Birth date', 'woosa-in3');?></span>
      </div>
      <div class="in3-birth-fields-wrap">
         <div class="in3-birth-fieds in3-birth-short">
            <label for="<?php echo PREFIX;?>_birth_day"><?php _e('Day', 'woosa-in3');?> <abbr class="required" title="required">*</abbr></label>
            <input type="number" min="1" max="31<?php /* check with month */ ?>" class="input-text" name="<?php echo PREFIX;?>_birth_day" placeholder="1" id="<?php echo PREFIX;?>_birth_day" value="<?php echo esc_attr( $birth_day );?>">
         </div>
         <div class="in3-birth-fieds in3-birth-short">
            <label for="<?php echo PREFIX;?>_birth_month"><?php _e('Month', 'woosa-in3');?> <abbr class="required" title="required">*</abbr></label>
            <input type="number" min="1" max="12" class="input-text" name="<?php echo PREFIX;?>_birth_month" placeholder="1" id="<?php echo PREFIX;?>_birth_month" value="<?php echo esc_attr( $birth_month );?>">
         </div>
         <div class="in3-birth-fieds in3-birth-long">
            <label for="<?php echo PREFIX;?>_birth_year"><?php _e('Year', 'woosa-in3');?> <abbr class="required" title="required">*</abbr></label>
            <input type="number" min="1900" max="2002<?php /* 18 years max */ ?>" class="input-text" name="<?php echo PREFIX;?>_birth_year" placeholder="2000" id="<?php echo PREFIX;?>_birth_year" value="<?php echo esc_attr( $birth_year );?>">
         </div>
      </div>
   </div>

   <div class="field-wrapper form-row" style="display: none;" data-coc-number="true">
      <label for="<?php echo PREFIX;?>_coc_number"><?php _e('CoC Number', 'woosa-in3');?> <abbr class="required" title="required">*</abbr></label>
      <input type="text" class="input-text" name="<?php echo PREFIX;?>_coc_number" id="<?php echo PREFIX;?>_coc_number" value="<?php echo esc_attr( $coc_number );?>">
   </div>

</div>
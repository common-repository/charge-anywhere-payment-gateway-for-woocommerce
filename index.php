<?php
/*
   Plugin Name: Charge Anywhere Payment Gateway For WooCommerce
   Description: Extends WooCommerce to Process Payments with Charge Anywhere version.
   Version: 2.0
   Plugin URI: https://www.chargeanywhere.com/
   Author: Charge Anywhere
   Author URI: https://www.chargeanywhere.com/
   License: Under GPL2
   WC requires at least: 8.3
   WC tested up to: 8.4
*/

//add_action('plugins_loaded', 'woocommerce_tech_chargeanywhere_init', 0);

//function woocommerce_tech_chargeanywhere_init(){}
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC ChargeAnywhere Payment gateway plugin class.
 *
 * @class WC_ChargeAnywhere_Payments
 */
class WC_ChargeAnywhere_Payments {

	/**
	 * Plugin bootstrapping.
	 */
	public static function init() {

		// ChargeAnywhere Payments gateway class.
		add_action( 'plugins_loaded', array( __CLASS__, 'includes' ), 0 );

		// Make the ChargeAnywhere Payments gateway available to WC.
		add_filter( 'woocommerce_payment_gateways', array( __CLASS__, 'add_gateway' ) );

		// Registers WooCommerce Blocks integration.
		add_action( 'woocommerce_blocks_loaded', array( __CLASS__, 'woocommerce_gateway_ChargeAnywhere_woocommerce_block_support' ) );

      

	}

	/**
	 * Add the ChargeAnywhere Payment gateway to the list of available gateways.
	 *
	 * @param array
	 */
	public static function add_gateway( $gateways ) {

		$options = get_option( 'woocommerce_chargeanywhere_settings', array() );

		if ( isset( $options['hide_for_non_admin_users'] ) ) {
			$hide_for_non_admin_users = $options['hide_for_non_admin_users'];
		} else {
			$hide_for_non_admin_users = 'no';
		}

		if ( ( 'yes' === $hide_for_non_admin_users && current_user_can( 'manage_options' ) ) || 'no' === $hide_for_non_admin_users ) {
			$gateways[] = 'WC_Gateway_ChargeAnywhere';
		}
		return $gateways;
	}

	/**
	 * Plugin includes.
	 */
	public static function includes() {

		// Make the WC_Gateway_ChargeAnywhere class available.
		if ( class_exists( 'WC_Payment_Gateway' ) ) {
			require_once 'includes/class-wc-gateway-chargeanywhere.php';
		}
	}

	/**
	 * Plugin url.
	 *
	 * @return string
	 */
	public static function plugin_url() {
		return untrailingslashit( plugins_url( '/', __FILE__ ) );
	}

	/**
	 * Plugin url.
	 *
	 * @return string
	 */
	public static function plugin_abspath() {
		return trailingslashit( plugin_dir_path( __FILE__ ) );
	}

	/**
	 * Registers WooCommerce Blocks integration.
	 *
	 */
	public static function woocommerce_gateway_ChargeAnywhere_woocommerce_block_support() {
		if ( class_exists( 'Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType' ) ) {
			require_once 'includes/blocks/class-wc-chargeanywhere-payments-blocks.php';
			add_action(
				'woocommerce_blocks_payment_method_type_registration',
				function( Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry ) {
					$payment_method_registry->register( new WC_Gateway_ChargeAnywhere_Blocks_Support() );
				}
			);
		}
	}
}

WC_ChargeAnywhere_Payments::init();

   /**
    * Localisation
    */

   load_plugin_textdomain('wc-chargeanywhere', false, dirname(plugin_basename(__FILE__)) . '/languages');

   function custom_script_in_admin()
   {
      wp_register_script('CA_handler_script', plugin_dir_url(__FILE__) . 'assets/chargeanywhere.js', '', true);
      wp_enqueue_script('CA_handler_script');
      wp_register_script('wc-chargeanywhere-script', plugin_dir_url(__FILE__) . '/includes/chargeanywhere-frontend.js', array('jquery', 'jquery-payment'), '1.0', true);
       wp_enqueue_script('wc-chargeanywhere-script');
   }

   add_action('admin_enqueue_scripts', 'custom_script_in_admin');
   add_action('woocommerce_checkout_process', 'validate_payment_form');

   /**
    * Frontend Form Validation
    */
   function validate_payment_form()
   {
      $validationObj = array();
      $validate_str = '';

      if ($_POST['chargeanywhere_option'] == 'credit') {
         if (trim($_POST['chargeanywhere-card-number']) == "") {
            $validate_str .= '<li>Credit card number is required</li>';
         } else {
            $card_number = preg_replace('/\s+/', '', trim($_POST['chargeanywhere-card-number']));
            if (!preg_match('/^[0-9]{12,20}+$/', $card_number)) {
               $validate_str .= '<li>Invalid credit card number</li>';
            }
         }

         if (trim($_POST['chargeanywhere-card-expiry']) == "") {
            $validate_str .= '<li>Expiry card details is required</li>';
         } else {
            $card_expiry = preg_replace('/\s+/', '', trim($_POST['chargeanywhere-card-expiry']));
            if (!preg_match('/^[0-9\/]{5}+$/', $card_expiry)) {
               $validate_str .= '<li>Invalid expiry card details</li>';
            }
         }
         if (trim($_POST['chargeanywhere-card-cvc']) == "") {
            $validate_str .= '<li>Card code is required</li>';
         } else if (!preg_match('/^[0-9]{3,4}+$/', trim($_POST['chargeanywhere-card-cvc']))) {
            $validate_str .= '<li>Invalid card code</li>';
         }
      } else {
         if (trim($_POST['chargeanywhere-account-number']) == "") {
            $validate_str .= '<li>Account number is required</li>';
         } else {
            $card_number = preg_replace('/\s+/', '', trim($_POST['chargeanywhere-account-number']));
            if (!preg_match('/^[0-9]{6,16}+$/', $card_number)) {
               $validate_str .= '<li>Invalid Account number</li>';
            }
         }

         if (trim($_POST['chargeanywhere-routing-number']) == "") {
            $validate_str .= '<li>Routing number is required</li>';
         } else {
            $card_expiry = preg_replace('/\s+/', '', trim($_POST['chargeanywhere-routing-number']));
            if (!preg_match('/^[0-9]{6,12}+$/', $card_expiry)) {
               $validate_str .= '<li>Invalid Routing number</li>';
            }
         }
      }
      if ($validate_str != '') {
         $validationObj['result'] = "failure";
         $validationObj['messages'] = '<ul class="woocommerce-error" role="alert">';
         $validationObj['messages'] .= $validate_str;
         $validationObj['messages'] .= '</ul>';
         $validationObj['refresh'] = false;
         $validationObj['reload'] = false;
         echo json_encode($validationObj);
         exit;
      }
   }

   /**
    * Fee Calculation and showing in frontend
    */
   add_action('woocommerce_cart_calculate_fees', function () {
      global $woocommerce;
      if (is_admin() && !defined('DOING_AJAX')) {
         return;
      }
      $args = array();
      if (isset($_POST['post_data'])) {
         parse_str($_POST['post_data'], $args);
      } else if (isset($_POST)) {
         $args = $_POST;
      }

      $chosen_payment_method = WC()->session->get('chosen_payment_method');
      $options = get_option('woocommerce_chargeanywhere_settings');
      WC()->session->set('credit_service_fee', 0);
      WC()->session->set('credit_convenience_service_fee', 0);
      WC()->session->set('ach_service_fee', 0);
      WC()->session->set('ach_convenience_service_fee', 0);

      if ($chosen_payment_method == 'chargeanywhere') {
         if (isset($args['chargeanywhere_option']) && $args['chargeanywhere_option'] == 'credit') {
            if ($options['accept_credit'] === 'yes' && $options['apply_credit_service'] === 'yes' && $options['credit_service_fee_value'] > 0) {
               $percentage = $options['credit_service_fee_value'] / 100;  // Percentage (5%) in float
               $totalTax = 0;
               $taxes = WC()->cart->get_cart_contents_taxes();
               foreach ($taxes as $tax) $totalTax += $tax;

               //WC()->cart->get_shipping_taxes() fetch only shipping taxes
               //WC()->cart->get_taxes() fetch all taxes
               //+ WC()->cart->get_shipping_total() shipping total func
               $percentage_fee = WC()->cart->get_cart_contents_total() * $percentage;
               if ($options['apply_credit_tax_amount_service_fee'] == 'yes')
                  $percentage_fee = (WC()->cart->get_cart_contents_total() + $totalTax) * $percentage;

               $percentage_fee = wc_format_decimal($percentage_fee);

               $label = " Service Fee";
               if (trim($options['credit_service_fee']) != '')
                  $label = " " . $options['credit_service_fee'];
               WC()->cart->add_fee(__($label, 'chargeanywhere-woocommerce'), $percentage_fee);
               WC()->session->set('credit_service_fee', $percentage_fee);
            }
            if ($options['accept_credit'] === 'yes' && $options['apply_credit_convenience_service'] === 'yes' && $options['credit_convenience_service_fee_value'] > 0) {
               $label = "Convenience Fee";
               if (trim($options['credit_convenience_service_fee']) != '')
                  $label = $options['credit_convenience_service_fee'];
               WC()->cart->add_fee(__($label, 'chargeanywhere-woocommerce'), $options['credit_convenience_service_fee_value']);
               WC()->session->set('credit_convenience_service_fee', $options['credit_convenience_service_fee_value']);
            }
         }
         if (isset($args['chargeanywhere_option']) && $args['chargeanywhere_option'] == 'ach') {
            if ($options['accept_ach'] === 'yes' && $options['apply_ach_service'] === 'yes' && $options['ach_service_fee_value'] > 0) {
               $percentage = $options['ach_service_fee_value'] / 100;  // Percentage (5%) in float

               $totalTax = 0;
               $taxes = WC()->cart->get_cart_contents_taxes();
               foreach ($taxes as $tax) $totalTax += $tax;

               $percentage_fee = WC()->cart->get_cart_contents_total() * $percentage;
               if ($options['apply_ach_tax_amount_service_fee'] == 'yes')
                  $percentage_fee = (WC()->cart->get_cart_contents_total() + $totalTax) * $percentage;

               $percentage_fee = wc_format_decimal($percentage_fee);
               $label = " Service Fee";
               if (trim($options['ach_service_fee']) != '')
                  $label = " " . $options['ach_service_fee'];

               WC()->cart->add_fee(__($label, 'chargeanywhere-woocommerce'), $percentage_fee);
               WC()->session->set('ach_service_fee', $percentage_fee);
            }
            if ($options['accept_ach'] === 'yes' && $options['apply_ach_convenience_service'] === 'yes' && $options['ach_convenience_service_fee_value'] > 0) {
               $label = "Convenience Fee";
               if (trim($options['ach_convenience_service_fee']) != '')
                  $label = $options['ach_convenience_service_fee'];

               WC()->cart->add_fee(__($label, 'chargeanywhere-woocommerce'), $options['ach_convenience_service_fee_value']);
               WC()->session->set('ach_convenience_service_fee', $options['ach_convenience_service_fee_value']);
            }
         }
      }
   });

   add_action('admin_footer', function () {
      $currentPostType = get_post_type();
      $order_id = get_the_ID();
      $tran_meta = get_post_meta($order_id, '_chargeanywhere_transaction', true);
      $options = get_option('woocommerce_chargeanywhere_settings');
      if ($currentPostType != 'shop_order') return;
      if ($tran_meta['chargeanywhere_option'] == 'credit') {
         if ($options['credit_refund_service_fee'] == 'no')
            return;
      }
      if ($tran_meta['chargeanywhere_option'] == 'ach') {
         if ($options['ach_refund_service_fee'] == 'no')
            return;
      }
      ?>
      <script type="text/javascript">
         (function($) {
      $(document).ready(function() {
         $('#order_fee_line_items .refund_line_total,#order_fee_line_items .refund_line_tax').attr('disabled', 'disabled');
      });
            })(jQuery);
         </script>
      <?php
   });

   add_action('woocommerce_review_order_before_payment', function () {
      ?><script type="text/javascript">
      (function($) {
      $('form.checkout').on('change', 'input[name^="payment_method"]', function() {
         $('body').trigger('update_checkout');
      });
      })(jQuery);
      </script><?php
   });

   /**
    * Add this Gateway to WooCommerce
      **/
 /*  function woocommerce_add_tech_chargeanywhere_gateway($methods)
   {
      $methods[] = 'WC_ChargeAnyWhere_SIP';
      return $methods;
   }*/
 //  add_filter('woocommerce_payment_gateways', 'woocommerce_add_tech_chargeanywhere_gateway');
      
         add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'chargeAnyWhereSIP_action_links');
         function chargeAnyWhereSIP_action_links($links)
         {
            $chargeSIP_links = array(
               '<a href="admin.php?page=wc-settings&tab=checkout&section=chargeanywhere" >Settings</a>',
               '<a href="http://www.chargeanywhere.com" target="_blank">Support</a>'
            );
            return array_merge($links, $chargeSIP_links);
         }

         // remove fees sort
         add_action('woocommerce_sort_fees_callback', 'ca_sort_fees', 10, 3);
         function ca_sort_fees($order, $a, $b)
         {
            return $a->name > $b->name ? 1 : -1;
         }

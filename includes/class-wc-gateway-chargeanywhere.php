<?php
/**
 * WC_Gateway_ChargeAnywhere class
 *
 * @author   Rishabh Rajvanshi <rrajvan@chargeanywhere.com>
 * @package  WooCommerce Charge Anywhere Payments Gateway
 * @since    1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Charge Anywhere Gateway.
 *
 * @class    WC_Gateway_ChargeAnywhere
 * @version  1.0.5
 */
class WC_Gateway_ChargeAnywhere extends WC_Payment_Gateway 
{
    protected $msg = array();

    public function __construct()
    {
       $this->id               = 'chargeanywhere';
       $this->method_title     = __('Charge Anywhere', 'chargeanywhere-woocommerce');
       $this->method_description = __( 'Charge Anywhere Payment Gateway for WooCommerce. Accepts Credit Cards and ACH payments.', 'woocommerce-gateway-dummy' );
       // $this->icon             = WP_PLUGIN_URL . "/" . plugin_basename(dirname(__FILE__)) . '/images/logo.gif';
       $this->has_fields       = false;
       $this->init_form_fields();
       $this->init_settings();
       $this->title            = $this->settings['title'];
       $this->description      = $this->settings['description'];
       $this->merchant_id      = $this->settings['merchant_id'];
       $this->mode             = $this->settings['working_mode'];
       $this->transaction_mode = $this->settings['transaction_mode'];
       $this->terminal_id      = $this->settings['terminal_id'];
       $this->secret_key       = $this->settings['secret_key'];

       $this->accept_credit    = $this->settings['accept_credit'];
       $this->apply_credit_service = $this->settings['apply_credit_service'];
       $this->credit_service_fee = $this->settings['credit_service_fee'];
       $this->credit_service_fee_value = $this->settings['credit_service_fee_value'];

       $this->accept_ach       = $this->settings['accept_ach'];
       $this->apply_ach_service = $this->settings['apply_ach_service'];
       $this->ach_service_fee  = $this->settings['ach_service_fee'];
       $this->ach_service_fee_value = $this->settings['ach_service_fee_value'];

       $this->success_message  = $this->settings['success_message'];
       $this->failed_message   = $this->settings['failed_message'];
       $this->email_customer   = 'false';
       $this->email_merchant   = 'false';
       $this->log_request      = 'false';
       $this->redirect_message = '';

       if (isset($this->settings['email_customer']) && $this->settings['email_customer'] == 'yes')
          $this->email_customer = 'true';

       if (isset($this->settings['email_merchant']) && $this->settings['email_merchant'] == 'yes')
          $this->email_merchant = 'true';

       if (isset($this->settings['log_request']) && $this->settings['log_request'] == 'yes')
          $this->log_request    = 'true';

       if (isset($this->settings['redirect_message']))
          $this->redirect_message  = $this->settings['redirect_message'];

       $this->live_url_transaction   = 'https://www.chargeanywhere.com/APIs/PaymentFormSIP.aspx';
       $this->test_url_transaction   = 'https://webtest.chargeanywhere.com/APIs/PaymentFormSIP.aspx';

       $this->live_url_process       = 'https://www.chargeanywhere.com/APIs/PaymentFormAIP.aspx';
       $this->test_url_process       = 'https://webtest.chargeanywhere.com/APIs/PaymentFormAIP.aspx';

       $this->msg['message']   = "";
       $this->msg['class']     = "";
       $this->newrelay         = home_url() . '/wc-api/WC_Gateway_ChargeAnywhere';


       //add_action('init', array(&$this, 'check_authorize_response'));
       //update for woocommerce >2.0
       add_action('woocommerce_order_status_completed', array($this, 'process_capture'));
       add_action('woocommerce_api_wc_gateway_chargeanywhere', array($this, 'check_authorize_response'));
       add_action('valid-authorize-request', array(&$this, 'successful_request'));


       if (version_compare(WOOCOMMERCE_VERSION, '2.0.0', '>=')) {
          add_action('woocommerce_update_options_payment_gateways_' . $this->id, array(&$this, 'process_admin_options'));
       } else {
          add_action('woocommerce_update_options_payment_gateways', array(&$this, 'process_admin_options'));
       }

       add_action('woocommerce_receipt_chargeanywhere', array(&$this, 'receipt_page'));
       // add_action('woocommerce_thankyou_authorize',array(&$this, 'thankyou_page'));
       $this->supports = array('products', 'refunds');
    }

    /**
     * process_refund function.
     *
     * @access public
     * @param int $order_id
     * @param float $amount
     * @param string $reason
     * @return bool|WP_Error
     */
    public function process_refund($order_id, $amount = NULL, $reason = '')
    {
       global $wpdb;
       $options = get_option('woocommerce_chargeanywhere_settings');
       $order = wc_get_order($order_id);

       $tran_meta = get_post_meta($order_id, '_chargeanywhere_transaction', true);
       $chargeanywherefee_details = (array) json_decode($tran_meta['chargeanywhere_fee_details']);

       $line_item_totals       = isset($_POST['line_item_totals']) ? json_decode(sanitize_text_field(wp_unslash($_POST['line_item_totals'])), true) : array();
       $line_item_tax_totals   = isset($_POST['line_item_tax_totals']) ? json_decode(sanitize_text_field(wp_unslash($_POST['line_item_tax_totals'])), true) : array();

       //$subtotal = $order->get_subtotal(); 
       //$taxes = $order->get_total_tax();

       $item_total_amount = 0;
       foreach ($order->get_items() as $item) {
          $item_total_amount += $item->get_total() + $item->get_total_tax(); // Discounted total with tax
       }

       $current_tax = 0;
       $current_shipping = 0;
       $current_shipping_tax = 0;
       $current_cart = 0;
       $prev_tax = 0;
       $prev_shipping = 0;
       $prev_shipping_tax = 0;
       $prev_cart = 0;
       $totalAmountWithOutFees = 0;
       foreach ($order->get_refunds() as $ind => $item) {
          if ($ind == 0) {
             $current_tax = (-1 * $item->get_cart_tax()); //cart_tax
             $current_shipping = (-1 * $item->get_shipping_total()); //shipping_total
             $current_shipping_tax = (-1 * $item->get_shipping_tax()); //shipping_tax
             $current_cart = (-1 * $item->get_total()); //amount
             $totalAmountWithOutFees = $current_cart;
             $current_cart = $current_cart - $current_tax - $current_shipping - $current_shipping_tax;
          } else {
             $prev_tax += (-1 * $item->get_cart_tax());
             $prev_shipping += (-1 * $item->get_shipping_total());
             $prev_shipping_tax += (-1 * $item->get_shipping_tax());
             $prev_cart += (-1 * $item->get_total());
             $totalAmountWithOutFees += $prev_cart;
             $prev_cart = $prev_cart - $prev_tax - $prev_shipping - $prev_shipping_tax;
          }
       }

       // echo $current_tax." -- ".$current_shipping." -- ".$current_shipping_tax." -- ".$current_cart;
       // echo $prev_tax." -- ".$prev_shipping." -- ".$prev_shipping_tax." -- ".$prev_cart;
       // echo "\n".($current_tax+$current_shipping+$current_shipping_tax+$current_cart+$prev_tax+$prev_shipping+$prev_shipping_tax+$prev_cart)."--".$order->get_total();

       /* $totalShipping = 0;
       foreach( $order->get_items( 'shipping' ) as $item_id => $item ){
          $totalShipping += $item->get_total() + $item->get_total_tax();
       } */

       // $amount = $amount + $totalShipping;
       $fully_refund = false;
       $order_fee_total = 0;
       $qty_refunded = 0;
       $prod_items = array();
       $amt = 0;
       $refundTotalAmt = 0;
       $refundTaxAmt = 0;


       foreach ($order->get_items() as $item_id => $item) {
          $prod_items[] = $item_id;
          $qty = $order->get_qty_refunded_for_item($item_id);

          $refundTotalAmt += $order->get_total_refunded_for_item($item_id);
          //$refundTaxAmt += $order->get_tax_refunded_for_item($item_id);
          $qty_refunded += $qty;
       }

       foreach ($line_item_totals as $ind => $val) {
          if (in_array($ind, $prod_items))
             $amt += $val;
       }

       foreach ($line_item_tax_totals as $ind => $values) {
          if (in_array($ind, $prod_items)) {
             foreach ($values as $i => $val) {
                $amt += $val;
             }
          }
       }

       $total_items_price_excluding_tax = 0;
       foreach ($line_item_totals  as $key => $value) {
          $total_items_price_excluding_tax += $value;
       }

       $feeItems = $order->get_items(array('fee'));

       $chargeanywhere_feeitems_mapped = array();
       $refund_fee_items = array();
       foreach ($feeItems as $item_id => $item) {
          $item_data = $item->get_data();

          if (strtolower($options['credit_service_fee']) == "")
             $options['credit_service_fee'] = 'service fee';
          if (strtolower($options['credit_convenience_service_fee']) == "")
             $options['credit_convenience_service_fee'] = 'convenience fee';

          if (strtolower($options['ach_service_fee']) == "")
             $options['ach_service_fee'] = 'service fee';
          if (strtolower($options['ach_convenience_service_fee']) == "")
             $options['ach_convenience_service_fee'] = 'convenience fee';

          if ($tran_meta['chargeanywhere_option'] == 'credit') {
             if (strtolower(trim($options['credit_service_fee'])) == strtolower(trim($item_data['name']))) {
                $chargeanywhere_feeitems_mapped['credit_service_fee']['id'] = $item_data['id'];
                $chargeanywhere_feeitems_mapped['credit_service_fee']['amount'] = $item_data['total'];
             }
             if (strtolower(trim($options['credit_convenience_service_fee'])) == strtolower(trim($item_data['name']))) {
                $chargeanywhere_feeitems_mapped['credit_convenience_service_fee']['id'] = $item_data['id'];
                $chargeanywhere_feeitems_mapped['credit_convenience_service_fee']['amount'] = $item_data['total'];
             }
          } else {
             if (strtolower(trim($options['ach_service_fee'])) == strtolower(trim($item_data['name']))) {
                $chargeanywhere_feeitems_mapped['ach_service_fee']['id'] = $item_data['id'];
                $chargeanywhere_feeitems_mapped['ach_service_fee']['amount'] = $item_data['total'];
             }
             if (strtolower(trim($options['ach_convenience_service_fee'])) == strtolower(trim($item_data['name']))) {
                $chargeanywhere_feeitems_mapped['ach_convenience_service_fee']['id'] = $item_data['id'];
                $chargeanywhere_feeitems_mapped['ach_convenience_service_fee']['amount'] = $item_data['total'];
             }
          }
       }

       //$fee_details = array();
       foreach ($order->get_fees() as $fee_id => $fee) {
          $order_fee_total += $fee->get_total();
          //$ff[$fee_id] = $fee->get_total();
       }
       $total = $order->get_total();
       //$pending_taxes = $total - $order_total;

       $order_tax = $current_shipping_tax + $current_tax;
       /*foreach($order->get_items('tax') as $tax){
       $order_tax += $tax->get_tax_total() ;
       }*/

       $order_ship = $current_shipping;
       /*foreach($order->get_items('shipping') as $ship){
       $order_ship += $ship->get_total() ;
       }*/

       //echo $refundTotalAmt." -- ".$amt." ==".$order_fee_total. " ---".$total." ====".$totalAmountWithOutFees;
       //echo "ddd";
       $inc = 0;
       $service_amount = 0;

       if ($tran_meta['chargeanywhere_option'] == 'credit' && $options['credit_refund_service_fee'] == 'yes') {
          $service_amount = (float) round((($amt / $item_total_amount) * $chargeanywhere_feeitems_mapped['credit_service_fee']['amount']), 2); //$tran_meta['fee'];
          $refund_fee_items[$inc]['id'] =  $chargeanywhere_feeitems_mapped['credit_service_fee']['id'];
          $refund_fee_items[$inc]['amount'] =   $service_amount;
          $inc++;
       }
       if ($tran_meta['chargeanywhere_option'] == 'ach' && $options['ach_refund_service_fee'] == 'yes') {
          $service_amount = (float) round((($amt / $item_total_amount) * $chargeanywhere_feeitems_mapped['ach_service_fee']['amount']), 2);
          $refund_fee_items[$inc]['id'] =  $chargeanywhere_feeitems_mapped['ach_service_fee']['id'];
          $refund_fee_items[$inc]['amount'] =  $service_amount;
          $inc++;
       }

       $totalRefund = 0;
       $totalRefundTax = 0;
       foreach ($order->get_refunds() as $item) {
          if ($item->get_reason() == 'Adjustment to close the order')
             $totalRefundTax += $item->get_total();
          else
             $totalRefund += $item->get_total();
       }

       // echo $totalRefundTax."--".$totalRefund;
       $total_quantity = $order->get_item_count();
       $qty_refunded = $qty_refunded * (-1);

       $conAmt = 0;
       if ($total_quantity == $qty_refunded) {
          if ($tran_meta['chargeanywhere_option'] == 'credit' && $options['credit_refund_service_fee'] == 'yes') {
             if ($service_amount > 0) {
                if (round($chargeanywhere_feeitems_mapped['credit_service_fee']['amount'], 2) <= ($service_amount + ($totalRefundTax * -1)))
                   $refund_fee_items[$inc - 1]['amount'] = round($chargeanywhere_feeitems_mapped['credit_service_fee']['amount'], 2) -  ($totalRefundTax * -1);
             }
          }

          if ($tran_meta['chargeanywhere_option'] == 'credit' && $options['credit_refund_convenience_fee'] == 'yes') {
             $service_amount += $chargeanywherefee_details['credit_convenience_service_fee'];
             $refund_fee_items[$inc]['id'] =  $chargeanywhere_feeitems_mapped['credit_convenience_service_fee']['id'];
             $refund_fee_items[$inc]['amount'] =  $chargeanywhere_feeitems_mapped['credit_convenience_service_fee']['amount'];
             $conAmt = $refund_fee_items[$inc]['amount'];
          }

          if ($tran_meta['chargeanywhere_option'] == 'ach' && $options['ach_refund_service_fee'] == 'yes') {
             if ($service_amount > 0) {
                if (round($chargeanywhere_feeitems_mapped['ach_service_fee']['amount'], 2) <= ($service_amount + ($totalRefundTax * -1)))
                   $refund_fee_items[$inc - 1]['amount'] = round($chargeanywhere_feeitems_mapped['ach_service_fee']['amount'], 2) -  ($totalRefundTax * -1);
             }
          }

          if ($tran_meta['chargeanywhere_option'] == 'ach' && $options['ach_refund_convenience_fee'] == 'yes') {
             $service_amount += $chargeanywherefee_details['ach_convenience_service_fee'];
             $refund_fee_items[$inc]['id'] =  $chargeanywhere_feeitems_mapped['ach_convenience_service_fee']['id'];
             $refund_fee_items[$inc]['amount'] =  $chargeanywhere_feeitems_mapped['ach_convenience_service_fee']['amount'];
             $conAmt = $refund_fee_items[$inc]['amount'];
          }
          $fully_refund = true;
       }

       if ($amount > 0) {
          try {
             $response = $this->refund($this, $order, $amount, $service_amount, array("tax" => $order_tax, "shipping" => $order_ship));

             if (isset($response['ResponseCode']) && '000' == $response['ResponseCode']) {
                $refunded_amount = number_format($amount, '2', '.', '');
                $line_items = array();
                $total_line_item_amount = 0;
                if (count($refund_fee_items) > 0) {
                   foreach ($refund_fee_items as $key => $value) {
                      $line_items[$value['id']]['qty'] = 0;
                      $line_items[$value['id']]['refund_total'] = $value['amount'] * -1;
                      $line_items[$value['id']]['refund_tax'] = array();
                      $total_line_item_amount += $value['amount'];
                   }
                   /*foreach( $order->get_items( 'shipping' ) as $item_id => $item ){
                   $line_items[$item_id]['qty'] = 0;
                   $line_items[$item_id]['refund_total'] = $totalShipping * -1;
                   $line_items[$item_id]['refund_tax'] = array();
                   $total_line_item_amount += $totalShipping;
                }*/
                   $refund = wc_create_refund(
                      array(
                         'amount'         => strval($total_line_item_amount),
                         'reason'         => 'Adjustment to close the order',
                         'order_id'       => $order_id,
                         'line_items'     => $line_items,
                         'restock_items'  => true,
                      )
                   );
                }
                $refunded_amount += $total_line_item_amount;
                if ($fully_refund) {
                   $tran_meta = get_post_meta($order_id, '_chargeanywhere_transaction', true);
                   $tran_meta['transaction_type'] = 'complete';
                   update_post_meta($order_id, '_chargeanywhere_transaction', $tran_meta);

                   if ($order->get_total() != wc_format_decimal($totalAmountWithOutFees + $conAmt, wc_get_price_decimals()))
                      $order->update_status('refunded', sprintf(__('Payment %s via IPN.', 'woocommerce'), strtolower($posted['payment_status'])));
                }

                $order->add_order_note(sprintf(__('Charge Anywhere refund completed for %s. Refund ID: %s', 'woocommerce-cardpay-chargeanywhere'), $refunded_amount, $response['ReferenceNumber']));
                return true;
             } else {
                $order->add_order_note($response['ResponseCode'] . "-" . $response['ResponseText']);
                throw new Exception(__('Charge Anywhere refund attempt failed.', 'woocommerce-cardpay-chargeanywhere'));
             }
          } catch (Exception $e) {
             $order->add_order_note($e->getMessage());
             return new WP_Error('chargeanywhere_error', $e->getMessage());
          }
       } else {
          return false;
       }
    }

    /**
     * refund function
     * 
     * @param WC_Order                   $order
     * @param float                      $amount
     * 
     * @return mixed
     */
    public function refund($gateway, $order, $amount, $service_amount, $extra)
    {
       $payload = $this->get_payload($gateway, $order, $amount, $service_amount, $extra, 'refundTransaction');
       if ($payload != false) {
          $response = $this->post_transaction($payload);
          return $response;
       }
       return true;
    }

    /**
     * Admin Charge any where Settings form
     */
    function init_form_fields()
    {
       $this->form_fields = array(
          'enabled'      => array(
             'title'        => __('Enable/Disable', 'chargeanywhere-woocommerce'),
             'type'         => 'checkbox',
             'label'        => __('Enable Charge Anywhere Payment Module.', 'chargeanywhere-woocommerce'),
             'default'      => 'no'
          ),
          'title'        => array(
             'title'        => __('Title:', 'chargeanywhere-woocommerce'),
             'type'         => 'text',
             'description'  => __('This controls the title which the user sees during checkout.', 'chargeanywhere-woocommerce'),
             'default'      => __('Charge Anywhere Gateway', 'chargeanywhere-woocommerce')
          ),
          'description'  => array(
             'title'        => __('Description:', 'chargeanywhere-woocommerce'),
             'type'         => 'textarea',
             'description'  => __('This controls the description which the user sees during checkout.', 'chargeanywhere-woocommerce'),
             'default'      => __('Pay securely by Credit or Debit Card through Charge Anywhere Secure Servers.', 'chargeanywhere-woocommerce')
          ),
          'merchant_id'     => array(
             'title'        => __('Merchant ID', 'chargeanywhere-woocommerce'),
             'type'         => 'text',
             'required'     => true,
             'description'  => __('This is Merchant ID')
          ),
          'terminal_id' => array(
             'title'        => __('Terminal ID', 'chargeanywhere-woocommerce'),
             'type'         => 'text',
             'description'  =>  __('This is Terminal ID', 'chargeanywhere-woocommerce')
          ),
          'secret_key' => array(
             'title'        => __('Secret', 'chargeanywhere-woocommerce'),
             'type'         => 'text',
             'description'  =>  __('Secret used to Authenticate the request with Charge Anywhere', 'chargeanywhere-woocommerce')
          ),
          'success_message' => array(
             'title'        => __('Transaction Success Message', 'chargeanywhere-woocommerce'),
             'type'         => 'textarea',
             'description' =>  __('Message to be displayed on successful transaction.', 'chargeanywhere-woocommerce'),
             'default'      => __('Your payment has been processed successfully.', 'chargeanywhere-woocommerce')
          ),
          'failed_message'  => array(
             'title'        => __('Transaction Failed Message', 'chargeanywhere-woocommerce'),
             'type'         => 'textarea',
             'description'  =>  __('Message to be displayed on failed transaction.', 'chargeanywhere-woocommerce'),
             'default'      => __('Your transaction has been declined.', 'chargeanywhere-woocommerce')
          ),
          'redirect_message'  => array(
             'title'        => __('Transaction Redirect Message', 'chargeanywhere-woocommerce'),
             'type'         => 'textarea',
             'description'  =>  __('Message to be displayed on payment redirection.', 'chargeanywhere-woocommerce'),
             'default'      => __('Thank you for your order. We are now redirecting you to Charge Anywhere to make payment.', 'chargeanywhere-woocommerce')
          ),
          'working_mode'    => array(
             'title'        => __('API Mode'),
             'type'         => 'select',
             'options'      => array('false' => 'Live/Production Mode', 'true' => 'Sandbox/Developer API Mode'),
             'description'  => "Live or Production / Sandbox Mode (Sandbox Mode requires Sandbox Account API.)"
          ),
          'transaction_mode'    => array(
             'title'        => __('Transaction Mode'),
             'type'         => 'select',
             'options'      => array('auth_capture' => 'Authorize and Capture', 'authorize' => 'Authorize Only'),
             'description'  => "Transaction Mode. If you are not sure what to use set to Authorize and Capture"
          ),
          'email_customer'      => array(
             'title'        => __('Email To Customer', 'chargeanywhere-woocommerce'),
             'type'         => 'checkbox',
             'label'        => __('Enable Sending Default Email To Customer From ChargeAnyWhere', 'chargeanywhere-woocommerce'),
             'default'      => 'no'
          ),
          'email_merchant'      => array(
             'title'        => __('Email To Merchant', 'chargeanywhere-woocommerce'),
             'type'         => 'checkbox',
             'label'        => __('Enable Sending Default Email To Merchant From ChargeAnyWhere', 'chargeanywhere-woocommerce'),
             'default'      => 'no'
          ),
          'log_request'      => array(
             'title'        => __('Enable Log request and response', 'chargeanywhere-woocommerce'),
             'type'         => 'checkbox',
             'label'        => __('Enable Log request and response', 'chargeanywhere-woocommerce'),
             'default'      => 'no'
          ),
          'accept_credit'      => array(
             'title'        => __('Accept Credit', 'chargeanywhere-woocommerce'),
             'type'         => 'checkbox',
             'label'        => __('', 'chargeanywhere-woocommerce'),
             'default'      => 'no'
          ),
          'apply_credit_service'      => array(
             'title'        => __('', 'chargeanywhere-woocommerce'),
             'type'         => 'checkbox',
             'label'        => __('Add Service Fee', 'chargeanywhere-woocommerce'),
             'default'      => 'no'
          ),
          'credit_service_fee'      => array(
             'title'        => __('Service Fee Label', 'chargeanywhere-woocommerce'),
             'type'         => 'text',
             'label'        => __('Service Fee Label', 'chargeanywhere-woocommerce'),
             'default'      => ''
          ),
          'credit_service_fee_value'      => array(
             'title'        => __('Service Fee Percentage (%)', 'chargeanywhere-woocommerce'),
             'type'         => 'text',
             'label'        => __('Service Fee Percentage', 'chargeanywhere-woocommerce'),
             'default'      => ''
          ),
          'apply_credit_tax_amount_service_fee'      => array(
             'title'        => __('', 'chargeanywhere-woocommerce'),
             'type'         => 'checkbox',
             'label'        => __('Include Tax Amount in Service Fee Calculation', 'chargeanywhere-woocommerce'),
             'default'      => 'no'
          ),
          'credit_refund_service_fee'      => array(
             'title'        => __('', 'chargeanywhere-woocommerce'),
             'type'         => 'checkbox',
             'label'        => __('Refund Service Fee on a Return', 'chargeanywhere-woocommerce'),
             'default'      => 'no'
          ),
          'apply_credit_convenience_service'      => array(
             'title'        => __('', 'chargeanywhere-woocommerce'),
             'type'         => 'checkbox',
             'label'        => __('Add Convenience Fee', 'chargeanywhere-woocommerce'),
             'default'      => 'no'
          ),
          'credit_convenience_service_fee'      => array(
             'title'        => __('Convenience Fee Label', 'chargeanywhere-woocommerce'),
             'type'         => 'text',
             'label'        => __('Convenience Fee Label', 'chargeanywhere-woocommerce'),
             'default'      => ''
          ),
          'credit_convenience_service_fee_value'      => array(
             'title'        => __('Convenience Fee Amount ($)', 'chargeanywhere-woocommerce'),
             'type'         => 'text',
             'label'        => __('Convenience Fee Amount', 'chargeanywhere-woocommerce'),
             'default'      => ''
          ),
          'credit_refund_convenience_fee'      => array(
             'title'        => __('', 'chargeanywhere-woocommerce'),
             'type'         => 'checkbox',
             'label'        => __('Refund Convenience Fee on a Return', 'chargeanywhere-woocommerce'),
             'default'      => 'no'
          ),
          'accept_ach'      => array(
             'title'        => __('Accept ACH', 'chargeanywhere-woocommerce'),
             'type'         => 'checkbox',
             'label'        => __('', 'chargeanywhere-woocommerce'),
             'default'      => 'no'
          ),
          'apply_ach_service'      => array(
             'title'        => __('', 'chargeanywhere-woocommerce'),
             'type'         => 'checkbox',
             'label'        => __('Add Service fee', 'chargeanywhere-woocommerce'),
             'default'      => 'no'
          ),
          'ach_service_fee'      => array(
             'title'        => __('Service Fee Label', 'chargeanywhere-woocommerce'),
             'type'         => 'text',
             'label'        => __('Service Fee Label', 'chargeanywhere-woocommerce'),
             'default'      => ''
          ),
          'ach_service_fee_value'      => array(
             'title'        => __('Service Fee Percentage (%)', 'chargeanywhere-woocommerce'),
             'type'         => 'text',
             'label'        => __('Service Fee Percentage', 'chargeanywhere-woocommerce'),
             'default'      => ''
          ),
          'apply_ach_tax_amount_service_fee'      => array(
             'title'        => __('', 'chargeanywhere-woocommerce'),
             'type'         => 'checkbox',
             'label'        => __('Include Tax Amount in Service Fee Calculation', 'chargeanywhere-woocommerce'),
             'default'      => 'no'
          ),
          'ach_refund_service_fee'      => array(
             'title'        => __('', 'chargeanywhere-woocommerce'),
             'type'         => 'checkbox',
             'label'        => __('Refund Service Fee on a Return', 'chargeanywhere-woocommerce'),
             'default'      => 'no'
          ),
          'apply_ach_convenience_service'      => array(
             'title'        => __('', 'chargeanywhere-woocommerce'),
             'type'         => 'checkbox',
             'label'        => __('Add Convenience Fee', 'chargeanywhere-woocommerce'),
             'default'      => 'no'
          ),
          'ach_convenience_service_fee'      => array(
             'title'        => __('Convenience Fee Label', 'chargeanywhere-woocommerce'),
             'type'         => 'text',
             'label'        => __('Convenience Fee Label', 'chargeanywhere-woocommerce'),
             'default'      => ''
          ),
          'ach_convenience_service_fee_value'      => array(
             'title'        => __('Convenience Fee Amount ($)', 'chargeanywhere-woocommerce'),
             'type'         => 'text',
             'label'        => __('Convenience Fee Amount', 'chargeanywhere-woocommerce'),
             'default'      => ''
          ),
          'ach_refund_convenience_fee'      => array(
             'title'        => __('', 'chargeanywhere-woocommerce'),
             'type'         => 'checkbox',
             'label'        => __('Refund Convenience Fee on a Return', 'chargeanywhere-woocommerce'),
             'default'      => 'no'
          ),
       );
    }

    /** 
     * Validation for Merchant ID
     */
    public function validate_merchant_id_field($key, $value)
    {
       if (trim($value) == '') {
          WC_Admin_Settings::add_error(esc_html__('The Merchant Id doesn\'t look like a correct one.'));
       }
       return $value;
    }

    /**
     * Validation for Terminal ID
     */
    public function validate_terminal_id_field($key, $value)
    {
       if (trim($value) == '') {
          WC_Admin_Settings::add_error(esc_html__('The Terminal Id doesn\'t look like a correct one.'));
       }
       return $value;
    }

    /**
     * Validation for Secret Key
     */
    public function validate_secret_key_field($key, $value)
    {
       if (trim($value) == '') {
          WC_Admin_Settings::add_error(esc_html__('The Secret Key doesn\'t look like a correct one.'));
       }
       return $value;
    }

    public function validate_credit_service_fee_field($key, $value)
    {
       if (preg_match("/tax/i", $value)) {
          WC_Admin_Settings::add_error(esc_html__('Credit Service Fee Label should not contain word tax'));
          $value = ''; // empty it because it is not correct
       }
       return $value;
    }

    public function validate_credit_convenience_service_fee_field($key, $value)
    {
       if (preg_match("/tax/i", $value)) {
          WC_Admin_Settings::add_error(esc_html__('Credit Convenience Service Fee Label should not contain word tax'));
          $value = ''; // empty it because it is not correct
       }
       return $value;
    }

    public function validate_ach_service_fee_field($key, $value)
    {
       if (preg_match("/tax/i", $value)) {
          WC_Admin_Settings::add_error(esc_html__('ACH Service Fee Label should not contain word tax'));
          $value = ''; // empty it because it is not correct
       }
       return $value;
    }

    public function validate_ach_convenience_service_fee_field($key, $value)
    {
       if (preg_match("/tax/i", $value)) {
          WC_Admin_Settings::add_error(esc_html__('ACH Convenience Service Fee Label should not contain word tax'));
          $value = ''; // empty it because it is not correct
       }
       return $value;
    }



    /**
     * Admin Panel Options
     * - Options for bits like 'title' and availability on a country-by-country basis
     **/
    public function admin_options()
    {
       echo '<h3>' . __('Charge Anywhere Payment Gateway', 'chargeanywhere-woocommerce') . '</h3>';
       echo '<table class="form-table">';
       $this->generate_settings_html();
       echo '</table>';
    }

    /**
     *  There are no payment fields for ChargeAnyWhere, but want to show the description if set.
     **/
    function payment_fields()
    {
       if (isset($_GET['ca-error'])) {
          wc_add_notice(__($_GET['ca-error'], 'woothemes'), 'error');
       }
       
       if ($this->description) {
          //if ( 'true' != $this->mode ) {
          echo wpautop(wptexturize(__($this->description, 'chargeanywhere-woocommerce')));
          //}
       }
       include('credit_card.php');
    }

    /**
     * process_capture function.
     *
     * @access public
     * @param int $order_id
     * @return bool
     */
    public function process_capture($order_id)
    {

       $order = wc_get_order($order_id);
       // Return if another payment method was used
       $payment_method = version_compare(WC_VERSION, '3.0.0', '<') ? $order->payment_method : $order->get_payment_method();
       if ($payment_method != $this->id) {
          return;
       }

       // Attempt to process the capture
       $tran_meta = get_post_meta($order_id, '_chargeanywhere_transaction', true);

       $orig_tran_type = $tran_meta['transaction_type'];
       $amount = $order->get_total();
       $order_tax = $order->get_total_tax();

       $order_ship = 0;
       foreach ($order->get_items('shipping') as $ship) {
          $order_ship += $ship->get_total();
       }

       $service_amount = 0;
       foreach ($order->get_fees() as $fee_id => $fee) {
          $service_amount += $fee->get_total();
       }

       if ('authorize' == $orig_tran_type) {
          try {
             $amt = $amount - $service_amount;
             $response = $this->capture($this, $order, $amt, $service_amount, array("tax" => $order_tax, "shipping" => $order_ship));
             if (is_wp_error($response)) {
                throw new Exception($response->get_error_message());
             }

             if (isset($response['ResponseCode']) && '000' == $response['ResponseCode']) {

                $tran_meta = get_post_meta($order_id, '_chargeanywhere_transaction', true);
                $tran_meta['transaction_type'] = 'auth_capture';
                update_post_meta($order_id, '_chargeanywhere_transaction', $tran_meta);

                $captured_amount = number_format($amount, '2', '.', '');
                $order->add_order_note(sprintf(__('Charge Anywhere auto capture completed for %s. Capture ID: %s', 'chargeanywhere-woocommerce'), $captured_amount, $response['ReferenceNumber']));
                return true;
             } else {
                throw new Exception(__('Charge Anywhere auto capture failed. Log into your gateway to manually process the capture.', 'chargeanywhere-woocommerce'));
             }
          } catch (Exception $e) {
             $order->add_order_note($e->getMessage());
             return true;
          }
       }
    }

    /**
     * capture function
     * 
     * @param WC_Order                   $order
     * @param float                      $amount
     * 
     * @return mixed
     */
    public function capture($gateway, $order, $amount, $service_amount, $extra)
    {
       $payload = $this->get_payload($gateway, $order, $amount, $service_amount, $extra, 'priorAuthCaptureTransaction');
       if ($payload != false) {
          $response = $this->post_transaction($payload);
          return $response;
       }
       return true;
    }

    /**
     * get_payload function
     * 
     * @param WC_Order                   $order
     * @param float                      $amount
     * @param string                     $transaction_type
     * 
     * @return string
     */
    public function get_payload($gateway, $order, $amount, $service_amount, $extra, $transaction_type)
    {
       $order_number = $order->get_id();
       $tran_meta = get_post_meta($order_number, '_chargeanywhere_transaction', true);
       $vSeed = $this->seed_gen();
       $data = array();

       if ($service_amount < 0)
          $service_amount = -1 * $service_amount;
       if ($transaction_type == 'priorAuthCaptureTransaction') {
          if (trim($tran_meta['transaction_type']) != 'authorize') return false;
          $data = array(
             'CustomerNumber' => wc_clean($order->get_customer_id()),
             'Secret' => $gateway->secret_key,
             'MerchantId' => $gateway->merchant_id,
             'TerminalId' => $gateway->terminal_id,
             'InvoiceNumber' => $order_number,
             'Seed' => $vSeed,
             'Mode' => '2',
             'Version' => '2.0',
             'TransactionType' => 'Force',
             'Tax' => $extra['tax'],
             'Shipping' => $extra['shipping'],
             'OriginalTransactionType' => 'Auth Only',
             'OriginalApprovalCode' => $tran_meta['approval_code'],
             'OriginalReferenceNumber' =>  $tran_meta['transaction_id'],
             'OriginalAmount' => $tran_meta['authorize_amount'],
             'EmailCustomer'  => $this->email_customer,
             'EmailMerchant'  => $this->email_merchant,
             'ServiceFeeLabel' => $tran_meta['fee_label'],
             'ServiceFeeAmount' => wc_clean($service_amount),
             'ServiceFeeProvided' => 1,
             'Amount' => wc_clean($amount + $service_amount)
          );
       } else {
          if (trim($tran_meta['transaction_type']) != 'auth_capture') return false;
          $data = array(
             'CustomerNumber' => wc_clean($order->get_customer_id()),
             'Secret' => $gateway->secret_key,
             'MerchantId' => $gateway->merchant_id,
             'TerminalId' => $gateway->terminal_id,
             'InvoiceNumber' => $order_number,
             'Seed' => $vSeed,
             'Mode' => '2',
             'Version' => '2.0',
             'TransactionType' => 'Return',
             'Tax' => $extra['tax'],
             'Shipping' => $extra['shipping'],
             'OriginalTransactionType' => 'Sale',
             'OriginalApprovalCode' => $tran_meta['approval_code'],
             'OriginalReferenceNumber' =>  $tran_meta['transaction_id'],
             'OriginalAmount' => $tran_meta['authorize_amount'],
             'EmailCustomer'  => $this->email_customer,
             'EmailMerchant'  => $this->email_merchant,
             'ServiceFeeLabel' => $tran_meta['fee_label'],
             'ServiceFeeAmount' => wc_clean($service_amount),
             'ServiceFeeProvided' => 1,
             'Amount' => wc_clean($amount + $service_amount)
          );
       }
       return json_encode($data);
    }

    /**
     * post_transaction function
     * 
     * @param string $payload
     * @param array  $headers
     * 
     * @return string|WP_Error
     */
    public function post_transaction($payload)
    {
       $data = json_decode($payload);
       $vPostData = "";
       foreach ($data as $key => $val) {
          if ($vPostData != "") $vPostData .= "&";
          $vPostData .= $key . "=" . $val;
       }

       $processURI = '';
       if ($this->mode == 'true') {
          $processURI = $this->test_url_process;
       } else {
          $processURI = $this->live_url_process;
       }

       if ($this->log_request == 'true') {
          $data = json_decode(json_encode($data), true);
          $this->logData("Request", array(
             'merchant_id'  => $this->merchant_id,
             'terminal_id'  => $this->terminal_id,
             'invoiceNumber' => $data['InvoiceNumber'],
             'customerNumber' => $data['CustomerNumber'],
             'amount' => $data['Amount'],
             'transactionType' => $data['TransactionType'],
             'ServiceFeeLabel' => $data['ServiceFeeLabel'],
             'ServiceFeeAmount' => $data['ServiceFeeAmount'],
             'OriginalTransactionType' => $data['OriginalTransactionType'],
             'data' => $data,
          ));
       }

       $response = wp_remote_post($processURI, array('method' => 'POST', 'body' => $vPostData));

       if (is_wp_error($response)) {
          $error_message = $response->get_error_message();
          return new WP_Error('chargeanywhere_error', __('There was a problem connecting to the payment gateway.' . $error_message, 'chargeanywhere-woocommerce'));
       }

       if ($response['body'] !== FALSE) {
          $responsedata = explode("&", $response['body']);
          $response = array();
          foreach ($responsedata as $key => $val) {
             $txt = explode("=", $val);
             $response[$txt[0]] = $txt[1];
          }

          if ($this->log_request == 'true') {
             $this->logData("Response",  array(
                'merchant_id'  => $this->merchant_id,
                'terminal_id'  => $this->terminal_id,
                'invoicenumber' => $data['InvoiceNumber'],
                'responsecode' => $response['ResponseCode'],
                'approvalcode' => $response['ApprovalCode'],
                'AuthorizedAmount' => $response['AuthorizedAmount'],
                'ReferenceNumber' => $response['ReferenceNumber'],
                'transactionType' => $data['TransactionType'],
             ));
          }

          return $response;
       } else {
             /*Handle Failure Case*/;
          return new WP_Error('chargeanywhere_error', __('There was a problem connecting to the payment gateway.', 'chargeanywhere-woocommerce'));
       }
    }

    /**
     * Receipt Page
     **/
    function receipt_page($order)
    {
       echo '<p>' . __('Thank you for your order, please click the button below to pay with Charge Anywhere.', 'chargeanywhere-woocommerce') . '</p>';
       echo $this->generate_authorize_form($order);
    }

    /**
     * Process the payment and return the result
     **/
    function process_payment($order_id)
    {
       $order = new WC_Order($order_id);
      //post data
     
       $card_raw       = isset($_POST['chargeanywherecardnumber']) ? sanitize_text_field(wp_unslash($_POST['chargeanywherecardnumber'])) : '';
       $card_number    = str_replace(' ', '', $card_raw);
       $exp_raw        = isset($_POST['chargeanywherecardexpiry']) ? sanitize_text_field(wp_unslash($_POST['chargeanywherecardexpiry'])) : '';
       $exp_date_array = explode('/', $exp_raw);
       $exp_month      = trim($exp_date_array[0]);
       $exp_year       = trim($exp_date_array[1]);
       //$exp_date       = $exp_month . substr($exp_year, -2);
       $cvc            = isset($_POST['chargeanywherecardcvc']) ? sanitize_text_field(wp_unslash($_POST['chargeanywherecardcvc'])) : '';

       $chargeanywhere_option            = isset($_POST['chargeanywhereoption']) ? sanitize_text_field(wp_unslash($_POST['chargeanywhereoption'])) : '';
       WC()->session->set('chargeanywhere_option', $chargeanywhere_option);
       WC()->session->set('chargeanywhere-card-number', $card_number);
       WC()->session->set('chargeanywhere-exp-month', $exp_month);
       WC()->session->set('chargeanywhere-exp-year', $exp_year);
       WC()->session->set('chargeanywhere-card-cvc', $cvc);


       $account_number      = isset($_POST['accountnumber']) ? sanitize_text_field(wp_unslash($_POST['accountnumber'])) : '';
       $account_number      = str_replace(' ', '', $account_number);
       $routing_number      = isset($_POST['routingnumber']) ? sanitize_text_field(wp_unslash($_POST['routingnumber'])) : '';
       $routing_number      = str_replace(' ', '', $routing_number);
       $acc_type            = isset($_POST['accounttype']) ? sanitize_text_field(wp_unslash($_POST['accounttype'])) : '';

       WC()->session->set('chargeanywhere-account-number', $account_number);
       WC()->session->set('chargeanywhere-routing-number', $routing_number);
       WC()->session->set('chargeanywhere-acctype', $acc_type);

       return array(
          'result'    => 'success',
          'redirect'   => $order->get_checkout_payment_url(true)
       );
    }

    /**
     * Check for valid chargeanywhere server callback to validate the transaction response.
     **/
    function check_authorize_response()
    {
      //print_r($_POST);
       global $woocommerce;
       $temp_order            = new WC_Order();
         $invoicenumber = isset($_POST['InvoiceNumber']) ? sanitize_text_field(wp_unslash($_POST['InvoiceNumber'])) : '';
          $order  = new WC_Order($invoicenumber);
       if (count($_POST)) {
          $redirect_url = '';
          $this->msg['class']     = 'error';
          $this->msg['message']   = $this->failed_message;

          //Response Data
          
          $responsecode = isset($_POST['ResponseCode']) ? sanitize_text_field(wp_unslash($_POST['ResponseCode'])) : '';
          $amount = isset($_POST['Amount']) ? sanitize_text_field(wp_unslash($_POST['Amount'])) : '';
          $approvalcode = isset($_POST['ApprovalCode']) ? sanitize_text_field(wp_unslash($_POST['ApprovalCode'])) : '';
          $authorize_amount = isset($_POST['AuthorizedAmount']) ? sanitize_text_field(wp_unslash($_POST['AuthorizedAmount'])) : '';

          $fee = isset($_POST['fee']) ? sanitize_text_field(wp_unslash($_POST['fee'])) : 0;
          $conv_fee = isset($_POST['fee']) ? sanitize_text_field(wp_unslash($_POST['conv_fee'])) : 0;

          $card_number = isset($_POST['CardNumber']) ? sanitize_text_field(wp_unslash($_POST['CardNumber'])) : '';
          $reference_number = isset($_POST['ReferenceNumber']) ? sanitize_text_field(wp_unslash($_POST['ReferenceNumber'])) : '';
          $expiry_month = isset($_POST['ExpMonth']) ? sanitize_text_field(wp_unslash($_POST['ExpMonth'])) : '';
          $chargeanywhere_option = isset($_POST['chargeanywhere_option']) ? sanitize_text_field(wp_unslash($_POST['chargeanywhere_option'])) : '';
          $chargeanywhere_fee_details = isset($_POST['chargeanywhere_fee_details']) ? sanitize_text_field(wp_unslash($_POST['chargeanywhere_fee_details'])) : '';
          if ($this->log_request == 'true') {
             $this->logData("Response", array(
                'merchant_id'  => $this->merchant_id,
                'terminal_id'  => $this->terminal_id,
                'invoicenumber' => $invoicenumber,
                'responsecode' => $responsecode,
                'approvalcode' => $approvalcode,
                'amount' => $amount,
                'chargeanywhere_option' => $chargeanywhere_option,
                'transaction_type' => $this->transaction_mode,
             ));
          }
          if ($responsecode != '') {
               try {
                  $transauthorised  = false;
                  if ($order->get_status() != 'completed') {
                     if ($responsecode == '000') {
                        $transauthorised        = true;
                        $this->msg['message']   = $this->success_message;
                        $this->msg['class']     = 'success';

                        if ($order->get_status() == 'processing') {
                        } else {
                           $fee_label = $_POST['ServiceFeeLabel'];
                           $order->payment_complete($invoicenumber);
                           // $order->update_status('processing');
                           $order->add_order_note('Charge Anywhere payment successful<br/>Ref Number/Transaction ID: ' . $reference_number);
                           $order->add_order_note($this->msg['message']);
                           $trans_id = $reference_number;
                           $tran_meta = array(
                              'transaction_id' => $trans_id,
                              'approval_code' => $approvalcode,
                              'authorize_amount' => $authorize_amount,
                              'cc_last4' => substr($card_number, -4),
                              'cc_expiry' => $expiry_month,
                              'fee_label' => $fee_label,
                              'transaction_type' => $this->transaction_mode,
                              'chargeanywhere_option' => $chargeanywhere_option,
                              'chargeanywhere_fee_details' => $chargeanywhere_fee_details,
                              'fee' => $fee,
                              'conv_fee' => $conv_fee
                           );
                           add_post_meta($invoicenumber, '_chargeanywhere_transaction', $tran_meta);
                           if ($this->transaction_mode == 'authorize') {
                              $order->update_status('Pending Payment');
                           }
                           $woocommerce->cart->empty_cart();
                        }
                     } else {
                        $this->msg['class'] = 'error';
                        $this->msg['message'] = $this->failed_message;
                        $order->add_order_note($this->msg['message']);
                        $order->update_status('failed');
                        wp_safe_redirect(wc_get_checkout_url() . '?ca-error=' . $_POST['ResponseText']);
                        //extra code can be added here such as sending an email to customer on transaction fail
                     }
                  }
                  if ($transauthorised == false) {
                     $order->update_status('failed');
                     $order->add_order_note($this->msg['message']);
                  }
               } catch (Exception $e) {
                  $msg = "Error";
               }
            } else {
               $this->msg['class'] = 'error';
               $this->msg['message'] = $this->failed_message;
               $order->add_order_note($this->msg['message']);
               $order->update_status('failed');
               wp_safe_redirect(wc_get_checkout_url() . '?ca-error=' . $_POST['ResponseText']);
               exit;
               // $order->add_order_note('SHA hash did not matched for this transaction. Please contact support <a href="http://www.chargeanywhere.com">contact plugin support</a> for help.');
            }
          $redirect_url = $order->get_checkout_order_received_url();
          $this->web_redirect($redirect_url);

          exit;
       } else {
          $this->msg['class'] = 'error';
          $this->msg['message'] = $this->failed_message;
          $order->add_order_note($this->msg['message']);
          $order->update_status('failed');
          if(isset($_POST['ResponseText'])){
            wp_safe_redirect(wc_get_checkout_url() . '?ca-error=' . $_POST['ResponseText']);
         }
       }
    }


    public function web_redirect($url)
    {
       echo "<html><head><script language=\"javascript\">
              <!--
              window.location=\"{$url}\";
              //-->
              </script>
              </head><body><noscript><meta http-equiv=\"refresh\" content=\"0;url={$url}\"></noscript></body></html>";
    }
    /**
     * Generate chargeanywhere button link
     **/
    public function generate_authorize_form($order_id)
    {
       global $woocommerce;
       $options = get_option('woocommerce_chargeanywhere_settings');
       $order         = new WC_Order($order_id);
       $timeStamp     = time();
       $order_total   = $order->get_total();
       $signatureKey  = ($this->secret_key != '') ? $this->secret_key : '';


       //$relay_url = $this->get_return_url( $order );
       $relay_url = get_site_url() . '/wc-api/' . get_class($this);
       $authorize_args = array();
       $authorize_args_array = array();

       $authorize_args['current_time'] = date("Y-m-d H:i:s:u");
       $chargeanywhere_option = WC()->session->get('chargeanywhere_option');

       $fee_store_details['credit_service_fee'] = WC()->session->get('credit_service_fee');
       $fee_store_details['credit_convenience_service_fee'] = WC()->session->get('credit_convenience_service_fee');
       $fee_store_details['ach_service_fee'] = WC()->session->get('ach_service_fee');
       $fee_store_details['ach_convenience_service_fee'] = WC()->session->get('ach_convenience_service_fee');

       $fee_label = "Service Fee";
       if ($chargeanywhere_option == 'credit') {
          $fee_label = $options['credit_service_fee'];
          $authorize_args['TransactionType'] = 'Sale';
          if ($this->transaction_mode == 'authorize')
             $authorize_args['TransactionType'] = 'Auth Only';

          $authorize_args['PaymentMethod'] = 1;

          $card_number = WC()->session->get('chargeanywhere-card-number');
          $exp_month = WC()->session->get('chargeanywhere-exp-month');
          $exp_year = WC()->session->get('chargeanywhere-exp-year');

          $authorize_args['fee'] = $fee_store_details['credit_service_fee'];
          $authorize_args['conv_fee'] = $fee_store_details['credit_convenience_service_fee'];
          //$authorize_args['payment_type'] = 'credit';
          $authorize_args['CardNumber'] = $card_number;
          $authorize_args['ExpMonth'] = $exp_month;
          $authorize_args['ExpYear'] = $exp_year;
          $authorize_args['CardVerificationValue'] =  WC()->session->get('chargeanywhere-card-cvc');
       } else {
          $authorize_args['PaymentMethod'] = 2;
          $fee_label = $options['ach_service_fee'];

          $accnumber = WC()->session->get('chargeanywhere-account-number');
          $routingnumber =  WC()->session->get('chargeanywhere-routing-number');
          $acctype =  WC()->session->get('chargeanywhere-acctype');
          //$authorize_args['payment_type'] = 'ach';
          $authorize_args['TransactionType'] = 'Sale';
          $authorize_args['AccountType'] = $acctype;
          $authorize_args['RoutingNumber'] = $routingnumber;
          $authorize_args['AccountNumber'] = $accnumber;

          $authorize_args['FirstName'] = $order->get_billing_first_name();
          $authorize_args['LastName'] = $order->get_billing_last_name();

          $authorize_args['fee'] = $fee_store_details['ach_service_fee'];
          $authorize_args['conv_fee'] = $fee_store_details['ach_convenience_service_fee'];
       }

       $vSeed = $this->seed_gen();
       $fee_details = $order->get_fees();
       $tax_total = 0;
       foreach ($order->get_items('fee') as $fee) {
          // The fee name
          $fee_name = $fee->get_name();
          // The fee total amount
          $fee_total = $fee->get_total();
          $tax_total += $fee_total;
       }
       $order_total = $order_total - $tax_total;

       $order_tax = 0;
       foreach ($order->get_items('tax') as $tax) {
          $order_tax += $tax->get_tax_total();
       }
       // $order_total = $order_total - $order_tax;

       $order_ship = 0;
       foreach ($order->get_items('shipping') as $ship) {
          $order_ship += $ship->get_total();
       }
       // $order_total = $order_total - $order_ship;

       $authorize_args['Tax'] = $order_tax;
       $authorize_args['Shipping'] = $order_ship;

       $tax_total_exclude_convienience = 0;
       if ($chargeanywhere_option == 'credit') {
          $tax_total_exclude_convienience =  $tax_total - $fee_store_details['credit_convenience_service_fee'];
          $order_total += $fee_store_details['credit_convenience_service_fee'];
       } else {
          $tax_total_exclude_convienience =  $tax_total - $fee_store_details['ach_convenience_service_fee'];
          $order_total += $fee_store_details['ach_convenience_service_fee'];
       }

       $vTextTohash = $this->merchant_id . ':' . $this->terminal_id . ':' . $vSeed . ':' . $order_total;
       $signature = hash_hmac('sha512', $vTextTohash, hex2bin($this->secret_key));

       $authorize_args['ServiceFeeLabel'] = $fee_label;
       $authorize_args['ServiceFeeAmount'] = $tax_total_exclude_convienience; //$tax_total;
       $authorize_args['ServiceFeeProvided'] = 1;

       $authorize_args['MerchantId'] = $this->merchant_id;
       $authorize_args['TerminalId'] = $this->terminal_id;
       $authorize_args['Seed'] = $vSeed;
       $authorize_args['Amount'] = $order_total;
       $authorize_args['chargeanywhere_fee_details'] = json_encode($fee_store_details);
       $authorize_args['chargeanywhere_option'] = $chargeanywhere_option;
       $authorize_args['Signature'] = $signature;
       $authorize_args['MerchantReceiptURL'] = $relay_url;

       $authorize_args['Mode'] = '1';
       $authorize_args['Version'] = '2.0';

       $authorize_args['InvoiceNumber'] = $order_id;
       $authorize_args['CustomerNumber'] = $order->get_customer_id();
       $authorize_args['BIFirstName'] = $order->get_billing_first_name();
       $authorize_args['BILastName'] = $order->get_billing_last_name();
       $authorize_args['BIAddress'] = $order->get_billing_address_1() . ' ' . $order->get_billing_address_2();
       $authorize_args['BICity'] = $order->get_billing_city();
       $authorize_args['BIState'] = $order->get_billing_state();
       $authorize_args['BIZipCode'] = $order->get_billing_postcode();
       $authorize_args['BICountry'] = $order->get_billing_country();
       $authorize_args['BIEmail'] = $order->get_billing_email();
       $authorize_args['EmailCustomer'] = $this->email_customer;
       $authorize_args['EmailMerchant'] = $this->email_merchant;

       foreach ($authorize_args as $key => $value) {
          $authorize_args_array[] = "<input type='hidden' name='$key' value='$value'/>";
       }

       if ($this->mode == 'true') {
          $processURI = $this->test_url_transaction;
       } else {
          $processURI = $this->live_url_transaction;
       }

       if ($this->log_request == 'true') {
          $this->logData("Request", array(
             'merchant_id'  => $this->merchant_id,
             'terminal_id'  => $this->terminal_id,
             'invoiceNumber' => $authorize_args['InvoiceNumber'],
             'customerNumber' => $authorize_args['CustomerNumber'],
             'amount' => $authorize_args['Amount'],
             'transactionType' => $authorize_args['TransactionType'],
             'chargeanywhere_option' => $chargeanywhere_option,
          ));
       }

       $html_form    = '<form action="' . $processURI . '" method="post" id="chargeanywhere_payment_form">'
          . implode('', $authorize_args_array)
          . '<input type="submit" class="button" id="submit_chargeanywhere_payment_form" value="' . __('Pay via Charge Anywhere', 'chargeanywhere-woocommerce') . '" /> <a class="button cancel" href="' . $order->get_cancel_order_url() . '">' . __('Cancel order &amp; restore cart', 'chargeanywhere-woocommerce') . '</a>'
          . '<script type="text/javascript">
                jQuery(function(){
                   jQuery("body").block({
                         message: "<img src=\"' . plugin_dir_url(__FILE__) . 'images/loader.gif\" alt=\"Redirecting…\" style=\"float:left; margin-right: 10px; width:48px;\" />' . __($this->redirect_message, 'chargeanywhere-woocommerce') . '",
                         overlayCSS: { background: "#ccc", opacity: 0.6, "z-index": "99999999999999999999999999999999" },
                   css: { padding:20, textAlign:"center", color:"#555", border:"3px solid #aaa", backgroundColor:"#fff",
                         cursor:"wait", lineHeight:"32px", "z-index":"999999999999999999999999999999999"}
                   });
                jQuery("#submit_chargeanywhere_payment_form").click();
             });
             </script>
             </form>';
       return $html_form;
    }

    public function seed_gen()
    {
       return rand(1, 9) . time();
    }

    /**
     * To log the request & response data
     */
    public function logData($type, $data)
    {
       $date = new DateTime('now', new DateTimeZone(wp_timezone_string()));
       $data = array_merge(array('current_time' => $date->format("Y-m-d H:i:s-u")), $data);
       $fname = date("Y-m-d");
       // $fileName = ABSPATH."wp-content/plugins/chargeanywhere-woocommerce/logs/".$fname.".log";
       $fileName = dirname(__FILE__) . "/logs/" . $fname . ".log";
       $file = fopen($fileName, "a") or die("Unable to open file!");
       fwrite($file, $type . ": " . json_encode($data) . "\n");
       fclose($file);
    }
 }

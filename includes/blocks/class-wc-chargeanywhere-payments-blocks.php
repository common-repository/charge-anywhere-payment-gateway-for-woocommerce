<?php
use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

/**
 * Charge Anywhere Payments Blocks integration
 *
 * @since 1.0.3
 */
final class WC_Gateway_ChargeAnywhere_Blocks_Support extends AbstractPaymentMethodType {

	/**
	 * The gateway instance.
	 *
	 * @var WC_Gateway_ChargeAnywhere
	 */
	private $gateway;

	/**
	 * Payment method name/id/slug.
	 *
	 * @var string
	 */
	protected $name = 'chargeanywhere';

	/**
	 * Initializes the payment method type.
	 */
	public function initialize() {
		$this->settings = get_option( 'woocommerce_chargeanywhere_settings', [] );
		$this->gateway  = new WC_Gateway_ChargeAnywhere();
	}

	/**
	 * Returns if this payment method should be active. If false, the scripts will not be enqueued.
	 *
	 * @return boolean
	 */
	public function is_active() {
		return $this->gateway->is_available();
	}

	/**
	 * Returns an array of scripts/handles to be registered for this payment method.
	 *
	 * @return array
	 */
	public function get_payment_method_script_handles() {
		$script_path       = '/assets/js/frontend/blocks.js';
		$script_asset_path = WC_ChargeAnywhere_Payments::plugin_abspath() . 'assets/js/frontend/blocks.asset.php';
		$script_asset      = file_exists( $script_asset_path )
			? require( $script_asset_path )
			: array(
				'dependencies' => array(),
				'version'      => '1.2.0'
			);
		$script_url        = WC_ChargeAnywhere_Payments::plugin_url() . $script_path;

		wp_register_script(
			'wc-chargeanywhere-payments-blocks',
			$script_url,
			$script_asset[ 'dependencies' ],
			$script_asset[ 'version' ],
			true
		);

		if ( function_exists( 'wp_set_script_translations' ) ) {
			wp_set_script_translations( 'wc-chargeanywhere-payments-blocks', 'chargeanywhere-woocommerce', WC_ChargeAnywhere_Payments::plugin_abspath() . 'languages/' );
		}

		return [ 'wc-chargeanywhere-payments-blocks' ];
	}

	/**
	 * Returns an array of key=>value pairs of data made available to the payment methods script.
	 *
	 * @return array
	 */
	public function get_payment_method_data() {
		return [
			'title'       => $this->get_setting( 'title' ),
			'description' => $this->get_setting( 'description' ),
			'merchant_id' => $this->get_setting( 'merchant_id' ),
			'terminal_id' => $this->get_setting( 'terminal_id' ),
			'secret_key' => $this->get_setting( 'secret_key' )?true:false,
			'accept_credit' => $this->get_setting( 'accept_credit' ),
			'accept_ach' => $this->get_setting( 'accept_ach' ),
			/*'success_message' => $this->get_setting( 'success_message' ),
			'failed_message' => $this->get_setting( 'failed_message' ),
			'redirect_message' => $this->get_setting( 'redirect_message' ),
			'transaction_mode' => $this->get_setting( 'transaction_mode' ),
			'email_customer' => $this->get_setting( 'email_customer' ),
			'email_merchant' => $this->get_setting( 'email_merchant' ),
			'log_request' => $this->get_setting( 'log_request' ),
			'apply_credit_service' => $this->get_setting( 'apply_credit_service' ),
			'credit_service_fee' => $this->get_setting( 'credit_service_fee' ),
			'credit_service_fee_value' => $this->get_setting( 'credit_service_fee_value' ),
			'apply_credit_tax_amount_service_fee' => $this->get_setting( 'apply_credit_tax_amount_service_fee' ),
			'credit_refund_service_fee' => $this->get_setting( 'credit_refund_service_fee' ),
			'apply_credit_convenience_service' => $this->get_setting( 'apply_credit_convenience_service' ),
			'credit_convenience_service_fee' => $this->get_setting( 'credit_convenience_service_fee' ),
			'credit_convenience_service_fee_value' => $this->get_setting( 'credit_convenience_service_fee_value' ),
			'credit_refund_convenience_fee' => $this->get_setting( 'credit_refund_convenience_fee' ),
			'apply_ach_service' => $this->get_setting( 'apply_ach_service' ),
			'ach_service_fee' => $this->get_setting( 'ach_service_fee' ),
			'ach_service_fee_value' => $this->get_setting( 'ach_service_fee_value' ),
			'apply_ach_tax_amount_service_fee' => $this->get_setting( 'apply_ach_tax_amount_service_fee' ),
			'ach_refund_service_fee' => $this->get_setting( 'ach_refund_service_fee' ),
			'apply_ach_convenience_service' => $this->get_setting( 'apply_ach_convenience_service' ),
			'ach_convenience_service_fee' => $this->get_setting( 'ach_convenience_service_fee' ),
			'ach_convenience_service_fee_value' => $this->get_setting( 'ach_convenience_service_fee_value' ),
			'ach_refund_convenience_fee' => $this->get_setting( 'ach_refund_convenience_fee' ),*/
			'mode' => $this->get_setting( 'working_mode' ),
			'supports'    => array_filter( $this->gateway->supports, [ $this->gateway, 'supports' ] )
		];
	}
}

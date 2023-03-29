<?php
/**
 * WooCommerce AvaTax
 *
 * This source file is subject to the GNU General Public License v3.0
 * that is bundled with this package in the file license.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.gnu.org/licenses/gpl-3.0.html
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@skyverge.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade WooCommerce AvaTax to newer
 * versions in the future. If you wish to customize WooCommerce AvaTax for your
 * needs please refer to http://docs.woocommerce.com/document/woocommerce-avatax/
 *
 * @author    SkyVerge
 * @copyright Copyright (c) 2016-2022, SkyVerge, Inc.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

use SkyVerge\WooCommerce\PluginFramework\v5_10_14 as Framework;

defined( 'ABSPATH' ) or exit;

/**
 * Handle the AJAX-specific functionality.
 *
 * @since 1.0.0
 */
class WC_AvaTax_AJAX {


	/** @var bool $reload_order_notes_after_calculating_taxes whether order notes should be reloaded after calculating order taxes in admin */
	protected $reload_order_notes_after_calculating_taxes = false;


	/**
	 * Construct the class.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		$this->add_hooks();
	}


	/**
	 * Adds handler actions and filters.
	 *
	 * @since 1.13.0
	 */
	protected function add_hooks() {

		// validate the Origin Address settings fields
		add_action( 'wp_ajax_wc_avatax_validate_origin_address', [ $this, 'validate_origin_address' ] );

		// validate the customer address at checkout
		add_action( 'wp_ajax_wc_avatax_validate_customer_address',        [ $this, 'validate_customer_address' ] );
		add_action( 'wp_ajax_nopriv_wc_avatax_validate_customer_address', [ $this, 'validate_customer_address' ] );

		// Cross Border ajax callback methods
		add_action( 'wp_ajax_wc_avatax_resync_error_products',    [ $this, 'resync_products_with_errors' ] );
		add_action( 'wp_ajax_wc_avatax_toggle_cross_border_sync', [ $this, 'toggle_cross_border_sync' ] );

		// display and save the product variation tax code field
		add_action( 'woocommerce_product_after_variable_attributes', [ $this, 'display_product_variation_code_fields' ], 15, 3 );
		add_action( 'woocommerce_save_product_variation',            [ $this, 'save_product_variation_code_fields' ] );

		// save the product tax code quick edit field
		add_action( 'woocommerce_product_quick_edit_save', [ $this, 'save_product_tax_code_quick_edit' ] );

		// save the tax code field when a new product category is created
		add_action( 'created_product_cat', [ $this, 'save_category_code_fields' ], 10, 2 );

		// add estimated AvaTax calculations to orders when "Calculate Taxes" is run from the admin
		add_action( 'woocommerce_saved_order_items', [ $this, 'estimate_order_tax' ] );

		// check for Landed Cost warnings after calculating taxes in admin and possibly reload order notes
		add_action( 'wc_avatax_after_order_tax_calculated', [ $this, 'check_for_landed_cost_warnings' ], 10, 2 );
		add_action( 'woocommerce_order_item_add_action_buttons', [ $this, 'maybe_trigger_order_notes_reload'] );
		add_action( 'wp_ajax_wc_avatax_get_order_notes', [ $this, 'get_order_notes' ] );

	}


	/**
	 * Checks for landed cost warnings in the tax calculation response and sets a flag for later use.
	 *
	 * @internal
	 *
	 * @since 1.16.0
	 *
	 * @param int $order_id order ID (unused)
	 * @param WC_AvaTax_API_Tax_Response $response tax calculation response object
	 * @return void
	 */
	public function check_for_landed_cost_warnings( $order_id, $response ) {

		foreach ( $response->get_messages() as $message ) {

			if ( 'MissingHSCodeWarning' === $message->summary ) {

				$this->reload_order_notes_after_calculating_taxes = true;
				break;
			}
		}
	}


	/**
	 * Triggers admin JS to reload order notes.
	 *
	 * @internal
	 *
	 * @since 1.16.0
	 *
	 * @return void
	 */
	public function maybe_trigger_order_notes_reload() {

		if ( $this->reload_order_notes_after_calculating_taxes ) {
			echo '<script>window.wc_avatax_admin.reload_order_notes("' . wp_create_nonce( 'wc_avatax_get_order_notes' ) .  '")</script>';
		}
	}


	/**
	 * Gets the order notes & sends an AJAX success response with teh rendered notes HTML.
	 *
	 * @internal
	 *
	 * @since 1.16.0
	 *
	 * @see \WC_AJAX::save_order_items() - based on this method
	 *
	 * @return void
	 */
	public function get_order_notes() {

		check_ajax_referer( 'wc_avatax_get_order_notes', 'security' );

		if ( ! isset( $_REQUEST['order_id'] ) || ! current_user_can( 'edit_shop_orders' )) {
			wp_die( -1 );
		}

		wp_send_json_success( [ 'notes_html' => $this->get_order_notes_html( absint( $_REQUEST['order_id'] ) ) ] );
	}


	/**
	 * Gets the order notes HTML for the given order ID.
	 *
	 * @since 1.16.0
	 *
	 * @param int $order_id
	 * @return false|string
	 */
	protected function get_order_notes_html( int $order_id ) {

		ob_start();
		$notes = wc_get_order_notes( [ 'order_id' => $order_id ] );

		if ( defined('WC_ABSPATH') ) {
			include WC_ABSPATH . '/includes/admin/meta-boxes/views/html-order-notes.php';
		}

		return ob_get_clean();
	}


	/**
	 * Validate the Origin Address settings fields.
	 *
	 * @since 1.0.0
	 */
	public function validate_origin_address() {

		// No nonce? No go
		check_ajax_referer( 'wc_avatax_validate_origin_address', 'nonce' );

		try {

			/**
			 * Fire before validating the origin address.
			 *
			 * @since 1.0.0
			 */
			do_action( 'wc_avatax_before_origin_address_validated' );

			$response = wc_avatax()->get_api()->validate_address( array(
				'address_1' => Framework\SV_WC_Helper::get_requested_value( 'line1' ),
				'city'      => Framework\SV_WC_Helper::get_requested_value( 'city' ),
				'state'     => Framework\SV_WC_Helper::get_requested_value( 'region' ),
				'country'   => Framework\SV_WC_Helper::get_requested_value( 'country' ),
				'postcode'  => Framework\SV_WC_Helper::get_requested_value( 'postcode' ),
			) );

			// Documented in `WC_AvaTax_Settings::save_address_field`
			$address = (array) apply_filters( 'wc_avatax_save_address_field', $response->get_normalized_address() );

			// Save the validated address
			update_option( 'wc_avatax_origin_address', $address );

			/**
			 * Fire after validating the origin address.
			 *
			 * @since 1.0.0
			 * @param array $address The validated and normalized address.
			 */
			do_action( 'wc_avatax_after_origin_address_validated', $address );

			wp_send_json( array(
				'code'    => 200,
				'address' => $address,
			) );

		} catch ( Framework\SV_WC_API_Exception $e ) {

			if ( wc_avatax()->logging_enabled() ) {
				wc_avatax()->log( $e->getMessage() );
			}

			wp_send_json( array(
				'code'  => (int) $e->getCode(),
				'error' => esc_html( $e->getMessage() ),
			) );
		}
	}


	/**
	 * Validate the customer address at checkout.
	 *
	 * @since 1.0.0
	 */
	public function validate_customer_address() {

		// No nonce? No go
		if ( ! wp_verify_nonce( Framework\SV_WC_Helper::get_requested_value( 'nonce' ), 'wc_avatax_validate_customer_address' ) ) {
			wp_die();
		}

		try {

			/**
			 * Fire before validating a customer address.
			 *
			 * @since 1.0.0
			 * @param array $address The validated and normalized address.
			 */
			do_action( 'wc_avatax_before_customer_address_validated' );

			$response = wc_avatax()->get_api()->validate_address( array(
				'address_1' => Framework\SV_WC_Helper::get_posted_value( 'address_1' ),
				'address_2' => Framework\SV_WC_Helper::get_posted_value( 'address_2' ),
				'city'      => Framework\SV_WC_Helper::get_posted_value( 'city' ),
				'state'     => Framework\SV_WC_Helper::get_posted_value( 'state' ),
				'country'   => Framework\SV_WC_Helper::get_posted_value( 'country' ),
				'postcode'  => Framework\SV_WC_Helper::get_posted_value( 'postcode' ),
			) );

			$address = $response->get_normalized_address();

			// Set the shipping address values to the normalized address
			WC()->customer->set_shipping_address( $address['address_1'] );
			WC()->customer->set_shipping_address_2( $address['address_2'] );
			WC()->customer->set_shipping_city( $address['city'] );
			WC()->customer->set_shipping_state( $address['state'] );
			WC()->customer->set_shipping_country( $address['country'] );
			WC()->customer->set_shipping_postcode( $address['postcode'] );

			$type = Framework\SV_WC_Helper::get_posted_value( 'type' );

			// If validating a billing address, set those values too
			if ( 'billing' === $type ) {

				WC()->customer->set_billing_address( $address['address_1'] );
				WC()->customer->set_billing_address_2( $address['address_2'] );
				WC()->customer->set_billing_city( $address['city'] );
				WC()->customer->set_billing_state( $address['state'] );
				WC()->customer->set_billing_country( $address['country'] );
				WC()->customer->set_billing_postcode( $address['postcode'] );
			}

			// Prepend the address type (billing or shipping) to the keys
			foreach ( $address as $key => $value ) {
				$address[ $type . '_' . $key ] = $value;
				unset( $address[ $key ] );
			}

			/**
			 * Fire after validating a customer address.
			 *
			 * @since 1.0.0
			 * @param array $address The validated and normalized address.
			 */
			do_action( 'wc_avatax_after_customer_address_validated', $address );

			WC()->session->set( 'wc_avatax_address_validated', true );

			// Off you go
			wp_send_json( array(
				'code'    => 200,
				'address' => $address,
			) );

		} catch ( Framework\SV_WC_API_Exception $e ) {

			if ( wc_avatax()->logging_enabled() ) {
				wc_avatax()->log( $e->getMessage() );
			}

			wp_send_json( array(
				'code'  => (int) $e->getCode(),
				'error' => esc_html( $e->getMessage() ),
			) );
		}
	}


	/**
	 * Display the product variation tax code field.
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 *
	 * @param int $loop the variation loop key
	 * @param array $variation_data the variation data
	 * @param \WC_Product_Variation $variation the variation object
	 */
	public function display_product_variation_code_fields( $loop, $variation_data, $variation ) {

		$default  = get_post_meta( $variation->post_parent, '_wc_avatax_code', true );
		$tax_code = get_post_meta( $variation->ID, '_wc_avatax_code', true );

		include( wc_avatax()->get_plugin_path() . '/src/admin/views/html-field-product-variation-tax-code.php' );
	}


	/**
	 * Save a product variation tax code.
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 *
	 * @param int $variation_id the varation ID
	 */
	public function save_product_variation_code_fields( $variation_id ) {

		$tax_code = '';

		if ( isset( $_POST['variable_post_id'] ) && ( false !== ( $i = array_search( $variation_id, $_POST['variable_post_id'] ) ) ) ) {

			if ( isset( $_POST['variable_wc_avatax_code'] ) ) {
				$tax_code = $_POST['variable_wc_avatax_code'][ $i ];
			}
		}

		if ( '' !== $tax_code ) {
			update_post_meta( $variation_id, '_wc_avatax_code', wc_clean( $tax_code ) );
		} else {
			delete_post_meta( $variation_id, '_wc_avatax_code' );
		}
	}


	/**
	 * Save the product tax code quick edit field.
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 *
	 * @param \WC_Product $product the product object
	 */
	public function save_product_tax_code_quick_edit( $product ) {

		if ( isset( $_REQUEST['_wc_avatax_code'] ) ) {
			update_post_meta( $product->get_id(), '_wc_avatax_code', sanitize_text_field( $_REQUEST['_wc_avatax_code'] ) );
		}
	}


	/**
	 * Saves the tax code fields when a new product category is created.
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 *
	 * @param int $term_id new term ID
	 * @param int $tt_id new term taxonomy ID
	 */
	public function save_category_code_fields( $term_id, $tt_id ) {

		$tax_code = sanitize_text_field( Framework\SV_WC_Helper::get_posted_value( 'wc_avatax_category_tax_code' ) );

		update_term_meta( $term_id, 'wc_avatax_tax_code', $tax_code );
	}


	/**
	 * Add estimated AvaTax calculations to orders when "Calculate Taxes" is run from the admin.
	 *
	 * @since 1.0.0
	 *
	 * @param int $order_id the order ID
	 * @throws WC_Data_Exception
	 */
	public function estimate_order_tax( $order_id ) {

		// If not otherwise calculating taxes, bail
		if ( ! doing_action( 'wp_ajax_woocommerce_calc_line_taxes' ) ) {
			return;
		}

		// If tax calculation is turned off, bail
		if ( ! wc_avatax()->get_tax_handler()->is_available() ) {
			return;
		}

		$order = wc_get_order( $order_id );

		// If order couldn't be fetched, bail
		if ( ! $order ) {
			return;
		}

		// Estimate taxes for the address provided in the request, if available. When an order is not yet saved, address
		// fields won't be set, making tax calculation not possible. The following will ensure we're using the address
		// given in the AJAX request (which is the address entered on the customer billing/shipping address form in admin).
		if ( isset( $_POST['country'], $_POST['state'] ) ) {

			/** @see \WC_AJAX::calc_line_taxes() */
			$country_code = wc_strtoupper( wc_clean( wp_unslash( $_POST['country'] ) ) );
			$state        = wc_strtoupper( wc_clean( wp_unslash( $_POST['state'] ) ) );
			$tax_based_on = get_option( 'woocommerce_tax_based_on ', $tax_based_on);
			// temporarily set address fields on order object so that the tax request class can access them
			if ( 'shipping' === $tax_based_on ) {
				$order->set_shipping_country( $country_code );
				$order->set_shipping_state( $state );
				$order->set_shipping_city( wc_strtoupper( wc_clean( wp_unslash( $_POST['city'] ?? '' ) ) ) );
				$order->set_shipping_postcode( wc_strtoupper( wc_clean( wp_unslash( $_POST['postcode'] ?? '' ) ) ) );
			}
			elseif ( 'billing' === $tax_based_on) {
				$order->set_billing_country( $country_code );
				$order->set_billing_state( $state );
				$order->set_billing_city( wc_strtoupper( wc_clean( wp_unslash( $_POST['city'] ?? '' ) ) ) );
				$order->set_billing_postcode( wc_strtoupper( wc_clean( wp_unslash( $_POST['postcode'] ?? '' ) ) ) );
			}

		}  elseif ( $order->has_shipping_address() ) {
			$country_code = $order->get_shipping_country( 'edit' );
			$state        = $order->get_shipping_state( 'edit' );
		} else {
			$country_code = $order->get_billing_country( 'edit' );
			$state        = $order->get_billing_state( 'edit' );
		}

		// check that the destination is taxable
		if ( ! wc_avatax()->get_tax_handler()->is_location_taxable( $country_code, $state ) ) {
			return;
		}

		wc_avatax()->get_order_handler()->estimate_tax( $order );
	}


	/**
	 * Process order refunds and get accurate tax refund rates from the AvaTax API.
	 *
	 * Totals passed around this method are mostly negative floats that will _subtract_ from an order's total.
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 * @deprecated 1.15.0
	 *
	 * @param int $order_id The order ID.
	 * @param int $refund_id The refund ID.
	 */
	public function process_refund( $order_id, $refund_id ) {

		wc_deprecated_function( __METHOD__, '1.15.0', 'WC_AvaTax_Order_Handler::process_refund' );
	}


	/**
	 * Attempts to sync again all the products that had sync errors in a previous run.
	 *
	 * @internal
	 *
	 * @since 1.13.0
	 */
	public function resync_products_with_errors() {

		check_ajax_referer( 'wc_avatax_resync_error_products', 'nonce' );

		wc_avatax()->get_landed_cost_sync_handler()->resync_products_with_errors();
	}


	/**
	 * Toggles the Cross-Border item classification sync state.
	 *
	 * @internal
	 *
	 * @since 1.13.0
	 */
	public function toggle_cross_border_sync() {

		check_ajax_referer( 'wc_avatax_toggle_cross_border_sync', 'nonce' );

		$sync_handler = wc_avatax()->get_landed_cost_sync_handler();

		if ( $can_toggle = ( $sync_handler->is_syncing_active() || wc_avatax()->get_landed_cost_handler()->can_connect_to_hs_api() ) ) {
			$sync_handler->toggle_syncing();
		}

		wp_send_json_success( [
			'toggled' => wc_bool_to_string( $can_toggle ),
		] );
	}


}

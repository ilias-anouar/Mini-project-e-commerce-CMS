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
 * Set up the AvaTax front-end.
 *
 * @since 1.0.0
 */
class WC_AvaTax_Frontend {


	/**
	 * Construct the class.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		// load front end assets
		add_action( 'wp_enqueue_scripts', [ $this, 'load_scripts' ] );

		if ( $this->address_validation_enabled() ) {

			// Add an address validation button below each address form at checkout.
			add_action( 'woocommerce_after_checkout_billing_form', array( $this, 'add_validate_address_button' ) );
			add_action( 'woocommerce_after_checkout_shipping_form', array( $this, 'add_shipping_validate_address_button' ) );

			// Validate the customer address at checkout when JavaScript is disabled.
			add_action( 'woocommerce_checkout_process', array( $this, 'validate_address' ) );
		}

		if ( wc_avatax()->get_tax_handler()->is_available() ) {

			// Display a "pending calculation" message on the cart page
			if ( 'itemized' === get_option( 'woocommerce_tax_total_display' ) ) {
				add_action( 'woocommerce_cart_totals_before_order_total', array( $this, 'display_cart_calculation_message' ) );
			} else {
				add_filter( 'woocommerce_cart_totals_taxes_total_html', array( $this, 'adjust_single_tax_total_html' ) );
			}

			// Add the VAT field if enabled
			if ( apply_filters( 'wc_avatax_enable_vat', ( 'yes' === get_option( 'wc_avatax_enable_vat' ) ) ) ) {
				add_filter( 'woocommerce_billing_fields', array( $this, 'add_checkout_vat_field' ) );
			}
		}
	}


	/**
	 * Loads front-end assets.
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 */
	public function load_scripts() {

		if ( ! is_checkout() ) {
			return;
		}

		// load styles
		wp_enqueue_style( 'wc-avatax-frontend', wc_avatax()->get_plugin_url() . '/assets/css/frontend/wc-avatax-frontend.min.css', [], WC_AvaTax::VERSION );

		// the frontend JS is only needed for address validation
		if ( $this->address_validation_enabled() ) {

			// load scripts
			wp_enqueue_script( 'wc-avatax-frontend', wc_avatax()->get_plugin_url() . '/assets/js/frontend/wc-avatax-frontend.min.js', array( 'jquery' ), WC_AvaTax::VERSION, true );

			wp_localize_script( 'wc-avatax-frontend', 'wc_avatax_frontend', [

				'ajax_url'                     => admin_url( 'admin-ajax.php' ),
				'address_validation_nonce'     => wp_create_nonce( 'wc_avatax_validate_customer_address' ),
				'address_validation_countries' => $this->get_address_validation_countries(),

				'i18n' => [
					'address_validated' => __( 'Address validated.', 'woocommerce-avatax' ),
				],

			] );

		}
	}


	/**
	 * Add an address validation button at checkout.
	 *
	 * @since 1.0.0
	 */
	public function add_validate_address_button() {

		echo $this->get_validate_address_button();
	}


	/**
	 * Add an address validation button at checkout.
	 *
	 * @since 1.1.1
	 */
	public function add_shipping_validate_address_button() {

		echo $this->get_validate_address_button( 'shipping' );
	}


	/**
	 * Gets the address validation button markup.
	 *
	 * @since 1.1.1
	 *
	 * @param string $type button type, either shipping or billing
	 * @return string button HTML
	 */
	protected function get_validate_address_button( $type = 'billing' ) {

		/**
		 * Filters the address validation button label.
		 *
		 * @since 1.0.0
		 *
		 * @param string $label the address validation button label
		 */
		$label = (string) apply_filters( 'wc_avatax_validate_address_button_label', __( 'Validate Address', 'woocommerce-avatax' ) );

		return '<button class="wc_avatax_validate_address button" data-address-type="' . esc_attr( $type ) . '">' . esc_html( $label ) . '</button>';
	}


	/**
	 * Validate the customer address at checkout when JavaScript is disabled.
	 *
	 * @since 1.0.0
	 */
	public function validate_address() {

		// If the address validation button was not pressed, bail
		if ( ! Framework\SV_WC_Helper::get_posted_value( 'woocommerce_checkout_update_totals' ) ) {
			return;
		}

		// Skip shipping if not needed
		if ( Framework\SV_WC_Helper::get_posted_value( 'ship_to_different_address' ) ) {
			$type = 'shipping';
		} else {
			$type = 'billing';
		}

		$response = wc_avatax()->get_api()->validate_address( array(
			'address_1' => Framework\SV_WC_Helper::get_posted_value( $type . '_address_1' ),
			'address_2' => Framework\SV_WC_Helper::get_posted_value( $type . '_address_2' ),
			'city'      => Framework\SV_WC_Helper::get_posted_value( $type . '_city' ),
			'state'     => Framework\SV_WC_Helper::get_posted_value( $type . '_state' ),
			'country'   => Framework\SV_WC_Helper::get_posted_value( $type . '_country' ),
			'postcode'  => Framework\SV_WC_Helper::get_posted_value( $type . '_postcode' ),
		) );

		$address = $response->get_normalized_address();

		// Set the shipping address values to the normalized address
		$_POST[ $type . '_address_1' ] = $address['address_1'];
		$_POST[ $type . '_address_2' ] = $address['address_2'];
		$_POST[ $type . '_city' ]      = $address['city'];
		$_POST[ $type . '_state' ]     = $address['state'];
		$_POST[ $type . '_country' ]   = $address['country'];
		$_POST[ $type . '_postcode' ]  = $address['postcode'];

		wc_add_notice( __( 'Address validated.', 'woocommerce-avatax' ), 'success' );
	}


	/**
	 * Display a "pending calculation" message on the cart page when displaying a single tax total.
	 *
	 * @since 1.2.1
	 * @param string $html the tax total HTML
	 * @return string
	 */
	public function adjust_single_tax_total_html( $html ) {

		$cart  = WC()->cart;
		$taxes = $cart->get_cart_contents_taxes();

		if ( empty( $taxes ) && wc_avatax()->get_tax_handler()->override_wc_rates() ) {

			if ( is_cart() ) {
				$html = esc_html( $this->get_cart_calculation_message() );
			} elseif ( is_checkout() && $this->address_validation_required() && ! WC()->session->get( 'wc_avatax_address_validated', false ) ) {
				$html = esc_html__( 'Taxes will be calculated after you validate your address', 'woocommerce-avatax' );
			}
		}

		return $html;
	}


	/**
	 * Display a "pending calculation" message on the cart page when taxes are itemized.
	 *
	 * @since 1.2.1
	 */
	public function display_cart_calculation_message() {

		$taxes = Framework\SV_WC_Plugin_Compatibility::is_wc_version_gte( '3.2' ) ? WC()->cart->get_cart_contents_taxes() : WC()->cart->taxes;

		if ( ! is_cart() || ! wc_avatax()->get_tax_handler()->override_wc_rates() || ! empty( $taxes ) ) {
			return;
		}

		/** This filter is documented in woocommerce-avatax/woocommerce-avatax.php */
		$title = apply_filters( 'wc_avatax_tax_label', WC()->countries->tax_or_vat() );

		echo '<tr class="tax-total">';
			echo '<th>' . esc_html( $title ) . '</th>';
			echo '<td data-title="' . esc_attr( $title ) . '">' . esc_html( $this->get_cart_calculation_message() ) . '</td>';
		echo '</tr>';
	}


	/**
	 * Get the "pending calculation" message for the cart page.
	 *
	 * @since 1.2.1
	 * @return string
	 */
	protected function get_cart_calculation_message() {

		/**
		 * Filter the cart pending tax calculation message.
		 *
		 * @since 1.2.1
		 * @param string $message
		 */
		return apply_filters( 'wc_avatax_cart_message', __( 'Taxes will be calculated at checkout', 'woocommerce-avatax' ) );
	}


	/**
	 * Add the VAT field to the checkout billing fields.
	 *
	 * @since 1.0.0
	 * @param array $fields The existing checkout fields.
	 * @return array $fields The checkout fields.
	 */
	public function add_checkout_vat_field( $fields ) {

		$origin_address = get_option( 'wc_avatax_origin_address', [] );
		$vat_countries  = Framework\SV_WC_Plugin_Compatibility::is_wc_version_lt( '4.0.0' ) ? WC()->countries->get_european_union_countries( 'eu_vat' ) : WC()->countries->get_vat_countries();

		// Only output the VAT if applicable to the shop's origin address
		if ( ! in_array( $origin_address['country'], $vat_countries, true ) ) {
			return $fields;
		}

		/**
		 * Filter the VAT ID checkout field label.
		 *
		 * @since 1.0.0
		 * @param string $label The VAT ID checkout field label.
		 */
		$label = apply_filters( 'wc_avatax_vat_id_field_label', __( 'VAT ID', 'woocommerce-avatax' ) );

		$fields['billing_wc_avatax_vat_id'] = [
			'label' => $label,
			'class' => [ 'form-row-wide' ],
		];

		return $fields;
	}


	/**
	 * Determines if address validation is required.
	 *
	 * @since 1.6.4
	 *
	 * @return bool
	 */
	public function address_validation_required() {

		/**
		 * Filters whether address validation is required.
		 *
		 * @since 1.6.4
		 *
		 * @param bool $required whether address validation is required
		 */
		return $this->address_validation_available() && (bool) apply_filters( 'wc_avatax_address_validation_required', ( 'yes' === get_option( 'wc_avatax_require_address_validation' ) ) );
	}


	/**
	 * Determine if address validation is available at checkout.
	 *
	 * @since 1.0.0
	 *
	 * @return bool $enabled Whether address validation is available at checkout.
	 */
	public function address_validation_available() {

		$countries = $this->get_address_validation_countries();

		/**
		 * Filters whether address validation is available.
		 *
		 * @since 1.0.0
		 *
		 * @param bool $required whether address validation is available
		 */
		return $this->address_validation_enabled() && (bool) apply_filters( 'wc_avatax_address_validation_available', in_array( WC()->customer->get_shipping_country(), $countries ) );
	}


	/**
	 * Determine if address validation is enabled.
	 *
	 * @since 1.0.0
	 * @return bool $enabled Whether address validation is enabled.
	 */
	public function address_validation_enabled() {

		/**
		 * Filter whether address validation is enabled.
		 *
		 * @since 1.0.0
		 * @param bool $enabled Whether address validation is enabled.
		 */
		return (bool) apply_filters( 'wc_avatax_enable_address_validation', ( 'yes' === get_option( 'wc_avatax_enable_address_validation' ) ) );
	}


	/**
	 * Determine if address validation is available at checkout.
	 *
	 * @since 1.0.0
	 * @return bool $enabled Whether address validation is available at checkout.
	 */
	public function get_address_validation_countries() {

		$countries = get_option( 'wc_avatax_address_validation_countries' );

		/**
		 * Filter the countries that support address validation.
		 *
		 * @since 1.0.0
		 * @param array $countries The countries that support address validation.
		 */
		return (array) apply_filters( 'wc_avatax_address_validation_countries', $countries );
	}
}

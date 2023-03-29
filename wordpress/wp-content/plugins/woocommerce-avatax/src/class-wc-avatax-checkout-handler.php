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
 * Handle the checkout-specific functionality.
 *
 * @since 1.0.0
 */
class WC_AvaTax_Checkout_Handler {


	/**
	 * Construct the class.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		if ( wc_avatax()->get_tax_handler()->is_available() ) {

			// calculate the tax based on the cart at checkout
			add_action( 'woocommerce_after_calculate_totals', [ $this, 'calculate_taxes' ], 998 );

			add_filter( 'woocommerce_get_price_excluding_tax', array( $this, 'adjust_cart_item_prices' ), 10, 3 );
			add_filter( 'woocommerce_get_price_including_tax', array( $this, 'adjust_cart_item_prices' ), 10, 3 );

			// set proper tax rate labels for display at checkout
			add_action( 'woocommerce_cart_tax_totals', [ $this, 'set_rate_labels' ], 998, 2 );

			// Set the customer VAT ID at checkout
			add_action( 'woocommerce_checkout_update_order_review', array( $this, 'set_customer_vat' ) );

			// add any messages returned by AvaTax to the checkout display
			add_action( 'woocommerce_review_order_before_submit', array( $this, 'add_checkout_messages' ) );

			// check whether the address has been validated
			add_action( 'woocommerce_after_checkout_validation', array( $this, 'check_address_validation' ), 10, 2 );
		}
	}


	/**
	 * Generate a temporary tax transaction and set the cart tax totals.
	 *
	 * This is only run at cart or checkout and before order payment.
	 * If there is any sort of API error, this will fall back to the estimated tax rates if available (US-only).
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 *
	 * @param \WC_Cart $cart WooCommerce cart instance
	 */
	public function calculate_taxes( $cart ) {

		// only calculate a full tax transaction when needed
		if ( ! $this->needs_calculation() || ! $this->ready_for_calculation() ) {
			return;
		}

		try {

			/**
			 * Fire before calculating the cart tax at checkout.
			 *
			 * @since 1.0.0
			 */
			do_action( 'wc_avatax_before_checkout_tax_calculated' );

			$response = wc_avatax()->get_api()->calculate_cart_tax( $cart );

			// we only get this far if there were no API errors
			$this->reset_cart_taxes( $cart );

			$this->set_product_taxes( $cart, $response->get_lines(), $response->is_tax_included() );

			$this->set_fee_taxes( $cart, $response->get_fee_lines() );

			$this->set_shipping_taxes( $cart, $response->get_shipping_lines() );

			/** @see WC_AvaTax_Landed_Cost_Handler::add_checkout_order_notes */
			/** @see WC_AvaTax_Order_Handler::set_new_order_item_meta_data */
			/** @see WC_AvaTax_Order_Handler::get_avatax_response_cart_line */
			$cart->avatax_response = $response;

			// TODO: consider removing the following cart property assignments and just use the avatax_response property from above {IT 2021-12-21}

			// store whether or not the rates include at least one landed cost
			// (has to run before setting avatax_rates, because it is used by it) <-- TODO: this does not seem to be true anymore {IT 2021-12-21}
			$cart->avatax_has_landed_costs = $response->has_landed_costs();

			// store the overall rates for later use
			$cart->avatax_rates = $response->get_rates();

			// store the response messages for later display
			$cart->avatax_messages = $response->get_messages();

			/**
			 * Fire after calculating the cart tax at checkout.
			 *
			 * @since 1.0.0
			 */
			do_action( 'wc_avatax_after_checkout_tax_calculated' );

		} catch ( Framework\SV_WC_API_Exception $e ) {

			$error = sprintf( __( 'Checkout Error: %s', 'woocommerce-avatax' ), $e->getMessage() );

			if ( wc_avatax()->logging_enabled() ) {
				wc_avatax()->log( $error );
			}
		}
	}


	/**
	 * Resets the cart taxes to before any AvaTax rates were added.
	 *
	 * @since 1.5.0
	 *
	 * @param \WC_Cart $cart
	 */
	protected function reset_cart_taxes( \WC_Cart $cart ) {

		$recurring_carts = isset( $cart->recurring_carts ) ? $cart->recurring_carts : null;

		remove_action( 'woocommerce_after_calculate_totals', [ $this, 'calculate_taxes' ], 998 );
		add_filter( 'woocommerce_find_rates', [ $this, 'remove_estimated_tax_rates' ] );

		$cart->calculate_totals();

		remove_filter( 'woocommerce_find_rates', [ $this, 'remove_estimated_tax_rates' ] );
		add_action( 'woocommerce_after_calculate_totals', [ $this, 'calculate_taxes' ], 998 );

		if ( $recurring_carts ) {
			$cart->recurring_carts = $recurring_carts;
		}
	}


	/**
	 * Removes any estimated AvaTax rates from the matched tax rates.
	 *
	 * This is used when resetting the cart taxes to remove any previously
	 * estimated rates so nothing is duplicated.
	 *
	 * @internal
	 *
	 * @since 1.5.0
	 *
	 * @see \WC_AvaTax_Checkout_Handler::reset_cart_taxes()
	 * @see \WC_AvaTax_Tax_Handler::set_matched_tax_rates()
	 *
	 * @param array $matched_tax_rates matched tax rates
	 * @return array $matched_tax_rates matched tax rates, minus any from AvaTax
	 */
	public function remove_estimated_tax_rates( $matched_tax_rates ) {

		foreach ( $matched_tax_rates as $code => $rate ) {

			if ( Framework\SV_WC_Helper::str_starts_with( $code, WC_AvaTax_Tax_Handler::RATE_PREFIX ) ) {
				unset( $matched_tax_rates[ $code ] );
			}
		}

		return $matched_tax_rates;
	}


	/**
	 * Sets the product tax data for a cart.
	 *
	 * @since 1.5.0
	 *
	 * @param \WC_Cart $cart cart object
	 * @param array $lines lines from the AvaTax API
	 * @param bool $tax_included whether the product prices included tax at calculation
	 */
	protected function set_product_taxes( WC_Cart $cart, $lines, $tax_included = false ) {

		$subtotal   = $cart->get_subtotal();
		$cart_taxes = $cart->get_cart_contents_taxes();

		foreach ( $lines as $line ) {

			// ensure there are no negative values set
			$line['tax'] = max( (float) $line['tax'], 0.00 );

			if ( isset( $cart->cart_contents[ $line['id'] ] ) ) {

				$line_tax_data = array();

				foreach ( $line['rates'] as $index => $rate ) {

					$total = $rate->get_total();

					$line_tax_data[ $index ] = $total;

					if ( isset( $cart_taxes[ $index ] ) ) {
						$cart_taxes[ $index ] += $total;
					} else {
						$cart_taxes[ $index ] = $total;
					}
				}

				// set the tax rate data
				$cart->cart_contents[ $line['id'] ]['line_tax_data']['total']    += $line_tax_data;
				$cart->cart_contents[ $line['id'] ]['line_tax_data']['subtotal'] += $line_tax_data;

				// set the line's tax totals
				$cart->cart_contents[ $line['id'] ]['line_tax']          += $line['tax'];
				$cart->cart_contents[ $line['id'] ]['line_subtotal_tax'] += $line['tax'];

				// if tax was included for this request, subtract from the line totals
				if ( $tax_included ) {

					$cart->cart_contents[ $line['id'] ]['line_total']    -= $line['tax'];
					$cart->cart_contents[ $line['id'] ]['line_subtotal'] -= $line['tax'];

					$subtotal -= $line['tax'];

					$cart->cart_contents_total -= $line['tax'];

				} else {

					$cart->total += $line['tax'];
				}

				$cart->set_subtotal( $subtotal );
				$cart->set_subtotal_tax( $cart->get_subtotal_tax() + (float) $line['tax'] );
				$cart->set_cart_contents_tax( $cart->get_cart_contents_tax() + (float) $line['tax'] );
				$cart->set_total_tax( $cart->get_total_tax() + (float) $line['tax'] );
			}
		}

		$cart->set_cart_contents_taxes( $cart_taxes );
	}


	/**
	 * Sets the fee tax data for a cart.
	 *
	 * @since 1.5.0
	 *
	 * @param \WC_Cart $cart cart object
	 * @param array $lines fee lines from the AvaTax API
	 */
	protected function set_fee_taxes( WC_Cart $cart, array $lines = [] ) {

		$cart_fees    = $cart->get_fees();
		$cart_fee_ids = wp_list_pluck( $cart_fees, 'id' );

		foreach ( $lines as $line ) {

			$line_id = str_replace( 'fee_', '', $line['id'] );

			// check if the line exists in the cart by getting the fee key
			$fee_key = $this->match_cart_fee_key( $line_id, $cart_fee_ids );

			// if the line doesn't exist in the cart, it's probably a fee
			// generated by Avalara that needs to be added to the cart
			if ( ! $fee_key ) {

				/** @see \WC_AvaTax_API_Tax_Request::include_retail_delivery_fee() so this appears as a label in cart instead of a slug */
				if ( 'retail-delivery-fee' === $line_id ) {
					$line_id = __( 'Retail Delivery Fee', 'woocommerce-avatax');
				} else {
					$line_id = "avatax-{$line_id}";
					$cart->add_fee( $line_id, $line['amount'], true );
				}


				$line_id      = sanitize_title( $line_id );
				$cart_fees    = $cart->get_fees();
				$cart_fee_ids = wp_list_pluck( $cart_fees, 'id' );

				$cart->fee_total += $line['amount'];
				$cart->total     += $line['amount'];

				if ( Framework\SV_WC_Plugin_Compatibility::is_wc_version_gte( '3.2' ) ) {
					if ( 'retail-delivery-fee' !== $line_id ) {
						$cart_fees[ $line_id ]->total = $line['amount'];
					}
					$cart->set_fee_tax( $cart->get_fee_tax() + $line['tax'] );
				}

				// re-search the cart fees with the new line_id (fee generated by Avalara)
				$fee_key = $this->match_cart_fee_key( $line_id, $cart_fee_ids );
			}

			if ( 'retail-delivery-fee' !== $line_id ) {
			// remove any prefixing from fee names
			$cart_fees[ $fee_key ]->name = str_replace( 'avatax-', '', $cart_fees[ $fee_key ]->name );
			}

			if ( isset( $cart_fees[ $fee_key ]->tax ) ) {
				$cart_fees[ $fee_key ]->tax += $line['tax'];
			} else {
				if ( 'retail-delivery-fee' !== $line_id ) {
					$cart_fees[ $fee_key ]->tax = $line['tax'];
				}
			}

			$tax_data  = $cart_fees[ $fee_key ]->tax_data ?? [];
			$fee_taxes = $cart->get_fee_taxes();

			// isset checks below are to account for a niche possibility where taxes with the same code may exist
			foreach ( $line['rates'] as $rate ) {

				/** @var \WC_AvaTax_API_Tax_Rate $rate */
				$code  = $rate->get_code();
				$total = $rate->get_total();

				$tax_data[ $code ] = $total;

				if ( isset( $fee_taxes[ $code ] ) ) {
					$fee_taxes[ $code ] += $total;
				} else {
					$fee_taxes[ $code ] = $total;
				}
			}

			if ( 'retail-delivery-fee' !== $line_id ) {
				$cart_fees[ $fee_key ]->tax_data = $tax_data;
			}

			if ( Framework\SV_WC_Plugin_Compatibility::is_wc_version_gte( '3.2' ) ) {
				$cart->set_fee_taxes( $fee_taxes );
				$cart->fees_api()->set_fees( $cart_fees );
				$cart->set_fee_tax( $cart->get_fee_tax() + (float) $line['tax'] );
				$cart->set_total_tax( $cart->get_total_tax() + (float) $line['tax'] );
			} else {
				$cart->fees  = $cart_fees;
				$cart->taxes = $fee_taxes;
				$cart->tax_total += $line['tax'];
			}

			$cart->total += $line['tax'];
		}
	}


	/**
	 * Try to match the given line ID to a cart fee key.
	 *
	 * @since 1.16.0
	 *
	 * @param string $line_id the line ID from AvaTax
	 * @param array $cart_fee_ids an array of cart fee id => cart fee key pairs
	 * @return false|int|string the found id, or false if none found
	 */
	protected function match_cart_fee_key( string $line_id, array $cart_fee_ids = [] ) {

		foreach ( $cart_fee_ids as $cart_fee_key => $cart_fee_id ) {

			// search for either hashed or non hashed ID
			if ( $cart_fee_id === $line_id || hash_equals( wp_hash( $cart_fee_id ), $line_id ) ) {

				return $cart_fee_key;
			}
		}

		return false;
	}


	/**
	 * Sets the shipping tax data for a cart.
	 *
	 * @since 1.5.0
	 *
	 * @param \WC_Cart $cart cart object
	 * @param array $lines shipping lines from the AvaTax API
	 */
	protected function set_shipping_taxes( WC_Cart $cart, $lines ) {

		$cart_shipping_taxes = $cart->get_shipping_taxes();
		$packages            = WC()->shipping()->get_packages();
		$shipping_tax_total  = 0;

		foreach ( $lines as $line ) {

			$shipping_taxes = [];

			foreach ( $line['rates'] as $index => $rate ) {

				$total = $rate->get_total();

				$shipping_taxes[ $index ] = $total;

				// isset checks below account for a case in which multiple lines include tax rates with the same code
				if ( isset( $cart_shipping_taxes[ $index ] ) ) {
					$cart_shipping_taxes[ $index ] += $total;
				} else {
					$cart_shipping_taxes[ $index ] = $total;
				}
			}

			$method_id = $line['sku'];

			foreach ( $packages as $index => $package ) {

				if ( ! empty( $package['rates'][ $method_id ] ) )  {
					$packages[ $index ]['rates'][ $method_id ]->taxes += $shipping_taxes;
				}
			}

			// ensure there are no negative values set
			$shipping_tax_total += max( (float) $line['tax'], 0.00 );
		}

		$cart->set_shipping_taxes( $cart_shipping_taxes );
		$cart->set_total_tax( $cart->get_total_tax() + $shipping_tax_total );

		WC()->shipping()->packages = $packages;

		$cart->total += array_sum( $cart_shipping_taxes );

		$cart->shipping_tax_total = array_sum( $cart_shipping_taxes );
	}


	/**
	 * Determines if the cart needs taxes calculated.
	 *
	 * This means a full calculation, not an estimation.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	private function needs_calculation() {

		if ( is_cart() ) {
			$needs_calculation = $this->calculate_on_cart();
		} elseif ( isset( $_POST['woocommerce_checkout_update_totals'] ) || is_checkout() ) {
			$needs_calculation = true;
		}  else {
			$needs_calculation = false;
		}

		/**
		 * Filters whether the cart needs new taxes calculated.
		 *
		 * @since 1.0.0
		 *
		 * @param bool $needs_calculation Whether the cart needs new taxes calculated.
		 */
		return (bool) apply_filters( 'wc_avatax_cart_needs_calculation', $needs_calculation );
	}


	/**
	 * Determines if a full calculation should be performed on the cart page.
	 *
	 * @since 1.5.0
	 *
	 * @return bool
	 */
	protected function calculate_on_cart() {

		// no need for costly calculations if estimation is already being used
		$estimation_supported = wc_avatax()->get_tax_handler()->country_can_estimate_rates( WC()->customer->get_shipping_country() );

		switch ( get_option( 'wc_avatax_calculate_on_cart', 'yes' ) ) {

			case 'force' :
				$calculate_on_cart = true;
			break;

			case 'yes' :
				$calculate_on_cart = ! $estimation_supported && 'yes' === get_option( 'wc_avatax_calculate_on_cart_international_customers', 'no' );
			break;

			case 'no' :
			default :
				$calculate_on_cart = false;
			break;
		}

		/**
		 * Filters whether a full calculation should be performed on the cart page.
		 *
		 * @since 1.5.0
		 *
		 * @param bool $calculate_on_cart whether to perform a full tax calculation on cart page
		 */
		return (bool) apply_filters( 'wc_avatax_calculate_on_cart', $calculate_on_cart );
	}


	/**
	 * Determine if the front-end is ready for tax calculation.
	 *
	 * The main factors here are whether we're on the checkout page and if the customer
	 * has supplied enough address information.
	 *
	 * @since 1.0.0
	 * @return bool $ready_for_calculation Whether the front-end is ready for tax calculation.
	 */
	private function ready_for_calculation() {

		// first check that the basic minimum address info is available
		$ready_for_calculation = WC()->customer->get_shipping_country() && $this->is_taxable();

		// check the locale for required region & postcode fields
		$locale_fields = WC()->countries->get_address_fields( WC()->customer->get_shipping_country(), 'shipping_' );

		if ( $locale_fields['shipping_state']['required'] && ! WC()->customer->get_shipping_state() ) {
			$ready_for_calculation = false;
		}

		if ( $locale_fields['shipping_postcode']['required'] && ! WC()->customer->get_shipping_postcode() ) {
			$ready_for_calculation = false;
		}

		// if address validation is available and required, ensure it's been done before calculating taxes
		if ( wc_avatax()->get_frontend_instance() && wc_avatax()->get_frontend_instance()->address_validation_required() && ! WC()->session->get( 'wc_avatax_address_validated', false ) ) {
			$ready_for_calculation = false;
		}

		/**
		 * Filter whether the front-end is ready for tax calculation.
		 *
		 * @since 1.0.0
		 * @param $ready_for_calculation Whether the front-end is ready for tax calculation.
		 */
		return (bool) apply_filters( 'wc_avatax_checkout_ready_for_calculation', $ready_for_calculation );
	}


	/**
	 * Determine if tax calculation is supported by the customer's taxable address.
	 *
	 * @since 1.1.0
	 * @return bool
	 */
	private function is_taxable() {

		$taxable_address = WC()->customer->get_taxable_address();

		$country_code = $taxable_address[0];
		$state        = $taxable_address[1];

		/**
		 * Filter whether the destination location is taxable by AvaTax.
		 *
		 * @since 1.1.0
		 * @param bool $is_taxable
		 */
		return (bool) apply_filters( 'wc_avatax_checkout_is_taxable', wc_avatax()->get_tax_handler()->is_location_taxable( $country_code, $state ) );
	}


	/**
	 * Adjusts the product line subtotals at checkout to account for various tax
	 * display settings.
	 *
	 * @internal
	 *
	 * @since 1.5.0
	 *
	 * @param float $price line item price
	 * @param int $qty line item quantity
	 * @param \WC_Product $product product object
	 * @return string $price The product subtotal.
	 */
	public function adjust_cart_item_prices( $price, $qty, $product ) {

		if ( ! is_cart() && ! is_checkout() ) {
			return $price;
		}

		$display_setting = wc_prices_include_tax() ? 'excl' : 'incl';

		// if there is no need to adjust prices based on the tax display setting, bail
		if ( $display_setting !== get_option( 'woocommerce_tax_display_cart' ) ) {
			return $price;
		}

		foreach ( WC()->cart->cart_contents as $item_key => $item ) {

			if ( $product->get_id() !== $item['product_id'] && $product->get_id() !== $item['variation_id'] ) {
				continue;
			}

			$line_tax = ( (float) $item['line_tax'] / $item['quantity'] ) * $qty;
			$price    = (float) $product->get_price() * $qty;

			if ( wc_prices_include_tax() ) {
				$price -= $line_tax;
			} else {
				$price += $line_tax;
			}
		}

		return $price;
	}


	/**
	 * Sets proper tax rate labels for display at checkout.
	 *
	 * @internal
	 *
	 * @since 1.5.0
	 *
	 * @param array $tax_totals calculated tax totals
	 * @param \WC_Cart $cart cart object
	 * @return array $tax_totals
	 */
	public function set_rate_labels( $tax_totals, $cart ) {

		if ( ! empty( $cart->avatax_rates ) ) {

			/** @var WC_AvaTax_API_Tax_Rate[][] $avatax_rates */
			$avatax_rates = $cart->avatax_rates;

			foreach ( array_keys( $tax_totals ) as $code ) {

				foreach( $avatax_rates as $avatax_line_rates ) {

					if ( isset( $avatax_line_rates[ $code ] ) ) {

						$tax_totals[ $code ]->label = $avatax_line_rates[ $code ]->get_label();
						break;
					}
				}
			}
		}

		return $tax_totals;
	}


	/**
	 * Set the customer's VAT ID at checkout.
	 *
	 * @since 1.0.0
	 * @param string $post_data The posted data.
	 */
	public function set_customer_vat( $post_data ) {

		if ( ! empty( $post_data ) ) {

			$post_data = explode( '&', $post_data );

			foreach ( $post_data as $pair ) {
				$pair                  = explode( '=', $pair );
				$post_data[ $pair[0] ] = urldecode( $pair[1] );
			}
		}

		if ( isset( $post_data['billing_wc_avatax_vat_id'] ) ) {
			WC()->session->set( 'wc_avatax_vat_id', $post_data['billing_wc_avatax_vat_id'] );
		}
	}


	/**
	 * Adds any messages returned by AvaTax to the checkout display.
	 *
	 * @internal
	 *
	 * @since 1.6.4
	 */
	public function add_checkout_messages() {

		if ( ! empty( WC()->cart->avatax_messages ) && is_array( WC()->cart->avatax_messages ) ) {

			$has_missing_hs_code_warnings = false;

			foreach ( WC()->cart->avatax_messages as $message ) {

				if ( ! empty( $message->summary ) && ! empty( $message->refersTo ) && 'LandedCost' === $message->refersTo ) {
					echo '<p class="wc-avatax-message">' . esc_html( $message->summary ) . '</p>';
				} elseif ( 'MissingHSCodeWarning' === $message->summary ) {
					$has_missing_hs_code_warnings = true;
				}
			}

			if ( $has_missing_hs_code_warnings ) {

				$country_code = WC()->customer->get_shipping_country();
				$country = ( new WC_Countries() )->get_countries()[$country_code] ?? $country_code;

				/* translators: Placeholders: %s - country name */
				echo '<p class="wc-avatax-message">' . sprintf( esc_html__( "We cannot calculate import duties for %s for some of the products in your cart. By placing the order, you'll need to settle any applicable customs duties and fees with the shipment carrier. You can also contact us to complete your order.", 'woocommerce-avatax' ), $country ) . '</p>';
			}
		}
	}


	/**
	 * Checks whether the address has been validated.
	 *
	 * Will add a checkout error if validation is required but hasn't been
	 * performed by the customer.
	 *
	 * @internal
	 *
	 * @since 1.6.4
	 *
	 * @param array $data checkout data
	 * @param \WP_Error $errors checkout errors, or null for WC 2.6
	 */
	public function check_address_validation( $data, $errors = null ) {

		if ( wc_avatax()->get_frontend_instance() && wc_avatax()->get_frontend_instance()->address_validation_required() && ! WC()->session->get( 'wc_avatax_address_validated', false ) ) {

			$message = __( 'Please validate your address to continue', 'woocommerce-avatax' );

			if ( $errors instanceof WP_Error ) {
				$errors->add( 'validation', $message );
			}
		}
	}


}

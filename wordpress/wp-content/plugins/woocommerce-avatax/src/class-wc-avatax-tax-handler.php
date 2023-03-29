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
 * The base tax handler class.
 *
 * @since 1.5.0
 */
class WC_AvaTax_Tax_Handler {


	/** @var string prefix assigned to any tax rate originating from the AvaTax API */
	const RATE_PREFIX = 'AVATAX';


	/**
	 * Constructs the class.
	 *
	 * @since 1.5.0
	 */
	public function __construct() {

		if ( $this->is_available() ) {

			// ensure tax-inclusive prices are displayed properly
			add_filter( 'woocommerce_adjust_non_base_location_prices', '__return_false' );

			if ( $this->override_wc_rates() ) {
				add_filter( 'woocommerce_matched_tax_rates', '__return_empty_array' );
			}

			// set matched tax rates throughout the store to those estimated by AvaTax
			add_filter( 'woocommerce_matched_tax_rates', array( $this, 'set_matched_tax_rates' ), 15, 5 );
		}

		// Add the AvaTax rate code
		add_filter( 'woocommerce_rate_code', array( $this, 'set_tax_rate_code' ), 10, 2 );

		// Set the AvaTax rate code label
		add_filter( 'woocommerce_cart_tax_totals', array( $this, 'set_tax_rate_labels' ), 10, 2 );

		// Set the order item tax rate ID
		add_filter( 'woocommerce_order_item_get_rate_id', array( $this, 'set_order_item_tax_rate_id' ), 10, 2 );
	}


	/**
	 * Replaces WooCommerce core tax rates with those estimated by AvaTax.
	 *
	 * This is primarily used when calculating tax in the shop or for the cart.
	 * It is also used as a first line of defense at checkout, so if the real
	 * final tax estimation fails, the customer will still be charged an
	 * approximate tax.
	 *
	 * @internal
	 *
	 * @see \WC_Tax::find_rates()
	 * @see \WC_Tax::get_matched_tax_rates()
	 *
	 * @since 1.5.0
	 *
	 * @param array $matched_tax_rates matched tax rates from WooCommerce
	 * @param string $country customer country code
	 * @param string $state customer state
	 * @param string $postcode customer postcode
	 * @param string $city customer city
	 *
	 * @return array AvaTax tax rates
	 */
	public function set_matched_tax_rates( $matched_tax_rates, $country, $state, $postcode, $city ) {

		// never estimate on the admin
		if ( is_admin() ) {
			return $matched_tax_rates;
		}

		// do not estimate on cart if the option excludes it
		if ( is_cart() && 'yes' !== get_option( 'wc_avatax_calculate_on_cart', 'yes' ) ) {
			return $matched_tax_rates;
		}

		// The tax display setting that should prompt rate estimation.
		// For instance, no need to get any rates if prices exclude tax and are set to display that way.
		$display_setting = wc_prices_include_tax() ? 'excl' : 'incl';

		if ( ! is_cart() && ! is_checkout() && $display_setting !== get_option( 'woocommerce_tax_display_shop' ) ) {
			return $matched_tax_rates;
		}

		$rates = $this->get_estimated_rates( $country, $state, $postcode, $city );

		foreach ( $rates as $code => $rate ) {

			$matched_tax_rates[ $code ] = array(
				'rate'     => $rate->get_rate() * 100,
				'label'    => $rate->get_label(),
				'shipping' => 'yes', // TODO: Is there any way to determine if a rate applies to shipping? This won't affect accuracy, but it would be nice to know {CW 2017-04-14}
				'compound' => 'no',
			);
		}

		return $matched_tax_rates;
	}


	/**
	 * Gets estimated tax rates based on a customer address.
	 *
	 * Currently the AvaTax API limits this to US addresses.
	 *
	 * @since 1.5.0
	 *
	 * @param string $country customer country code
	 * @param string $state customer state/region code
	 * @param string $postcode customer postal code
	 * @param string $city customer city name
	 *
	 * @return \WC_AvaTax_API_Tax_Rate[] objects
	 */
	public function get_estimated_rates( $country, $state, $postcode, $city ) {

		$rates = array();

		// estimating is US-only & postcode is required
		if ( ! $this->country_can_estimate_rates( $country ) || ! $postcode || ! $this->is_location_taxable( $country, $state ) ) {
			return $rates;
		}

		try {

			$response = wc_avatax()->get_api()->get_estimated_rates( array(
				'city'     => $city,
				'state'    => $state,
				'postcode' => $postcode,
				'country'  => $country,
			) );

			return $response->get_rates();

		} catch ( Framework\SV_WC_Plugin_Exception $e ) {

			if ( wc_avatax()->logging_enabled() ) {
				wc_avatax()->log( 'Error estimating tax rate. ' . $e->getMessage() );
			}
		}

		return $rates;
	}


	/**
	 * Sets the custom AvaTax tax rate code.
	 *
	 * @internal
	 *
	 * @since 1.5.0
	 *
	 * @param string $code_string found tax rate code (this will be empty)
	 * @param int|string $key requested tax rate code ID
	 *
	 * @return string $code_string AvaTax tax rate code
	 */
	public function set_tax_rate_code( $code_string, $key ) {

		if ( Framework\SV_WC_Helper::str_starts_with( $key, $this->get_rate_prefix() ) ) {
			$code_string = $key;
		}

		return $code_string;
	}


	/**
	 * Sets the AvaTax tax rate label and amount label.
	 *
	 * @internal
	 *
	 * @since 1.5.0
	 *
	 * @param array $tax_totals existing tax rate totals
	 * @param \WC_Cart $cart cart object
	 *
	 * @return array
	 */
	public function set_tax_rate_labels( $tax_totals, $cart ) {

		$taxes = WC_Tax::get_rates();

		foreach ( array_keys( $tax_totals ) as $code ) {

			if ( Framework\SV_WC_Helper::str_starts_with( $code, $this->get_rate_prefix() ) && ! empty( $taxes[ $code ]['label'] ) ) {

				$tax_totals[ $code ]->label = $taxes[ $code ]['label'];
			}
		}

		return $tax_totals;
	}


	/**
	 * Sets the order item tax rate ID.
	 *
	 * This is primarily used so that taxes display properly in the admin.
	 *
	 * @since 1.5.0
	 *
	 * @param int $rate_id tax rate ID (this will be empty)
	 * @param \WC_Order_Item_Tax $item order item tax object
	 * @return string
	 */
	public function set_order_item_tax_rate_id( $rate_id, $item ) {

		if ( Framework\SV_WC_Helper::str_starts_with( $item->get_name(), $this->get_rate_prefix() ) ) {
			$rate_id = $item->get_name();
		}

		return $rate_id;
	}


	/**
	 * Determines if tax calculation is supported by the given country and/or
	 * state.
	 *
	 * Currently this only checks the plugin's availability settings and not any
	 * actual nexus settings in the merchant's Avalara account, as that information
	 * is not yet available via their REST API.
	 *
	 * @since 1.5.0
	 *
	 * @param string $country_code country code to check
	 * @param string $state state to check - omit to only check the country
	 *
	 * @return bool
	 */
	public function is_location_taxable( $country_code, $state = '' ) {

		$taxable = false;

		$locations = $this->get_enabled_locations();

		// if any state is valid (wildcard), no need to check further
		if ( in_array( $country_code . ':*', $locations, true ) ) {
			$taxable = true;
		} elseif ( $state ) {
			$taxable = in_array( $country_code . ':' . $state, $locations );
		}

		/**
		 * Filters whether a provided country/state combo is taxable by AvaTax.
		 *
		 * @since 1.2.3
		 * @param bool $taxable
		 * @param string $country_code the country code to check
		 * @param string $state the state to check
		 */
		return (bool) apply_filters( 'wc_avatax_is_location_taxable', $taxable, $country_code, $state );
	}


	/**
	 * Gets the locations where tax calculation is enabled in the settings.
	 *
	 * @since 1.5.0
	 *
	 * @return array
	 */
	public function get_enabled_locations() {

		if ( 'specific' === get_option( 'wc_avatax_tax_locations', 'all' ) ) {
			$locations = get_option( 'wc_avatax_specific_tax_locations', array() );
		} else {
			$locations = array_keys( $this->get_available_locations() );
		}

		/**
		 * Filter the locations where tax calculation is enabled in the settings.
		 *
		 * @since 1.5.0
		 *
		 * @param array $locations locations in the format $country_code:$state_code => $country_name
		 */
		return apply_filters( 'wc_avatax_enabled_tax_locations', $locations );
	}


	/**
	 * Gets the locations where tax calculation is available from Avalara.
	 *
	 * @since 1.5.0
	 *
	 * @return array
	 */
	public function get_available_locations() {

		$countries = ! empty( WC()->countries ) ? WC()->countries->get_allowed_countries() : array();

		// These countries can be supported at the state level
		$countries_with_jurisdictions = array(
			'BR',
			'CA',
			'IN',
			'US',
		);

		$locations = array();

		foreach ( $countries as $country_code => $country_name ) {

			$locations[ $country_code . ':*' ] = $country_name;

			if ( in_array( $country_code, $countries_with_jurisdictions ) && $states = WC()->countries->get_states( $country_code ) ) {

				foreach ( $states as $state_code => $state_name ) {
					$locations[ $country_code . ':' . $state_code ] = "&nbsp;&nbsp;{$country_name} &ndash; {$state_name}";
				}
			}
		}

		/**
		 * Filter the locations where tax calculation is available from Avalara.
		 *
		 * @since 1.1.0
		 *
		 * @param array $locations ocations in the format $country_code:$state_code => $country_name
		 */
		return apply_filters( 'wc_avatax_available_tax_locations', $locations );
	}


	/**
	 * Determines if a given country supports rate estimation.
	 *
	 * @since 1.5.0
	 *
	 * @param string $country_code country code
	 *
	 * @return bool
	 */
	public function country_can_estimate_rates( $country_code = '' ) {

		if ( ! $country_code ) {
			$country_code = WC()->customer->get_shipping_country();
		}

		$supported_countries = array(
			'US',
		);

		return in_array( $country_code, $supported_countries, true );
	}


	/**
	 * Determines if AvaTax calculation is available.
	 *
	 * @since 1.5.0
	 *
	 * @return bool
	 */
	public function is_available() {

		/**
		 * Filter whether AvaTax calculation is available.
		 *
		 * @since 1.0.0
		 * @param bool $is_available whether AvaTax calculation is available
		 */
		return (bool) apply_filters( 'wc_avatax_is_available', $this->is_enabled() && $this->is_configured() );
	}


	/**
	 * Determines if AvaTax calculation is configured.
	 *
	 * @since 1.5.0
	 *
	 * @return bool
	 */
	public function is_configured() {

		/**
		 * Filter whether AvaTax calculation is configured.
		 *
		 * @since 1.0.0
		 * @param bool $is_configured whether AvaTax calculation is configured
		 */
		return (bool) apply_filters( 'wc_avatax_is_configured', wc_tax_enabled() && $this->is_origin_address_complete() );
	}


	/**
	 * Determines if AvaTax calculation is enabled.
	 *
	 * @since 1.5.0
	 *
	 * @return bool
	 */
	public function is_enabled() {

		/**
		 * Filter whether AvaTax calculation is enabled.
		 *
		 * @since 1.0.0
		 * @param bool $is_enabled whether AvaTax calculation is enabled
		 */
		return (bool) apply_filters( 'wc_avatax_is_enabled', 'yes' === get_option( 'wc_avatax_enable_tax_calculation' ) );
	}


	/**
	 * Determiens if the configured origin address is complete enough.
	 *
	 * @since 1.5.0
	 *
	 * @return bool
	 */
	public function is_origin_address_complete() {

		$origin_address = $this->get_origin_address();

		return ! empty( $origin_address ) && ! empty( $origin_address['postcode'] ) && ! empty( $origin_address['country'] );
	}


	/**
	 * Gets the origin address.
	 *
	 * @since 1.5.0
	 *
	 * @return array
	 */
	public function get_origin_address() {

		/**
		 * Filters the origin address.
		 *
		 * @since 1.5.0
		 *
		 * @param array $address origin address
		 */
		return apply_filters( 'wc_avatax_origin_address', get_option( 'wc_avatax_origin_address' ) );
	}


	/**
	 * Gets the default tax code for shipping.
	 *
	 * @since 1.5.0
	 *
	 * @return string
	 */
	public function get_default_product_tax_code() : string {

		/**
		 * Filters the default tax code for shipping.
		 *
		 * @since 1.5.0
		 *
		 * @param string $tax_code default tax code for shipping
		 */
		return (string) apply_filters( 'wc_avatax_default_product_tax_code', get_option( 'wc_avatax_default_product_code', 'P0000000' ) );
	}


	/**
	 * Gets the default tax code for shipping.
	 *
	 * @since 1.5.0
	 *
	 * @return string
	 */
	public function get_default_shipping_tax_code() {

		/**
		 * Filters the default tax code for shipping.
		 *
		 * @since 1.5.0
		 *
		 * @param string $tax_code default tax code for shipping
		 */
		return apply_filters( 'wc_avatax_default_shipping_tax_code', get_option( 'wc_avatax_shipping_code', 'FR' ) );
	}


	/**
	 * Gets the prefix assigned to any tax rate originating from the AvaTax API.
	 *
	 * @since 1.5.0
	 *
	 * @return string
	 */
	public function get_rate_prefix() {

		return self::RATE_PREFIX;
	}


	/**
	 * Determines if WooCommerce tax rates should be completely overridden.
	 *
	 * @since 1.5.0
	 *
	 * @return bool
	 */
	public function override_wc_rates() {

		/**
		 * Filter whether WooCommerce tax rates should be overridden.
		 *
		 * @since 1.0.0
		 *
		 * @param bool $override whether WooCommerce tax rates should be overridden
		 */
		return (bool) apply_filters( 'wc_avatax_override_woocommerce_rates', $this->is_available() );
	}


}

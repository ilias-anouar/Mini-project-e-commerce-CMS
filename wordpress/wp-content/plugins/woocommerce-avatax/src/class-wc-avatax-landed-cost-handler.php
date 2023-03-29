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

use SkyVerge\WooCommerce\AvaTax\API\Models\HS_Classification_Model;
use SkyVerge\WooCommerce\AvaTax\Traits\Resolves_Product_Item_Code;

defined( 'ABSPATH' ) or exit;

/**
 * The base landed cost handler class.
 *
 * @since 1.5.0
 */
class WC_AvaTax_Landed_Cost_Handler {

	use Resolves_Product_Item_Code;

	/**
	 * Gets the HTS code for a product.
	 *
	 * If a country is provided, it will try and get the fully qualified HTS code
	 * for the product and country, based on the plugin configuration.
	 *
	 * @since 1.5.0
	 * @deprecated 1.16.0
	 *
	 * @param \WC_Product $product product object
	 * @param string $destination_country shipping destination country
	 * @return string $code the product's HTS code
	 */
	public function get_hts_code( WC_Product $product, $destination_country = '' ) {

		wc_deprecated_function( __METHOD__, '1.16.0' );

		$code = $product->get_meta( '_wc_avatax_hts_code' );

		// if a variation, check for the parent product's HTS code
		if ( ! $code && $product->is_type( 'variation' ) ) {

			$product = wc_get_product( $product->get_parent_id( 'edit' ) );

			$code = $product->get_meta( '_wc_avatax_hts_code' );
		}

		if ( ! $code ) {

			$categories = get_the_terms( $product->get_id(), 'product_cat' );

			if ( is_array( $categories ) ) {

				foreach ( $categories as $category ) {

					if ( $category_code = get_term_meta( $category->term_id, 'wc_avatax_hts_code', true ) ) {
						$code = $category_code;
						break;
					}
				}
			}
		}

		if ( $code ) {

			if ( ! $destination_country ) {
				$destination_country = WC()->countries->get_base_country();
			}

			if ( $country_code = $this->get_country_class_code( $code, $destination_country ) ) {
				$code .= $country_code;
			}
		}

		/**
		 * Filters a product's HTS code.
		 *
		 * @since 1.5.0
		 * @deprecated 1.16.0
		 *
		 * @param string $code HTS code
		 * @param \WC_Product $product product object
		 * @param string $destination_country shipping destination country
		 */
		return apply_filters( 'wc_avatax_landed_cost_product_hts_code', $code, $product, $destination_country );
	}


	/**
	 * Gets classification code configured for an HTS code & country.
	 *
	 * @since 1.5.0
	 * @deprecated 1.16.0
	 *
	 * @param string $hts_code the product HTS code
	 * @param string $country destination country code
	 * @return string $code country-specific tariff code
	 */
	public function get_country_class_code( $hts_code, $country ) {

		wc_deprecated_function( __METHOD__, '1.16.0' );

		$stored_codes = $this->get_classes( $hts_code );
		$code         = '';

		foreach ( $stored_codes as $class_code => $countries ) {

			if ( in_array( $country, $countries, true ) ) {
				$code = $class_code;
				break;
			}
		}

		return $code;
	}


	/**
	 * Gets all configured classification codes.
	 *
	 * @since 1.5.0
	 * @deprecated 1.16.0
	 *
	 * @param string $hts_code specific HTS code
	 * @return array
	 */
	public function get_classes( $hts_code = '' ) {

		wc_deprecated_function( __METHOD__, '1.16.0' );

		$classes = get_option( 'wc_avatax_landed_cost_classes', array() );

		// if looking for a specific HTS code
		if ( $hts_code ) {

			$classes = isset( $classes[ $hts_code ] ) ? $classes[ $hts_code ] : array();

		// otherwise, get 'em all
		} else {

			$hts_codes = $this->get_hts_codes();

			foreach ( $classes as $code => $data ) {

				if ( ! in_array( $code, $hts_codes ) ) {
					unset( $classes[ $code ] );
				}
			}
		}

		return $classes;
	}


	/**
	 * Gets all product HTS codes.
	 *
	 * @since 1.5.0
	 * @deprecated 1.16.0
	 *
	 * @param bool $use_cache whether to use the cache or get fresh results
	 * @return array
	 */
	public function get_hts_codes( $use_cache = true ) {
		global $wpdb;

		wc_deprecated_function( __METHOD__, '1.16.0' );

		$codes = $this->get_hts_cache();

		if ( ! $use_cache || empty( $codes ) ) {

			$product_codes = $wpdb->get_col( "SELECT meta_value FROM $wpdb->postmeta WHERE meta_key = '_wc_avatax_hts_code'" );
			$term_codes    = $wpdb->get_col( "SELECT meta_value FROM $wpdb->termmeta WHERE meta_key = 'wc_avatax_hts_code'" );

			$codes = array_filter( array_unique( array_merge( $product_codes, $term_codes ) ) );

			$this->set_hts_cache( $codes );
		}

		return $codes;
	}


	/**
	 * Gets the HTS code cache.
	 *
	 * @since 1.5.0
	 * @deprecated 1.16.0
	 *
	 * @return array
	 */
	public function get_hts_cache() {

		wc_deprecated_function( __METHOD__, '1.16.0' );

		return ( $codes = get_transient( 'wc_avatax_hts_codes' ) ) ? $codes : array();
	}


	/**
	 * Sets the HTS code cache.
	 *
	 * @since 1.5.0
	 * @deprecated 1.16.0
	 *
	 * @param array $codes HTS codes
	 */
	public function set_hts_cache( $codes ) {

		wc_deprecated_function( __METHOD__, '1.16.0' );

		set_transient( 'wc_avatax_hts_codes', $codes, 15 * DAY_IN_SECONDS );
	}


	/**
	 * Clears the HTS code cache.
	 *
	 * @since 1.5.0
	 * @deprecated 1.16.0
	 */
	public function clear_hts_cache() {

		wc_deprecated_function( __METHOD__, '1.16.0' );

		delete_transient( 'wc_avatax_hts_codes' );
	}


	/**
	 * Determines if landed cost is enabled.
	 *
	 * @since 1.5.0
	 * @deprecated 1.16.0 - Landed Costs Admin panel & settings have been removed, rendering this method obsolete
	 *
	 * @return bool
	 */
	public function is_enabled() : bool {

		wc_deprecated_function( __METHOD__, '1.16.0' );

		/**
		 * Filters whether Landed Cost is enabled.
		 *
		 * @since 1.5.0
		 * @deprecated 1.16.0
		 *
		 * @param bool $is_enabled
		 */
		return (bool) apply_filters( 'wc_avatax_is_landed_cost_enabled', 'yes' === get_option( 'wc_avatax_enable_landed_cost', 'no' ) );
	}


	/**
	 * Gets the Landed Cost Incoterms.
	 *
	 * Since 1.16.0 this method returns null, unless the wc_avatax_landed_cost_incoterms option has been set
	 * previously. This is because most AvaTax accounts use Nexus-level importer-of-record setting, in which case the
	 * transaction-level flag has no effect. However, since this option was previously available, and because some
	 * accounts may need to use transaction-level flag, we provide a filter which has access to the request data.
	 *
	 * @since 1.5.0
	 *
	 * @param array $data request data
	 * @return string|null
	 */
	public function get_incoterms( array $data = [] ) {

		/**
		 * Filters the Landed Cost Incoterms.
		 *
		 * @since 1.5.0
		 *
		 * @param string $incoterms Landed Cost Incoterms (either seller or buyer)
		 * @param array $data request data
		 */
		return apply_filters( 'wc_avatax_landed_cost_incoterms', get_option( 'wc_avatax_landed_cost_incoterms', null ), $data );
	}


	/**
	 * Gets the Landed Cost shipping mode.
	 *
	 * @since 1.5.0
	 * @deprecated 1.16.0
	 *
	 * @return string
	 */
	public function get_shipping_mode() {

		wc_deprecated_function( __METHOD__, '1.16.0' );

		/**
		 * Filters the Landed Cost shipping mode.
		 *
		 * @since 1.5.0
		 *
		 * @param string $shipping_mode Landed Cost shipping mode
		 */
		return apply_filters( 'wc_avatax_landed_cost_shipping_mode', get_option( 'wc_avatax_landed_cost_shipping_mode', 'ground' ) );
	}


	/**
	 * Adds action & filter hooks.
	 *
	 * Note that since 1.16.0 we no longer check if the feature is enabled/available, as AvaTax may calculate
	 * cross-border duties regardless if the feature has been enabled in the WooCommerce extension. As such, we want to
	 * ensure we're properly handled these cases as well.
	 *
	 * @since 1.5.0
	 */
	public function add_hooks() {


		// add Landed Cost notes after an order is posted to Avalara
		add_action( 'wc_avatax_after_order_tax_calculated', [ $this, 'add_calculated_order_notes' ], 10, 2 );
		add_action( 'woocommerce_checkout_order_processed', [ $this, 'add_checkout_order_notes' ] );

		// replace VAT/Tax with Import Fees if there are landed costs
		add_filter( 'woocommerce_countries_tax_or_vat', [ $this, 'replace_tax_or_vat' ] );

		// reorder the taxes to make sure any landed costs are displayed first
		add_filter( 'woocommerce_cart_get_taxes', [ $this, 'reorder_taxes' ], 10, 2 );
	}


	/**
	 * Adds Landed Cost notes after an order is posted to Avalara.
	 *
	 * This ensures the merchant is better informed if duties are not calculated
	 * for some reason.
	 *
	 * @internal
	 *
	 * @since 1.5.0
	 *
	 * @param int $order_id order ID
	 * @param WC_AvaTax_API_Tax_Response $response tax calculation response object
	 */
	public function add_calculated_order_notes( $order_id, $response ) {

		$order = wc_get_order( $order_id );

		if ( ! $order ) {
			return;
		}

		$this->add_order_notes_from_api_response( $order, $response );
	}


	/**
	 * Adds Landed Cost notes after an order is processed at checkout.
	 *
	 * This ensures the merchant is better informed if duties are not calculated
	 * for some reason.
	 *
	 * @internal
	 *
	 * @since 1.16.0
	 *
	 * @param int|WC_Order $order_id The order ID, or order object
	 */
	public function add_checkout_order_notes( $order_id ) {

		$order = wc_get_order( $order_id );

		if ( ! $order || ! WC()->cart->avatax_response instanceof WC_AvaTax_API_Tax_Response ) {
			return;
		}

		/** @see WC_AvaTax_Checkout_Handler::calculate_taxes() */
		$this->add_order_notes_from_api_response( $order, WC()->cart->avatax_response );
	}


	/**
	 * Adds order notes from AvaTax API response messages.
	 *
	 * @since 1.16.0
	 *
	 * @param WC_Order $order
	 * @param WC_AvaTax_API_Tax_Response $response tax calculation response object
	 * @return void
	 */
	protected function add_order_notes_from_api_response( WC_Order $order, WC_AvaTax_API_Tax_Response $response ) {

		$lines_missing_hs_codes = [];

		foreach ( $response->get_messages() as $message ) {

			if ( empty( $message->summary ) ) {
				continue;
			}

			if ( 'LandedCost' === ( $message->refersTo ?? null ) ) {
				$order->add_order_note( $message->summary );
			} elseif ( 'MissingHSCodeWarning' === $message->summary ) {
				// refersTo is something like `LineNo : 123`
				$lines_missing_hs_codes[] = trim( explode( ':', $message->refersTo)[1] );
			}
		}

		if ( ! empty( $lines_missing_hs_codes ) ) {

			$message = __( 'Import duties could not be calculated for the following items (missing cross-border classification for the destination country):', 'woocommerce-avatax' );

			$item_names = [];

			foreach( $lines_missing_hs_codes as $line_no ) {

				// In case the `Line No` is not numeric, it's likely cart item key - in which case we get the name from the cart item product.
				// Otherwise, we can just get it from the matching order item.
				// Note that since WC does not store the cart item key on the order line item, there's no way to simply map the cart item key
				// to the saved order item. Even though the `woocommerce_checkout_create_order_line_item` hook receives both the order item and
				// cart item key, the order item is not saved at that point, so it does not have an ID.
				if ( ! is_numeric( $line_no ) && isset( WC()->cart ) && $item = WC()->cart->get_cart_item( $line_no ) ) {
					$item_names[] = $item['data']->get_name();
				} else if ( $item = $order->get_item( $line_no ) )  {
					$item_names[] = $item->get_name();
				}
			}

			$message .= ' ' . implode( ', ', $item_names );

			$order->add_order_note( $message );
		}

	}


	/**
	 * Replaces VAT/Tax with Import Fees if there are landed costs
	 * and taxes are displayed as a single total.
	 *
	 * @since 1.10.0
	 *
	 * @param string $label the original label (Tax or VAT)
	 * @return string
	 */
	public function replace_tax_or_vat( $label ) {

		if ( 'single' === get_option( 'woocommerce_tax_total_display' ) && ! empty( WC()->cart->avatax_has_landed_costs ) ) {

			$label = __( 'Import Fees', 'woocommerce-avatax' );
		}

		return $label;
	}


	/**
	 * Reorders the taxes to make sure any landed costs are displayed first.
	 *
	 * @see WC_Cart::get_taxes()
	 *
	 * @since 1.10.0
	 *
	 * @param array $taxes the original taxes
	 * @param \WC_Cart $cart the cart
	 * @return array
	 */
	public function reorder_taxes( $taxes, $cart ) {

		if ( $cart->avatax_has_landed_costs ) {

			// search for a key containing LandedCost
			$landed_cost_tax = array_filter( $taxes, function ( $key ) {
				return strpos( $key, 'LandedCost' ) !== false;
			}, ARRAY_FILTER_USE_KEY );

			if ( ! empty( $landed_cost_tax ) ) {

				unset( $taxes[ key( $landed_cost_tax ) ] );

				//  add it to the beginning of the array because it needs to be displayed before other taxes
				$taxes = [ key( $landed_cost_tax ) => current( $landed_cost_tax ) ] + $taxes;
			}
		}

		return $taxes;
	}


	/**
	 * Determines whether the store is subscribed to Cross Border.
	 *
	 * @since 1.13.0
	 *
	 * @deprecated 1.16.0 - it's currently not possible to check for Item Classification subscription via the API.
	 *
	 * @return bool
	 */
	public function is_subscribed_to_cross_border() : bool {

		wc_deprecated_function( __METHOD__, '1.16.0', 'WC_AvaTax_Landed_Cost_Handler::can_connect_to_hs_api' );

		return false;
	}


	/**
	 * Checks whether the store can connect to HS classification API.
	 *
	 * @since 1.16.0
	 *
	 * @return bool
	 */
	public function can_connect_to_hs_api() : bool {

		if ( ! wc_avatax()->has_hs_api_credentials_set() ) {
			return false;
		}

		try {
			// Send a test GetHSClassificationRequest - a response with an auth error indicates invalid credentials or a
			// missing subscription. As long as there's no auth error (404 Not Found is fine in this context), we should
			// be able to connect.
			return ! wc_avatax()->get_hs_api()->get_hs_classification(
				( new HS_Classification_Model() )->set_id(
					$this->generate_classification_id( 'wc-avatax-connection-test', 'US' )
				)
			)->has_auth_error();

		} catch ( Exception $exception ) {

			if ( wc_avatax()->logging_enabled() ) {
				wc_avatax()->log( $exception->getMessage() );
			}

			return false;
		}
	}


	/**
	 * Gets the selected sync countries.
	 *
	 * @since 1.13.0
	 *
	 * @return array
	 */
	public function get_countries_for_product_sync() : array {

		return (array) get_option( 'wc_avatax_api_product_countries_sync', []);
	}


	/**
	 * Determines whether at least one supported country is selected for syncing.
	 *
	 * @since 1.13.0
	 *
	 * @return bool
	 */
	public function has_countries_for_product_sync() : bool {

		return ! empty( $this->get_countries_for_product_sync() );
	}


	/**
	 * Gets the selected sync countries grouped by their product classification systems.
	 *
	 * @since 1.16.0
	 *
	 * @return array
	 */
	public function get_countries_for_product_sync_grouped_by_product_classification_system() : array {

		$systems = [];

		foreach ( $this->get_countries_for_product_sync() as $country ) {

			if ( $system = $this->get_product_classification_system_for_country( $country ) ) {

				if ( ! isset( $systems[ $system ] ) ) {
					$systems[ $system ] = [];
				}

				$systems[ $system ][] = $country;
			}
		}

		return $systems;
	}


	/**
	 * Gets an optimized list of the selected countries for product sync.
	 *
	 * The returned list only includes one country per classification system.
	 *
	 * @since 1.16.0
	 *
	 * @return string[]
	 */
	public function get_optimized_list_of_countries_for_product_sync() : array {

		return array_map( 'current', array_values( $this->get_countries_for_product_sync_grouped_by_product_classification_system() ) );
	}


	/**
	 * Stores an HS Code as a product meta by country.
	 *
	 * TODO: consider if this method is needed after sites have fully moved over to optimized sync handling {IT 2022-01-06}
	 *
	 * @since 1.13.0
	 *
	 * @param \WC_Product $product the WooCommerce product
	 * @param string $destination_country the country code
	 * @param string $hs_code the HS code
	 */
	public function save_hs_code( \WC_Product $product, string $destination_country, string $hs_code) {

		$hs_codes = $product->get_meta( '_wc_avatax_hs_codes' );

		if ( is_array( $hs_codes ) ) {
			$hs_codes[ $destination_country ] = $hs_code;
		} else {
			$hs_codes = [ $destination_country => $hs_code ];
		}

		ksort($hs_codes);

		$product->update_meta_data( '_wc_avatax_hs_codes', $hs_codes );
		$product->save_meta_data();
	}


	/**
	 * Gets the HS code for a product and country.
	 *
	 * TODO: consider if this method is needed after sites have fully moved over to optimized sync handling {IT 2022-01-06}
	 *
	 * @since 1.13.0
	 *
	 * @param \WC_Product $product a WooCommerce product
	 * @param string $destination_country a country code
	 * @return string
	 */
	public function get_hs_code( \WC_Product $product, string $destination_country ) : string {

		$hs_codes = $product->get_meta( '_wc_avatax_hs_codes' );

		// if a variation, check for the parent product's HS codes
		if ( empty( $hs_codes ) && $product->is_type( 'variation' ) ) {

			$parent_product = wc_get_product( $product->get_parent_id( 'edit' ) );

			$hs_codes = $parent_product->get_meta( '_wc_avatax_hs_codes' );
		}

		$hs_code = ! empty( $hs_codes ) && is_array( $hs_codes ) && isset( $hs_codes[ $destination_country ] ) ? $hs_codes[ $destination_country ] : '';

		/**
		 * Filters an HS code for a product.
		 *
		 * @since 1.13.0
		 *
		 * @param string $hs_code the found HS code
		 * @param \WC_Product the product the code is for
		 * @param string $destination_country the destination country
		 */
		return (string) apply_filters( 'wc_avatax_landed_cost_product_hs_code', $hs_code, $product, $destination_country );
	}


	/**
	 * Stores a classification ID as a product meta by country.
	 *
	 * @since 1.13.0
	 *
	 * @param \WC_Product $product the WooCommerce product
	 * @param string $destination_country the country code
	 * @param string $classification_id the classification ID, optional - will be generated if not provided
	 */
	public function save_classification_id( \WC_Product $product, string $destination_country, string $classification_id = null ) {

		if ( ! $classification_id ) {
			$classification_id = $this->generate_classification_id( $this->resolve_product_item_code( $product ), $destination_country );
		}

		$classification_ids = $product->get_meta( '_wc_avatax_classification_ids' );

		if ( is_array( $classification_ids ) ) {
			$classification_ids[ $destination_country ] = $classification_id;
		} else {
			$classification_ids = [ $destination_country => $classification_id ];
		}

		$product->update_meta_data( '_wc_avatax_classification_ids', $classification_ids );
		$product->save_meta_data();
	}


	/**
	 * Generates a classification ID for the given item code and country pair.
	 *
	 * The classification ID consists of the company ID, item code and the destination country. Note that the online
	 * Classification API docs no longer have this information - it was taken from the older PDF docs.
	 *
	 * @since 1.16.0
	 *
	 * @param int|string $item_code
	 * @param string $country
	 * @return string
	 */
	protected function generate_classification_id( $item_code, string $country ) : string {

		$company_id = wc_avatax()->get_company_id();

		return "{$company_id}-{$item_code}-{$country}";
	}


	/**
	 * Gets the classification ID for a product and country.
	 *
	 * @since 1.13.0
	 *
	 * @param \WC_Product $product a WooCommerce product
	 * @param string $destination_country a country code
	 * @return string
	 */
	public function get_classification_id( \WC_Product $product, string $destination_country ) : string {

		$classification_ids = $product->get_meta( '_wc_avatax_classification_ids' );
		$classification_id = ! empty( $classification_ids ) && is_array( $classification_ids ) && isset( $classification_ids[ $destination_country ] ) ? $classification_ids[ $destination_country ] : '';

		/**
		 * Filters the classification ID for a product at a destination country.
		 *
		 * @since 1.13.0
		 *
		 * @param string $classification_id
		 * @param \WC_Product $product
		 * @param string $destination_country
		 */
		return (string) apply_filters( 'wc_avatax_landed_cost_product_classification_id', $classification_id, $product, $destination_country );
	}


	/**
	 * Safely fetches a result from the API, storing the result in a transient.
	 *
	 * In case of an API exception, catches and logs it
	 *
	 * @since 1.16.0
	 *
	 * @param Closure $callback a function to perform the actual API request and returns the value, receives WC_AvaTax_API as a single argument
	 * @param string $transient the transient name for storing the value
	 * @param int|null $timeout transient timeout in seconds, optional, defaults to 1 day
	 * @return mixed
	 */
	protected function safely_get_api_result( Closure $callback, string $transient, int $timeout = null ) {

		$value = get_transient( $transient );

		// can't use a simple truthy check here, as only boolean false will indicate a missing or expired transient
		if ( $value !== false ) {
			return $value;
		}

		try {

			if ( $api = wc_avatax()->get_api() ) {

				$value = $callback( $api );

				set_transient( $transient, $value, $timeout ?? DAY_IN_SECONDS );

				return $value;
			}

		} catch ( Exception $e ) {

			if ( wc_avatax()->logging_enabled() ) {
				wc_avatax()->log( sprintf( '%1$s: %2$s', $e->getCode() ?? 'Error', $e->getMessage() ) );
			}
		}

		return null;
	}


	/**
	 * Gets the supported countries list.
	 *
	 * @since 1.13.0
	 *
	 * @return string[] array of country codes
	 */
	public function get_supported_countries() : array {

		return (array) $this->safely_get_api_result( function( WC_AvaTax_API $api ) {
			return $api->get_nexus_list()->get_country_list();
		}, 'wc_avatax_landed_cost_supported_countries' );
	}


	/**
	 * Gets all the supported product classification systems.
	 *
	 * @since 1.16.0
	 *
	 * @return array
	 */
	public function get_product_classification_systems() : array {

		return (array) $this->safely_get_api_result( function( WC_AvaTax_API $api ) {
			return $api->get_product_classification_systems_list()->get_current_system_list();
		}, 'wc_avatax_landed_cost_product_classification_systems' );
	}


	/**
	 * Gets a list of countries using the given product classification system.
	 *
	 * @since 1.16.0
	 *
	 * @param string $system_code the classification system code
	 * @return array an array of country codes, if any
	 */
	public function get_countries_using_product_classification_system( string $system_code ) : array {

		return $this->get_product_classification_systems()[ $system_code ] ?? [];
	}


	/**
	 * Gets the current product classification system for the given country.
	 *
	 * @since 1.16.0
	 *
	 * @param string $country the country code
	 * @return string the classification system code or an empty string
	 */
	public function get_product_classification_system_for_country( string $country ) : string {

		foreach( $this->get_product_classification_systems() as $system_code => $countries ) {
			if ( in_array( $country, $countries, true ) ) {
				return $system_code;
			}
		}

		return '';
	}


}

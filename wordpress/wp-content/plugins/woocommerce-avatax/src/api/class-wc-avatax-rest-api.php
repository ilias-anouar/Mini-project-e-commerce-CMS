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
 * Handler for the WooCommerce REST API.
 *
 * @since 1.7.0
 */
class WC_AvaTax_REST_API {


	/** \WC_Avatax plugin main instance */
	private $plugin;


	/**
	 * Hooks in the WooCommerce REST API.
	 *
	 * @since 1.7.0
	 *
	 * @param \WC_Avatax $plugin main instance
	 */
	public function __construct( $plugin ) {

		$this->plugin = $plugin;

		// WC REST API v1 (WC 2.6+)

		// insert order
		add_action( 'woocommerce_rest_insert_shop_order',  array( $this, 'process_rest_api_order' ), 0, 1 );
		// order response
		add_filter( 'woocommerce_rest_prepare_shop_order', array( $this, 'handle_order_response' ), 0, 3 );

		// WC REST API v2 (WC 3.0+ ) / v3 (WC 3.5+)

		// insert order
		add_action( 'woocommerce_rest_insert_shop_order_object',  array( $this, 'process_rest_api_order' ), 0, 1 );
		// order response
		add_filter( 'woocommerce_rest_prepare_shop_order_object', array( $this, 'handle_order_response' ), 0, 3 );
	}


	/**
	 * Gets the main plugin instance.
	 *
	 * @since 1.7.0
	 *
	 * @return \WC_Avatax
	 */
	protected function get_plugin() {

		return $this->plugin;
	}


	/**
	 * Processes orders created via the REST API.
	 *
	 * @internal
	 *
	 * @since 1.7.0
	 *
	 * @param \WC_Order|int $order order object or ID
	 */
	public function process_rest_api_order( $order ) {

		if ( ! $order instanceof WC_Order ) {
			$order = wc_get_order( $order );
		}

		if ( $order ) {

			$order_handler = wc_avatax()->get_order_handler();

			// don't calculate orders that have already been sent or aren't taxable
			if ( $order_handler->is_order_posted( $order ) || ! $order_handler->is_order_taxable( $order ) ) {
				return;
			}

			// always estimate the tax first
			$order_handler->estimate_tax( $order );

			// but only record a document if it's paid
			if ( $order->is_paid() ) {
				$order_handler->process_paid_order( $order );
			}

			$order->calculate_totals( true );
		}
	}


	/**
	 * Filters the order response data to tweak AvaTax data.
	 *
	 * WooCommerce REST API order endpoint expects tax rate IDs to be integers so we make sure that AvaTax doesn't push its alphanumerical strings there.
 	 * Also, when creating an order via the REST API, this callback ensures that the tax totals are calculated before returned in the API response.
	 *
	 * @internal
	 *
	 * @since 1.7.0
	 *
	 * @param \WP_REST_Response $response response object
	 * @param \WP_Post $post order post object
	 * @param \WP_REST_Request $request request object
	 * @return \WP_REST_Response
	 */
	public function handle_order_response( $response, $post, $request ) {

		// only adjust rates by default for REST API v3
		$adjust_tax_rates = Framework\SV_WC_Helper::str_exists( $request->get_route(), 'wc/v3' );

		/**
		 * Filters whether to adjust REST API order response tax rates to add the AvaTax rate code.
		 *
		 * Versions prior to 1.7.0 did not make this adjustment, so the filter ensure backwards compatibility with client code that's expecting the old format.
		 *
		 * @since 1.7.0
		 *
		 * @param bool $adjust_tax_rates whether to adjust REST API order response tax rates
		 * @param \WP_REST_Response $response response object
		 * @param \WP_REST_Request $request request object
		 */
		if ( ! apply_filters( 'wc_avatax_rest_api_adjust_order_response_tax_rates', $adjust_tax_rates, $response, $request ) ) {
			return $response;
		}

		$data = $response->get_data();

		if ( is_array( $data ) ) {

			$new_data    = $data;
			$order_items = array(
				'line_items',
				'shipping_lines',
				'fee_lines',
				'coupon_lines',
			);

			foreach ( $order_items as $items ) {

				if ( isset( $data[ $items ] ) )  {

					$new_data = $this->handle_order_items_rate( $items, $new_data );
				}
			}

			$response->set_data( $new_data );
		}

		return $response;
	}


	/**
	 * Adjusts line item rate data.
	 *
	 * @since 1.7.0
	 *
	 * @param string $key key to process, for example: 'line_items', 'shipping_lines', 'fee_lines', 'coupon_lines'...
	 * @param array $order_data whole order data
	 * @return array
	 */
	private function handle_order_items_rate( $key, array $order_data ) {

		$new_data = $order_data;

		if ( is_array( $order_data[ $key ] ) && ! empty( $order_data[ $key ] ) ) {

			foreach ( $order_data[ $key ] as $item_key => $item_data ) {

				$new_data[ $key ][ $item_key ] = $item_data;

				if ( isset( $item_data['taxes'] ) && is_array( $item_data['taxes'] ) ) {

					$new_data[ $key ][ $item_key ]['taxes'] = array();

					foreach ( $item_data['taxes'] as $index => $tax ) {

						// skip if the value is already of an expected type
						if ( ! is_array( $tax ) || ! isset( $tax['id'] ) || is_numeric( $tax['id'] ) || ! is_string( $tax['id'] ) ) {
							$new_data[ $key ][ $item_key ]['taxes'][ $index ] = $tax;
							continue;
						}

						if ( Framework\SV_WC_Helper::str_starts_with( strtoupper( trim( $tax['id'] ) ), 'AVATAX-' ) ) {

							// the expected type for the tax ID must be an integer, so we move the special AvaTax identifier to a custom property
							$tax['rate_code'] = $tax['id'];
							// we cannot replace the tax ID with nothing else than 0 because there's no unique numerical ID we could supply to WooCommerce, and we can just assume 0 must be treated as a special case
							$tax['id']        = 0;
						}

						// we replace the line item tax with the updated one for the response
						$new_data[ $key ][ $item_key ]['taxes'][ $index ] = $tax;
					}
				}
			}
		}

		return $new_data;
	}


}

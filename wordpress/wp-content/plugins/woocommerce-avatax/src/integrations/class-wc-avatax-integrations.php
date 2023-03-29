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

use SkyVerge\WooCommerce\AvaTax\Integrations\ApplePay;

defined( 'ABSPATH' ) or exit;

/**
 * The base integrations handler.
 *
 * @since 1.5.0
 */
class WC_AvaTax_Integrations {


	/** @var ApplePay Apple Pay integration instance */
	protected $apple_pay;


	/**
	 * Constructor.
	 *
	 * TODO: move subscription integration methods into their own integration class {WV 2021-04-05}
	 *
	 * @since 1.5.0
	 */
	public function __construct() {

		// remove any tax calculation meta from a newly created subscription
		add_action( 'woocommerce_checkout_subscription_created', array( $this, 'remove_new_subscription_meta' ) );

		// remove tax calculation meta from a newly created subscription's items
		add_action( 'woocommerce_checkout_create_order_line_item', array( $this, 'remove_new_subscription_item_meta' ), 10, 4 );

		// re-calculate taxes for a renewal order when recurring total changes are supported
		add_filter( 'wcs_renewal_order_created', array( $this, 'recalculate_renewal_taxes' ), 50, 2 );

		// set a product's shipping destination based on Local Pickup Plus data
		add_filter( 'wc_avatax_product_destination', array( $this, 'set_lpp_destination' ), 10, 3 );

		add_filter( 'wc_avatax_checkout_ready_for_calculation', array( $this, 'set_lpp_ready_for_calculation' ) );

		$this->load_integrations();
	}


	/**
	 * Initializes integration classes.
	 *
	 * @since 1.12.0
	 */
	private function load_integrations() {

		$this->apple_pay = wc_avatax()->load_class( '/src/integrations/ApplePay.php', ApplePay::class );
	}


	/**
	 * Removes tax calculation meta from a newly created subscription.
	 *
	 * @internal
	 *
	 * @since 1.5.0
	 *
	 * @param \WC_Subscription $subscription subscription object
	 */
	public function remove_new_subscription_meta( $subscription ) {

		$subscription->delete_meta_data( '_wc_avatax_tax_date' );
		$subscription->delete_meta_data( '_wc_avatax_origin_address' );
		$subscription->delete_meta_data( '_wc_avatax_destination_address' );
		$subscription->save_meta_data();
	}


	/**
	 * Removes tax calculation meta from a newly created subscription's items.
	 *
	 * @internal
	 *
	 * @since 1.5.0
	 *
	 * @param \WC_Order_Item $item order item
	 * @param string $cart_item_key cart item key
	 * @param array $cart_item cart item data
	 * @param \WC_Order $subscription subscription object
	 */
	public function remove_new_subscription_item_meta( $item, $cart_item_key, $cart_item, $subscription ) {

		if ( function_exists( 'wcs_is_subscription' ) && wcs_is_subscription( $subscription ) ) {
			$item->delete_meta_data( '_wc_avatax_origin_address' );
			$item->delete_meta_data( '_wc_avatax_destination_address' );
		}
	}


	/**
	 * Recalculate renewal taxes and send to Avalara.
	 *
	 * Recalculates taxes for a renewal order when created, but before sent to the payment gateway.
	 * Requires Subscriptions 2.0+.
	 *
	 * @internal
	 *
	 * @since 1.5.0
	 *
	 * @param \WC_Order $renewal_order the newly-created renewal order
	 * @param int|\WC_Subscription $subscription Post ID of a 'shop_subscription' post, or instance of a WC_Subscription object
	 * @return \WC_Order updated renewal order
	 */
	public function recalculate_renewal_taxes( $renewal_order, $subscription ) {

		$result = null;

		// do not remove this instanceof check due to calculate_order_tax() type-hinting
		if ( ! $renewal_order instanceof WC_Order ) {
			return $result;
		}

		if ( ! is_object( $subscription ) ) {
			$subscription = wcs_get_subscription( $subscription );
		}

		// this covers manual renewals as well
		if ( $subscription->payment_method_supports( 'subscription_amount_changes' ) ) {

			// only calculate via AvaTax if the order has a taxable address, as configured in the settings
			if ( wc_avatax()->get_order_handler()->is_order_taxable( $renewal_order ) ) {

				// re-calculate the subscription first to update the totals
				wc_avatax()->get_order_handler()->calculate_order_tax( $subscription, false, true );

				// re-calculate the renewal order
				$result = wc_avatax()->get_order_handler()->calculate_order_tax( $renewal_order, false, true );

			// otherwise, recalculate all of the totals without AvaTax
			// this ensures that address changes away from taxable locations don't continue to charge the previous taxable amount
			} else {

				$subscription->calculate_totals();
				$renewal_order->calculate_totals();
			}
		}

		return $result instanceof WC_Order ? $result : $renewal_order;
	}


	/**
	 * Sets a product's shipping destination based on Local Pickup Plus data.
	 *
	 * @internal
	 *
	 * @since 1.5.0
	 *
	 * @param array $address destination address
	 * @param \WC_Product $product product object
	 * @param array $package shipping package
	 * @return array
	 */
	public function set_lpp_destination( $address, $product, $package ) {

		if ( ! empty( $package['pickup_location_id'] ) && function_exists( 'wc_local_pickup_plus_get_pickup_location' ) && $pickup_location = wc_local_pickup_plus_get_pickup_location( $package['pickup_location_id'] ) ) {

			$address = $pickup_location->get_address()->get_array();
		}

		return $address;
	}


	/**
	 * Sets the cart as "ready for calculation" when a pickup is being used.
	 *
	 * @since 1.5.0
	 *
	 * @param bool $ready true if ready for calculation
	 * @return bool
	 */
	public function set_lpp_ready_for_calculation( $ready ) {

		// be sure we at least have a country selected + LPP active
		if ( function_exists( 'wc_local_pickup_plus' ) && WC()->customer->get_shipping_country() ) {

			$cart_items = WC()->session->get( 'wc_local_pickup_plus_cart_items', array() );

			if ( ! empty( $cart_items ) ) {

				// if any items are being picked up, force tax calculation
				foreach ( $cart_items as $item ) {
					if ( 'pickup' === $item['handling'] ) {
						$ready = true;
						break;
					}
				}
			}
		}

		return $ready;
	}


}

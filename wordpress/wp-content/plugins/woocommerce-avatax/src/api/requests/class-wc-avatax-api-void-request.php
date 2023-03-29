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
 * The AvaTax API void request class.
 *
 * @since 1.5.0
 */
class WC_AvaTax_API_Void_Request extends \WC_AvaTax_API_Company_Request {


	/**
	 * Voids a transaction in Avalara based on a WooCommerce order.
	 *
	 * @since 1.5.0
	 *
	 * @param int $order_id WoCommerce order ID
	 * @throws Framework\SV_WC_API_Exception for an invalid order key
	 */
	public function void_order( $order_id ) {

		$transaction_code = get_post_meta( $order_id, '_order_key', true );

		// If the order has no key, bail
		if ( ! $transaction_code ) {
			throw new Framework\SV_WC_API_Exception( __( 'Invalid order key.', 'woocommerce-avatax' ) );
		}

		$this->void_transaction( $transaction_code );
	}


	/**
	 * Voids a transaction in Avalara based on a WooCommerce refund.
	 *
	 * @since 1.5.0
	 *
	 * @param \WC_Order_Refund $refund order refund object
	 * @throws Framework\SV_WC_API_Exception for an invalid order key
	 */
	public function void_refund( WC_Order_Refund $refund ) {

		$order_key = get_post_meta( $refund->get_parent_id( 'edit' ), '_order_key', true );

		// If the order has no key, bail
		if ( ! $order_key ) {
			throw new Framework\SV_WC_API_Exception( __( 'Invalid order key.', 'woocommerce-avatax' ) );
		}

		$transaction_code = $order_key . '-' . $refund->get_id();

		$this->void_transaction( $transaction_code );
	}


	/**
	 * Void sa transaction in Avalara.
	 *
	 * @since 1.5.0
	 *
	 * @param string $code transaction code
	 */
	public function void_transaction( $code ) {

		$this->method = 'POST';
		$this->path   .= "/transactions/{$code}/void";
		$this->data   = array(
			'code' => 'DocVoided',
		);
	}


}

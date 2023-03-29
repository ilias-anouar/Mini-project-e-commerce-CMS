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
 * The base AvaTax API request class.
 *
 * @since 1.0.0
 */
class WC_AvaTax_API_Request extends Framework\SV_WC_API_JSON_Request {


	/**
	 * Prepare an address for the AvaTax API.
	 *
	 * Instead of keeping the input array keys 1-to-1 with the AvaTax API param
	 * keys, we map them to WooCommerce's standard address keys to make things
	 * easier on the WooCommerce side and avoid extra changes if the AvaTax API
	 * changes.
	 *
	 * @since 1.0.0
	 *
	 * @param array $address address data
	 *
	 * @return array prepared address
	 */
	protected function prepare_address( $address ) {

		// compat for varying address keys in WC core
		if ( ! isset( $address['address_1'] ) && isset( $address['address'] ) ) {
			$address['address_1'] = $address['address'];
		}

		$address = wp_parse_args( (array) $address, array(
			'address_1' => '',
			'address_2' => '',
			'city'      => '',
			'state'     => '',
			'country'   => '',
			'postcode'  => '',
		) );

		if ( ! empty( $address['postcode'] ) && ! empty( $address['country'] ) ) {
			$address['postcode'] = wc_format_postcode( $address['postcode'], $address['country'] );
		}

		$address = array(
			'line1'       => Framework\SV_WC_Helper::str_truncate( Framework\SV_WC_Helper::str_to_sane_utf8( $address['address_1'] ), 50 ),
			'line2'       => Framework\SV_WC_Helper::str_truncate( Framework\SV_WC_Helper::str_to_sane_utf8( $address['address_2'] ), 50 ),
			'city'        => Framework\SV_WC_Helper::str_truncate( Framework\SV_WC_Helper::str_to_sane_utf8( $address['city'] ), 50 ),
			'region'      => Framework\SV_WC_Helper::str_truncate( Framework\SV_WC_Helper::str_to_sane_utf8( $address['state'] ), 3, '' ),
			'country'     => Framework\SV_WC_Helper::str_truncate( Framework\SV_WC_Helper::str_to_sane_utf8( $address['country'] ), 2, '' ),
			'postalCode'  => Framework\SV_WC_Helper::str_truncate( Framework\SV_WC_Helper::str_to_sane_utf8( $address['postcode'] ), 11, '' ),
		);

		return $address;
	}


}

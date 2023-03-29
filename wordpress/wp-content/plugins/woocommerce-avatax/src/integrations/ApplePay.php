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

namespace SkyVerge\WooCommerce\AvaTax\Integrations;

defined( 'ABSPATH' ) or exit;

use SkyVerge\WooCommerce\PluginFramework\v5_10_14 as Framework;

/**
 * The Apple Pay integration handler.
 *
 * @since 1.12.0
 */
class ApplePay {


	/**
	 * Constructor.
	 *
	 * @since 1.12.0
	 */
	public function __construct() {

		add_filter( 'wc_avatax_cart_needs_calculation', [ $this, 'needs_full_tax_calculation' ] );
	}


	/**
	 * Determines whether full tax calculation is needed for the current request.
	 *
	 * Returns true if the request looks like an Apple Pay Ajax request.
	 *
	 * @internal
	 *
	 * @since 1.12.0
	 *
	 * @param bool $needs_calculation whether full tax calculation will be performed
	 *
	 * @return bool
	 */
	public function needs_full_tax_calculation( $needs_calculation ) {

		if ( wp_doing_ajax() && false !== strpos( Framework\SV_WC_Helper::get_requested_value( 'action' ), '_apple_pay_' ) ) {
			return true;
		}

		return $needs_calculation;
	}


}

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
 * The AvaTax API Entity/Use code response class.
 *
 * @since 1.6.2
 */
class WC_AvaTax_API_Entity_Use_Code_Response extends \WC_AvaTax_API_Response {


	/**
	 * Gets the enabled subscriptions.
	 *
	 * @since 1.6.2
	 *
	 * @return array
	 */
	public function get_codes() {

		$api_codes = $this->value;
		$codes     = array();

		if ( is_array( $api_codes ) ) {

			foreach ( $api_codes as $api_code ) {
				$codes[ $api_code->code ] = $api_code->name;
			}
		}

		return $codes;
	}


}

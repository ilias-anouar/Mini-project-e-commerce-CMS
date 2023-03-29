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
 * The AvaTax REST API subscriptions request class.
 *
 * This is used when testing for required account subscriptions, like Landed Cost.
 *
 * TODO: since 1.16.0 this class is no longer used, as there are no subscriptions to check. Consider removing {IT 2022-01-11}
 *
 * @since 1.5.0
 */
class WC_Avatax_API_Subscriptions_Request extends \WC_AvaTax_API_Request {


	/**
	 * Constructs the class.
	 *
	 * @since 1.5.0
	 */
	public function __construct( $account_id ) {

		$this->path   = "/accounts/{$account_id}/subscriptions";
		$this->method = 'GET';
	}


}

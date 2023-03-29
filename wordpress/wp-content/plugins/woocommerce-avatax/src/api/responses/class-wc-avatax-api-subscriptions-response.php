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
 * The AvaTax API subscriptions response class.
 *
 * TODO: since 1.16.0 this class is no longer used, as there are no subscriptions to check. Consider removing {IT 2022-01-11}
 *
 * @since 1.5.0
 */
class WC_AvaTax_API_Subscriptions_Response extends \WC_AvaTax_API_Response {


	/** Auto Address subscription name **/
	const TYPE_AUTO_ADDRESS = 'AutoAddress';

	/** AvaTax Standard subscription name **/
	const TYPE_AVATAX_ST = 'AvaTaxST';

	/** AvaTax Pro subscription name **/
	const TYPE_AVATAX_PRO = 'AvaTaxPro';

	/** AvaTax Global subscription name **/
	const TYPE_AVATAX_GLOBAL = 'AvaTaxGlobal';

	/** Landed Cost subscription name (used for cross-border duty calculations) **/
	const TYPE_LANDED_COST = 'AvaLandedCost';


	/**
	 * Gets the enabled subscriptions.
	 *
	 * @since 1.5.0
	 *
	 * @return array
	 */
	public function get_subscriptions() : array {

		return is_array( $this->value ) ? $this->value : [];
	}


	/**
	 * Determines if the account has the Landed Cost subscription.
	 *
	 * @since 1.5.0
	 *
	 * @return bool
	 */
	public function has_landed_cost() : bool {

		return $this->has_subscription( self::TYPE_LANDED_COST );
	}


	/**
	 * Determines if the account is eligible to sync HS codes through the Cross Border API.
	 *
	 * @since 1.13.0
	 *
	 * @deprecated 1.16.0 - this method was erroneously used to check for the Item Classification API subscription
	 *
	 * @return bool
	 */
	public function has_cross_border() : bool {

		wc_deprecated_function( __METHOD__, '1.16.0' );

		return false;
	}


	/**
	 * Determines if the account has the given subscription type.
	 *
	 * @since 1.5.0
	 *
	 * @param string $type subscription type
	 * @return bool
	 */
	public function has_subscription( string $type ) : bool {

		return in_array( $type, wp_list_pluck( $this->get_subscriptions(), 'subscriptionDescription' ), true );
	}


}

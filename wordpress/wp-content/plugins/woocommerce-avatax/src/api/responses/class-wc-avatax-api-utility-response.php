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
 * The AvaTax API utility response class.
 *
 * @since 1.5.0
 */
class WC_AvaTax_API_Utility_Response extends \WC_AvaTax_API_Response {


	/**
	 * Determines if the API is authenticated.
	 *
	 * @since 1.5.0
	 *
	 * @return bool
	 */
	public function is_authenticated() {

		return (bool) $this->authenticated;
	}


	/**
	 * Gets the connected API version.
	 *
	 * @since 1.5.0
	 *
	 * @return string
	 */
	public function get_version() {

		return $this->version;
	}


	/**
	 * Gets the connected API authentication type.
	 *
	 * @since 1.5.0
	 *
	 * @return string
	 */
	public function get_authentication_type() {

		return $this->authenticationType;
	}


	/**
	 * Gets the connected API username.
	 *
	 * @since 1.5.0
	 *
	 * @return string
	 */
	public function get_user_name() {

		return $this->authenticatedUserName;
	}


	/**
	 * Gets the connected API user ID.
	 *
	 * @since 1.5.0
	 *
	 * @return string
	 */
	public function get_user_id() {

		return $this->authenticatedUserId;
	}


	/**
	 * Gets the connected API account ID.
	 *
	 * @since 1.5.0
	 *
	 * @return string
	 */
	public function get_account_id() {

		return $this->authenticatedAccountId;
	}


}

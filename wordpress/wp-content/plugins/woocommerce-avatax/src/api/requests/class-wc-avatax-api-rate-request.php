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
 * The API rate request class.
 *
 * @since 1.5.0
 */
class WC_Avatax_API_Rate_Request extends \WC_AvaTax_API_Request {


	use Framework\API\Traits\Cacheable_Request_Trait;


	/**
	 * Sets the address query params.
	 *
	 * @see \WC_AvaTax_API_Request::prepare_address()
	 *
	 * @since 1.5.0
	 *
	 * @param array $address address data, in the standard WooCommerce format
	 */
	public function set_address_data( $address ) {

		$this->path   = '/taxrates/byaddress';
		$this->method = 'GET';
		$this->params = $this->prepare_address( $address );
	}


}

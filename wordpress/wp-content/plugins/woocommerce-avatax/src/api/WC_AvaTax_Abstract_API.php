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

namespace SkyVerge\WooCommerce\AvaTax\Api;

defined( 'ABSPATH' ) or exit;

use SkyVerge\WooCommerce\PluginFramework\v5_10_14 as Framework;
use WC_AvaTax;

/**
 * The AvaTax abstract API.
 *
 * @since 1.13.0
 */
abstract class WC_AvaTax_Abstract_API extends Framework\API\Abstract_Cacheable_API_Base {


	/** AvaTax API version */
	const VERSION = 'v2';

	/** WC integration ID, sent with every API request */
	const INTEGRATION_ID = 'a0o33000004goTr';


	/**
	 * Construct the API.
	 *
	 * @since 1.13.0
	 */
	public function __construct() {

		$this->set_request_content_type_header( 'application/json' );
		$this->set_request_accept_header( 'application/json' );

		// set some Avalara-specific headers with every API request
		$this->set_request_headers( [
			'X-Avalara-Client' => sprintf('AvaTax For WooCommerce || %sv2;%s;;;;',  $this->get_plugin()::VERSION,$this->get_plugin()::CLIENT_STRING), // this is formatted specifically for their audit system - do not change unless requested
			'X-Avalara-UID'    => static::INTEGRATION_ID,
		] );
	}


	/**
	 * Return the plugin class instance associated with this API.
	 *
	 * @since 1.13.0
	 * @return WC_AvaTax
	 */
	protected function get_plugin() {

		return wc_avatax();
	}


}

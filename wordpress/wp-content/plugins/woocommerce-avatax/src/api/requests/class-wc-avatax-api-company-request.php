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
 * The AvaTax API company request class.
 *
 * @since 1.0.0
 */
class WC_AvaTax_API_Company_Request extends WC_AvaTax_API_Request {


	/** @var string Avalara company code */
	protected $company_code;


	/**
	 * Constructs the class.
	 *
	 * @since 1.5.0
	 *
	 * @param string $company_code company code
	 */
	public function __construct( $company_code ) {

		$this->company_code = $company_code;

		$this->path = "/companies/{$this->company_code}";
	}


	/**
	 * Gets the company code for this request.
	 *
	 * @since 1.5.0
	 *
	 * @return string company code
	 */
	public function get_company_code() {

		return $this->company_code;
	}


}

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

namespace SkyVerge\WooCommerce\AvaTax\API\Requests;

use WC_AvaTax_API_Request;

defined( 'ABSPATH' ) or exit;

/**
 * The product classification systems by company request class.
 *
 * @see https://developer.avalara.com/api-reference/avatax/rest/v2/methods/Definitions/ListProductClassificationSystemsByCompany/
 *
 * @since 1.16.0
 */
class Product_Classification_Systems_List_By_Company_Request extends WC_AvaTax_API_Request {


	/**
	 * Product classification systems list by company request constructor
	 *
	 * @since 1.16.0
	 *
	 * @param string $company_code company code for this request
	 */
	public function __construct( string $company_code ) {

		$this->path   = '/definitions/productclassificationsystems/bycompany/' . $company_code;
		$this->method = 'GET';
	}


}

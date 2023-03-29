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

namespace SkyVerge\WooCommerce\AvaTax\API\Responses;

use WC_AvaTax_API_Response;

defined( 'ABSPATH' ) or exit;

/**
 * The AvaTax API companies response class.
 *
 * @since 1.13.0
 */
class Companies_Response extends WC_AvaTax_API_Response {


	/**
	 * Gets the company ID from the companies' response body.
	 *
	 * @since 1.13.0
	 *
	 * @param string $company_code
	 * @return string|null
	 */
	public function get_company_id( string $company_code = '' ) {

		$company_id = $this->value[0]->id ?? null;

		if ( ! $company_id ) {
			return null;
		}

		// try to match the company ID with the one saved in settings, or fallback to the first one from the response
		if ( empty( $company_code ) ) {
			$company_code = get_option( 'wc_avatax_company_code' );
		}

		foreach ( $this->value ?? [] as $company ) {
			if ( isset( $company->id, $company->companyCode ) && $company_code === $company->companyCode ) {
				return (string) $company->id;
			}
		}

		return (string) $company_id;
	}


}

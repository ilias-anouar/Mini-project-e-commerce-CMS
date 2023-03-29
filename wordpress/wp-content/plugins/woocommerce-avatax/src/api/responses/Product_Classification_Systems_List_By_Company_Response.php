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

use DateTime;
use Exception;
use WC_AvaTax_API_Response;

defined( 'ABSPATH' ) or exit;

/**
 * The product classification systems by company response class.
 *
 * @since 1.16.0
 */
class Product_Classification_Systems_List_By_Company_Response extends WC_AvaTax_API_Response {


	/**
	 * Gets all the current supported product classification systems.
	 *
	 * This excludes any historical systems.
	 *
	 * @since 1.16.0
	 *
	 * @return array
	 *
	 * @throws Exception
	 */
	public function get_current_system_list() : array {

		$systems = [];

		foreach( $this->response_data->value ?? [] as $system ) {

			$responseObject = $this;

			// array_values will reset the array keys, array_filter will leave out any empty values
			$countries = array_values( array_filter( array_map( static function ( $entry ) use ( $responseObject ) {
				// only return countries that are currently using this system
				return $responseObject->should_return_country( $entry ) ? $entry->country : null;
			}, $system->countries ) ) );

			if ( ! empty( $countries ) ) {
				$systems[ $system->systemCode ] = $countries;
			}

		}

		return $systems;
	}

	/**
	 * Determines whether the country should be returned or not based on the system end date.
	 *
	 * @since 1.16.5
	 *
	 * @param $entry
	 *
	 * @return bool
	 *
	 * @throws Exception
	 */
	protected function should_return_country( $entry ) : bool {

		$effectiveTimestamp = empty( $entry->effDate ) ? 0 : (new DateTime( $entry->effDate ))->getTimestamp();
		$endTimestamp       = empty( $entry->endDate ) ? PHP_INT_MAX : (new DateTime( $entry->endDate ))->getTimestamp();
		$currentTimestamp   = (new DateTime( 'now' ))->getTimestamp();

		return $currentTimestamp >= $effectiveTimestamp && $currentTimestamp <= $endTimestamp;
	}


}

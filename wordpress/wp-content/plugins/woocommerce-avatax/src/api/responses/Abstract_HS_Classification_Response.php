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

defined( 'ABSPATH' ) or exit;

use WC_AvaTax_API_Response;

/**
 * The AvaTax API HS Classification response class.
 *
 * @since 1.13.0
 */
abstract class Abstract_HS_Classification_Response extends WC_AvaTax_API_Response {


	/** @var string pending classification status */
	const CLASSIFICATION_STATUS_PENDING = 'pending';

	/** @var string classified classification status */
	const CLASSIFICATION_STATUS_CLASSIFIED = 'classified';

	/** @var string classification unavailable */
	const CLASSIFICATION_STATUS_UNAVAILABLE = 'cannot_be_classified';


	/**
	 * Gets the HS classification ID, if available.
	 *
	 * @since 1.13.0
	 *
	 * @return string|null
	 */
	public function get_hs_classification_id() {

		return $this->response_data->id ?? null;
	}


	/**
	 * Gets the country of destination, if available.
	 *
	 * @since 1.13.0
	 *
	 * @return string|null
	 */
	public function get_country_of_destination() {

		return $this->response_data->countryOfDestination ?? null;
	}


	/**
	 * Gets the HS code, depending on classification status.
	 *
	 * @since 1.13.0
	 *
	 * @return string|null
	 */
	public function get_hs_code() {

		return $this->response_data->hsCode ?? null;
	}


	/**
	 * Gets the classification status.
	 *
	 * @since 1.13.0
	 *
	 * @return string
	 */
	public function get_status() : string {

		return strtolower( $this->response_data->status ?? '' );
	}


	/**
	 * Determines whether the classification is pending.
	 *
	 * @since 1.13.0
	 *
	 * @return bool
	 */
	public function is_pending() : bool {

		return self::CLASSIFICATION_STATUS_PENDING === $this->get_status();
	}


	/**
	 * Determines whether the classification is done.
	 *
	 * @since 1.13.0
	 *
	 * @return bool
	 */
	public function is_classified() : bool {

		return self::CLASSIFICATION_STATUS_CLASSIFIED === $this->get_status();
	}


	/**
	 * Determines whether the classification cannot be performed.
	 *
	 * @since 1.13.0
	 *
	 * @return bool
	 */
	public function cannot_be_classified() : bool {

		return self::CLASSIFICATION_STATUS_UNAVAILABLE === $this->get_status();
	}


	/**
	 * Gets the detailed description of why the item cannot be classified.
	 *
	 * @since 1.13.0
	 *
	 * @return string
	 */
	public function get_resolution() : string {

		return $this->response_data->resolution ?? '';
	}


}

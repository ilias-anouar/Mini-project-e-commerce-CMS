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

defined( 'ABSPATH' ) or exit;

use SkyVerge\WooCommerce\AvaTax\API\Models\HS_Classification_Model;
use WC_AvaTax_API_Request;

/**
 * The AvaTax HS Classification API request class.
 *
 * @since 1.13.0
 */
abstract class Abstract_HS_Classification_Request extends WC_AvaTax_API_Request {


	/** @var HS_Classification_Model */
	protected $classification;


	/**
	 * Initializes the request params.
	 *
	 * @since 1.13.0
	 *
	 * @param HS_Classification_Model $classification
	 */
	public function __construct( HS_Classification_Model $classification ) {

		$this->classification = $classification;
		$this->path = '/companies/' . wc_avatax()->get_company_id() . '/classifications/hs';
	}


	/**
	 * Gets the classification for the request.
	 *
	 * @since 1.13.0
	 *
	 * @return HS_Classification_Model
	 */
	protected function get_classification() : HS_Classification_Model {

		return $this->classification;
	}


	/**
	 * Builds params from a classification model.
	 *
	 * @since 1.13.0
	 *
	 * @return array
	 */
	protected function get_classification_params() : array {

		$params = [];

		if ( $item = $this->classification->get_item() ) {

			$params = [
				'countryOfDestination' => $this->classification->get_country_of_destination(),
				'item'                 => [
					'companyId'                => $item->get_company_id(),
					'itemCode'                 => $item->get_item_code(),
					'summary'                  => $item->get_summary(),
					'description'              => $item->get_description(),
					'itemGroup'                => $item->get_item_group(),
					'classificationParameters' => $item->get_classification_parameters(),
				],
			];

			if ( $parentCode = $item->get_parent_code() ) {
				$params['item']['parentCode'] = $parentCode;
			}
		}

		return $params;
	}


}

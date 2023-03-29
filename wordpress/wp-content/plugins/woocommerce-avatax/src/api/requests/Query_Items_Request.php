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
 * The Query Items Request
 *
 * @see https://developer.avalara.com/api-reference/avatax/rest/v2/methods/Items/QueryItems/
 *
 * Note: this class does not currently support pagination params other than limit ($top), as they're not needed for
 * our planned use-cases.
 *
 * TODO: consider extracting the pagination and filtering methods to a trait if adding other similar request {IT 2021-12-22}
 *
 * @since 1.16.0
 */
class Query_Items_Request extends WC_AvaTax_API_Request {


	/**
	 * Query items request constructor.
	 *
	 * @since 1.16.0
	 */
	public function __construct() {

		$this->path   = '/companies/' . wc_avatax()->get_company_id() . '/items';
		$this->method = 'GET';
	}


	/**
	 * Set the request's filter parameter.
	 *
	 * @since 1.16.0
	 *
	 * @param string $filter
	 * @return $this
	 */
	public function filter( string $filter ) : Query_Items_Request {

		$this->params['$filter'] = $filter;

		return $this;
	}


	/**
	 * Set the request's include parameter.
	 *
	 * @since 1.16.0
	 *
	 * @param string|array $include a list of params to include in the response
	 * @return $this
	 */
	public function include( $include ) : Query_Items_Request {

		$this->params['$include'] = is_array( $include ) ? implode( ',', $include ) : $include;

		return $this;
	}


	/**
	 * Set the request's top (limit) parameter.
	 *
	 * @since 1.16.0
	 *
	 * @param int $limit the number of items to include in a single page
	 * @return $this
	 */
	public function limit( int $limit ) : Query_Items_Request {

		$this->params['$top'] = $limit;

		return $this;
	}


}

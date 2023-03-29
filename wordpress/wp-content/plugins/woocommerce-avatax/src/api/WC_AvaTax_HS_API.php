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

use SkyVerge\WooCommerce\AvaTax\API\Models\HS_Classification_Model;
use SkyVerge\WooCommerce\AvaTax\API\Requests\Abstract_HS_Classification_Request;
use SkyVerge\WooCommerce\AvaTax\API\Requests\HS_Classification_Create_Request;
use SkyVerge\WooCommerce\AvaTax\API\Requests\HS_Classification_Get_Request;
use SkyVerge\WooCommerce\AvaTax\API\Requests\HS_Classification_Update_Request;
use SkyVerge\WooCommerce\AvaTax\API\Responses\Abstract_HS_Classification_Response;
use SkyVerge\WooCommerce\AvaTax\API\Responses\HS_Classification_Create_Response;
use SkyVerge\WooCommerce\AvaTax\API\Responses\HS_Classification_Get_Response;
use SkyVerge\WooCommerce\AvaTax\API\Responses\HS_Classification_Update_Response;
use SkyVerge\WooCommerce\PluginFramework\v5_10_14 as Framework;

/**
 * The AvaTax HS Classification API.
 *
 * @since 1.13.0
 */
class WC_AvaTax_HS_API extends WC_AvaTax_Abstract_API {


	/** @var string Avalara account username */
	protected $username;

	/** @var string Avalara account password */
	protected $password;


	/**
	 * Construct the API.
	 *
	 * @since 1.13.0
	 *
	 * @param string $username Avalara username
	 * @param string $password Avalara password
	 * @param string $environment The current API environment, either `production` or `development`.
	 */
	public function __construct(string $username, string $password, string $environment ) {

		$this->username = $username;
		$this->password = $password;

		$this->request_uri = ( 'production' === $environment ) ? 'https://api.classification.avalara.net/api/' : 'https://api-sandbox.classification.avalara.net/api/';
		$this->request_uri .= static::VERSION;

		// Set basic auth creds
		$this->set_http_basic_auth( $this->username, $this->password );

		parent::__construct();
	}


	/**
	 * Builds and returns a new API request object
	 *
	 * @see Framework\SV_WC_API_Base::get_new_request()
	 *
	 * @since 1.13.0
	 *
	 * @param string $type the desired request type
	 * @param mixed $args optional argument(s) to be passed to the request
	 * @return Abstract_HS_Classification_Request
	 * @throws Framework\SV_WC_API_Exception for invalid request types
	 */
	protected function get_new_request( $type = '', $args = null ) : Abstract_HS_Classification_Request {

		switch ( $type ) {

			case HS_Classification_Create_Request::class :
				$this->set_response_handler( HS_Classification_Create_Response::class );
				$request = new HS_Classification_Create_Request( $args );
				break;

			case HS_Classification_Get_Request::class :
				$this->set_response_handler( HS_Classification_Get_Response::class );
				$request = new HS_Classification_Get_Request( $args );
				break;

			default:
				throw new Framework\SV_WC_API_Exception( 'Invalid request type' );
		}

		return $request;
	}


	/**
	 * Gets an HS classification request.
	 *
	 * @since 1.13.0
	 *
	 * @param HS_Classification_Model $classification
	 * @param string $type
	 * @return HS_Classification_Create_Response|HS_Classification_Get_Response|HS_Classification_Update_Response
	 * @throws Framework\SV_WC_API_Exception
	 */
	private function get_hs_classification_request( HS_Classification_Model $classification, string $type ) : Abstract_HS_Classification_Response {

		if ( ! class_exists( $type, false ) || ! in_array( Abstract_HS_Classification_Request::class, class_parents( $type, false ), true ) ) {
			throw new Framework\SV_WC_API_Exception( sprintf( 'Invalid HS Classification request type %s', $type ) );
		}

		$request = $this->get_new_request( $type, $classification );

		return $this->perform_request( $request );
	}


	/**
	 * Creates or updates an HS Classification.
	 *
	 * @since 1.13.0
	 *
	 * @param HS_Classification_Model $classification
	 * @return HS_Classification_Create_Response
	 * @throws Framework\SV_WC_API_Exception
	 */
	public function create_hs_classification( HS_Classification_Model $classification ) : HS_Classification_Create_Response {

		return $this->get_hs_classification_request( $classification, HS_Classification_Create_Request::class );
	}


	/**
	 * Gets an HS Classification.
	 *
	 * @since 1.13.0
	 *
	 * @param HS_Classification_Model $classification
	 * @return HS_Classification_Get_Response
	 * @throws Framework\SV_WC_API_Exception
	 */
	public function get_hs_classification( HS_Classification_Model $classification ) : HS_Classification_Get_Response {

		return $this->get_hs_classification_request( $classification, HS_Classification_Get_Request::class );
	}


	/**
	 * Updates an HS Classification.
	 *
	 * @since 1.13.0
	 * @deprecated 1.16.0
	 *
	 * @param HS_Classification_Model $classification
	 * @return HS_Classification_Update_Response
	 * @throws Framework\SV_WC_API_Exception
	 */
	public function update_hs_classification( HS_Classification_Model $classification ) : HS_Classification_Update_Response {

		wc_deprecated_function( __METHOD__, '1.16.0', __CLASS__ . '::create_hs_classification()' );

		return $this->get_hs_classification_request( $classification, HS_Classification_Update_Request::class );
	}


}

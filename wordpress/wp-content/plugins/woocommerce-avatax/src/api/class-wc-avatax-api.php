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

use SkyVerge\WooCommerce\AvaTax\API\Requests\Companies_Request;
use SkyVerge\WooCommerce\AvaTax\API\Requests\Nexus_List_Request;
use SkyVerge\WooCommerce\AvaTax\API\Requests\Product_Classification_Systems_List_By_Company_Request;
use SkyVerge\WooCommerce\AvaTax\API\Requests\Query_Items_Request;
use SkyVerge\WooCommerce\AvaTax\API\Responses\Companies_Response;
use SkyVerge\WooCommerce\AvaTax\API\Responses\Nexus_List_Response;
use SkyVerge\WooCommerce\AvaTax\API\Responses\Product_Classification_Systems_List_By_Company_Response;
use SkyVerge\WooCommerce\AvaTax\API\Responses\Query_Items_Response;
use SkyVerge\WooCommerce\AvaTax\Api\WC_AvaTax_Abstract_API;
use SkyVerge\WooCommerce\PluginFramework\v5_10_14 as Framework;

defined( 'ABSPATH' ) or exit;

/**
 * The AvaTax API.
 *
 * @since 1.0.0
 */
class WC_AvaTax_API extends WC_AvaTax_Abstract_API {


	/** @var string Avalara account ID */
	protected $account_id;

	/** @var string Avalara license key */
	protected $license_key;

	/** @var string Avalara company code */
	protected $company_code;


	/**
	 * Construct the API.
	 *
	 * @since 1.0.0
	 *
	 * @param string $account_id Avalara account ID
	 * @param string $license_key Avalara license key
	 * @param string $company_code Avalara company code
	 * @param string $environment The current API environment, either `production` or `development`.
	 */
	public function __construct( $account_id, $license_key, $company_code, $environment ) {

		$this->account_id   = $account_id;
		$this->license_key  = $license_key;
		$this->company_code = $company_code;

		$this->request_uri = ( 'production' === $environment ) ? 'https://rest.avatax.com/api/' : 'https://sandbox-rest.avatax.com/api/';
		$this->request_uri .= static::VERSION;

		// Set basic auth creds
		$this->set_http_basic_auth( $this->account_id, $this->license_key );

		parent::__construct();
	}


	/**
	 * Gets the companies.
	 *
	 * @since 1.13.0
	 *
	 * @return Companies_Response
	 * @throws Framework\SV_WC_API_Exception
	 */
	public function get_companies() : Companies_Response {

		$request = $this->get_new_request( 'companies' );

		return $this->perform_request( $request );
	}


	/**
	 * Gets the Nexus list.
	 *
	 * @since 1.13.0
	 *
	 * @return Nexus_List_Response
	 * @throws Framework\SV_WC_API_Exception
	 */
	public function get_nexus_list() : Nexus_List_Response {

		$request = $this->get_new_request( 'nexus-list' );

		//The response handler is getting set to Companies_Response::class due to wc_avatax()->get_company_id() in Nexus_List_Request. Again setting it to Nexus_List_Response::class
		$this->set_response_handler( Nexus_List_Response::class );

		return $this->perform_request( $request );
	}


	/**
	 * Gets the product classification systems list.
	 *
	 * @since 1.16.0
	 *
	 * @return Product_Classification_Systems_List_By_Company_Response
	 * @throws Framework\SV_WC_API_Exception
	 */
	public function get_product_classification_systems_list() : Product_Classification_Systems_List_By_Company_Response {

		return $this->perform_request( $this->get_new_request( 'product-classification-systems-list' ) );
	}


	/**
	 * Queries the items list
	 *
	 * @since 1.16.0
	 *
	 * @param string $filter
	 * @param array|string $include
	 * @param null $limit
	 * @return Query_Items_Response
	 * @throws Framework\SV_WC_API_Exception
	 */
	public function query_items( string $filter = '', $include = '', $limit = null ) : Query_Items_Response {

		$request = $this->get_new_request( 'query-items' );
		$request->filter( $filter )->include( $include )->limit( $limit );

		return $this->perform_request( $request );
	}


	/**
	 * Gets estimated tax rates based on an address.
	 *
	 * @param array $address customer address
	 *
	 * @since 1.5.0
	 *
	 * @return WC_AvaTax_API_Rate_Response response object
	 * @throws Framework\SV_WC_API_Exception
	 */
	public function get_estimated_rates( $address ) {

		$request = $this->get_new_request( 'rate' );

		$request->set_address_data( $address );

		return $this->perform_request( $request );
	}


	/**
	 * Get the calculated tax for a cart instance.
	 *
	 * @since 1.5.0
	 *
	 * @param \WC_Cart $cart cart object
	 * @return WC_AvaTax_API_Tax_Response response object
	 * @throws Framework\SV_WC_API_Exception
	 */
	public function calculate_cart_tax( WC_Cart $cart ) {

		$request = $this->get_new_request( 'tax' );

		$request->process_cart( $cart );

		return $this->perform_request( $request );
	}


	/**
	 * Get the calculated tax for a specific order.
	 *
	 * @since 1.0.0
	 *
	 * @param \WC_Order $order order object
	 * @param bool $commit Whether to commit the transaction to Avalara
	 * @return WC_AvaTax_API_Tax_Response response object
	 * @throws Framework\SV_WC_API_Exception
	 */
	public function calculate_order_tax( WC_Order $order, bool $commit ) {

		$request = $this->get_new_request( 'tax' );

		$request->process_order( $order, $commit );

		return $this->perform_request( $request );
	}


	/**
	 * Get the calculated tax for a refunded order.
	 *
	 * @since 1.0.0
	 *
	 * @param WC_Order_Refund $refund order refund object
	 * @return WC_AvaTax_API_Tax_Response response object
	 * @throws Framework\SV_WC_API_Exception
	 */
	public function calculate_refund_tax( WC_Order_Refund $refund ) {

		$request = $this->get_new_request( 'tax' );

		$request->process_refund( $refund );

		return $this->perform_request( $request );
	}


	/**
	 * Refund an order.
	 *
	 * @since 1.15.0
	 *
	 * @param WC_Order_Refund $refund order refund object
	 * @param string|null $type the type of refund, leave empty for a Full refund
	 * @return WC_AvaTax_API_Response response object
	 * @throws Framework\SV_WC_API_Exception
	 */
	public function refund_order( WC_Order_Refund $refund, string $type = null ) {

		$request = $this->get_new_request( 'refund' );

		$request->process_refund( $refund, $type );

		return $this->perform_request( $request );
	}


	/**
	 * Validate an address.
	 *
	 * @since 1.0.0
	 *
	 * @param array $address {
	 *     The address details.
	 *
	 * @type string $address_1 Line 1 of the street address.
	 * @type string $address_2 Line 2 of the street address.
	 * @type string $city The city name.
	 * @type string $state The state or region.
	 * @type string $country The country code.
	 * @type string $postcode The zip or postcode.
	 * }
	 * @return object The validated and normalized address.
	 * @throws Framework\SV_WC_API_Exception
	 */
	public function validate_address( array $address ) {

		$request = $this->get_new_request( 'address' );

		$request->validate_address( $address );

		return $this->perform_request( $request );
	}


	/**
	 * Void a document in Avalara based on a WooCommerce order.
	 *
	 * @since 1.0.0
	 *
	 * @param int $order_id The associated order ID.
	 * @return WC_AvaTax_API_Tax_Response
	 * @throws Framework\SV_WC_API_Exception
	 */
	public function void_order( $order_id ) {

		$request = $this->get_new_request( 'void' );

		$request->void_order( $order_id );

		return $this->perform_request( $request );
	}


	/**
	 * Void a document in Avalara based on a WooCommerce refund.
	 *
	 * @since 1.0.0
	 *
	 * @param WC_Order_Refund $refund order refund object
	 * @return WC_AvaTax_API_Tax_Response
	 * @throws Framework\SV_WC_API_Exception
	 */
	public function void_refund( WC_Order_Refund $refund ) {

		$request = $this->get_new_request( 'void' );

		$request->void_refund( $refund );

		return $this->perform_request( $request );
	}


	/**
	 * Pings the AvaTax API.
	 *
	 * Primarily used to test for a valid connection.
	 *
	 * @since 1.0.0
	 *
	 * @return WC_AvaTax_API_Utility_Response
	 * @throws Framework\SV_WC_API_Exception
	 */
	public function test() {

		$request = $this->get_new_request( 'utility' );

		$request->set_ping_data();

		return $this->perform_request( $request );
	}


	/**
	 * Gets the configured account subscriptions.
	 *
	 * TODO: since 1.16.0 this method is no longer called, as there are no subscriptions to check. Consider removing {IT 2022-01-11}
	 *
	 * @since 1.5.0
	 *
	 * @return WC_AvaTax_API_Subscriptions_Response $response response object
	 * @throws Framework\SV_WC_API_Exception
	 */
	public function get_subscriptions() {

		$request = $this->get_new_request( 'subscriptions' );

		return $this->perform_request( $request );
	}


	/**
	 * Gets the available Entity/Use codes.
	 *
	 * @since 1.6.2
	 *
	 * @return WC_AvaTax_API_Entity_Use_Code_Response $response response object
	 * @throws Framework\SV_WC_API_Exception
	 */
	public function get_entity_use_codes() {

		$request = $this->get_new_request( 'entity-use-code' );

		return $this->perform_request( $request );
	}


	/**
	 * Allow child classes to validate a response prior to instantiating the
	 * response object. Useful for checking response codes or messages, e.g.
	 * throw an exception if the response code is not 200.
	 *
	 * A child class implementing this method should simply return true if the response
	 * processing should continue, or throw a Framework\SV_WC_API_Exception with a
	 * relevant error message & code to stop processing.
	 *
	 * Note: Child classes *must* sanitize the raw response body before throwing
	 * an exception, as it will be included in the broadcast_request() method
	 * which is typically used to log requests.
	 *
	 * @since 1.0.0
	 */
	protected function do_pre_parse_response_validation() {

		// TODO

		return true;
	}


	/**
	 * Validate the parsed response data.
	 *
	 * Primarily checks for errors returned by the AvaTax API.
	 *
	 * @since 1.5.0
	 *
	 * @throws Framework\SV_WC_API_Exception
	 * @return bool
	 */
	protected function do_post_parse_response_validation() {

		$response = $this->get_response();

		if ( $response->has_errors() ) {

			$messages = array();
			$errors   = $response->get_errors();

			foreach ( $errors->get_error_codes() as $code ) {
				$messages[] = '[' . $code . '] ' . $errors->get_error_message( $code );
			}

			$message = implode( ' ', $messages );

			throw new Framework\SV_WC_API_Exception( $message );
		}

		return true;
	}


	/**
	 * Builds and returns a new API request object
	 *
	 * @see Framework\SV_WC_API_Base::get_new_request()
	 *
	 * @since 1.0.0
	 *
	 * @param string $type the desired request type
	 * @param mixed $args optional argument(s) to be passed to the request
	 * @return Companies_Request|Nexus_List_Request|Product_Classification_Systems_List_By_Company_Request|Query_Items_Request|WC_AvaTax_API_Address_Request|WC_AvaTax_API_Entity_Use_Code_Request|WC_Avatax_API_Rate_Request|WC_Avatax_API_Subscriptions_Request|WC_AvaTax_API_Tax_Request|WC_Avatax_API_Utility_Request|WC_AvaTax_API_Void_Request
	 * @throws Framework\SV_WC_API_Exception for invalid request types
	 */
	protected function get_new_request( $type = '', $args = null ) {

		switch ( $type ) {

			case 'companies' :
				$this->set_response_handler( Companies_Response::class );
				return new Companies_Request();

			case 'nexus-list' :
				$this->set_response_handler( Nexus_List_Response::class );
				return new Nexus_List_Request();

			case 'product-classification-systems-list' :
				$this->set_response_handler( Product_Classification_Systems_List_By_Company_Response::class );
				return new Product_Classification_Systems_List_By_Company_Request( $this->get_company_code() );

			case 'query-items' :
				$this->set_response_handler( Query_Items_Response::class );
				return new Query_Items_Request();

			case 'utility':
				$this->set_response_handler( 'WC_Avatax_API_Utility_Response' );
				return new WC_Avatax_API_Utility_Request();

			case 'subscriptions':
				$this->set_response_handler( 'WC_Avatax_API_Subscriptions_Response' );
				return new WC_Avatax_API_Subscriptions_Request( $this->account_id );

			case 'entity-use-code':
				$this->set_response_handler( 'WC_AvaTax_API_Entity_Use_Code_Response' );
				return new WC_AvaTax_API_Entity_Use_Code_Request();

			case 'rate':
				$this->set_response_handler( 'WC_AvaTax_API_Rate_Response' );
				return new WC_Avatax_API_Rate_Request();

			case 'tax':
				$this->set_response_handler( 'WC_AvaTax_API_Tax_Response' );
				return new WC_AvaTax_API_Tax_Request( $this->get_company_code() );

			case 'void':
				$this->set_response_handler( 'WC_AvaTax_API_Response' );
				return new WC_AvaTax_API_Void_Request( $this->get_company_code() );

			case 'address':
				$this->set_response_handler( 'WC_AvaTax_API_Address_Response' );
				return new WC_AvaTax_API_Address_Request();

			default:
				throw new Framework\SV_WC_API_Exception( 'Invalid request type' );
		}
	}


	/**
	 * Gets the configured company code.
	 *
	 * @since 1.5.0
	 *
	 * @return string
	 */
	public function get_company_code() {

		return $this->company_code;
	}


}

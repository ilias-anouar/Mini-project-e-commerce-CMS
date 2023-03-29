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
 * The AvaTax API request class.
 *
 * @since 1.0.0
 */
class WC_AvaTax_API_Response extends Framework\SV_WC_API_JSON_Response {


	/**
	 * Determines if the response contains errors.
	 *
	 * @since 1.5.0
	 * @return bool
	 */
	public function has_errors() {

		$errors = $this->get_errors()->get_error_codes();

		return ! empty( $errors );
	}


	/**
	 * Gets the errors, if any.
	 *
	 * @since 1.5.0
	 * @return \WP_Error|array
	 */
	public function get_errors() {

		$errors = new WP_Error();

		if ( ! empty( $this->response_data->error ) ) {

			$error = $this->response_data->error;

			foreach ( $error->details as $detail ) {

				$messages = array();

				if ( isset( $detail->message ) && $detail->message ) {
					$messages[] = $detail->message;
				}

				if ( isset( $detail->description ) && $detail->description ) {
					$messages[] = $detail->description;
				}

				if ( empty( $messages ) ) {
					continue;
				}

				$errors->add( $detail->number ?? '', implode( ' ', $messages ), $detail->code );
			}
		}

		if ( ! empty( $this->response_data->messages ) ) {

			foreach ( $this->response_data->messages as $message ) {

				if ( isset( $message->summary, $message->severity ) && 'error' === strtolower( trim( $message->severity ) ) ) {

					$errors->add( __( 'Error', 'woocommerce-avatax' ), $message->summary );
				}
			}
		}

		return $errors;
	}


	/**
	 * Gets the response error code, if  any.
	 *
	 * @since 1.16.0
	 *
	 * @return string
	 */
	public function get_error_code() : string {

		return $this->response_data->error->code ?? '';
	}


	/**
	 * Checks whether the response has an authentication or authorization error.
	 *
	 * AuthenticationIncomplete occurs with missing credentials in IC API calls.
	 * AuthenticationException occurs with missing / invalid credentials in REST API calls.
	 * AuthorizationException occurs with missing entitlement/subscription or sometimes, invalid credentials (for IC API calls)
	 *
	 * @see https://developer.avalara.com/avatax/errors/AuthenticationIncomplete/
	 * @see https://developer.avalara.com/avatax/errors/AuthenticationException/
	 * @see https://developer.avalara.com/avatax/errors/AuthorizationException/
	 *
	 * @since 1.16.0
	 *
	 * @return bool
	 */
	public function has_auth_error() : bool {

		return in_array( $this->get_error_code(), [
			'AuthorizationException',
			'AuthenticationException',
			'AuthenticationIncomplete',
		], true );
	}


}

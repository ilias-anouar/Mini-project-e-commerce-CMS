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
 * The AvaTax API rate response class.
 *
 * @since 1.5.0
 */
class WC_AvaTax_API_Rate_Response extends \WC_AvaTax_API_Response {


	/**
	 * Gets the total estimated tax rate.
	 *
	 * @since 1.5.0
	 *
	 * @return float
	 */
	public function get_total_rate() {

		return $this->totalRate;
	}


	/**
	 * Gets the individual estimated tax rates.
	 *
	 * @since 1.5.0
	 *
	 * @return \WC_AvaTax_API_Tax_Rate[]
	 */
	public function get_rates() {

		$api_tax_rates = [];

		if ( is_array( $this->rates ) ) {

			foreach ( $this->rates as $rate ) {

				$api_tax_rates[] = new WC_AvaTax_API_Tax_Rate( [
					'code' => $rate->name,
					'name' => $rate->type,
					'rate' => $rate->rate,
				] );
			}
		}

		return $this->ensure_unique_tax_rate_indexes( $api_tax_rates );
	}


	/**
	 * Modifies the given array of tax rates to ensure each entry gets a unique index.
	 *
	 * If two tax rates use EXAMPLE as their code, the rates will be added to the resulting
	 * array using EXAMPLE-1 and EXAMPLE-2 as the index.
	 *
	 * @since 1.12.0
	 *
	 * @param WC_AvaTax_API_Tax_Rate[] $rates original list of rates
	 *
	 * @return array
	 */
	protected function ensure_unique_tax_rate_indexes( $rates ) {

		$groups = [];

		// group tax rates by code
		foreach ( $rates as $rate ) {
			if ( isset( $groups[ $rate->get_code() ] ) ) {
				$groups[ $rate->get_code() ][] = $rate;
			} else {
				$groups[ $rate->get_code() ] = [ $rate ];
			}
		}

		$rates = [];

		// create a list of rates adding a numeric prefix to the index for rates that have the same code
		foreach ( $groups as $code => $group_rates ) {

			if ( 1 === count( $group_rates ) ) {

				$rates[ $code ] = reset( $group_rates );

			} else {

				foreach ( $group_rates as $index => $rate ) {
					$rates[ $code . '-' . ( $index + 1 ) ] = $rate;
				}
			}
		}

		return $rates;
	}


}

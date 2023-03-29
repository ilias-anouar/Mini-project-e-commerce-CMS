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
 * The AvaTax API tax response class.
 *
 * @since 1.0.0
 */
class WC_AvaTax_API_Tax_Response extends \WC_AvaTax_API_Response {


	/**
	 * Gets any cart item lines.
	 *
	 * This only returns products and no fees or shipping.
	 *
	 * @since 1.5.1
	 *
	 * @return array
	 */
	public function get_cart_lines() : array {

		$lines = $this->get_lines();

		foreach ( $lines as $key => $line ) {

			if ( Framework\SV_WC_Helper::str_starts_with( $line['id'], 'fee_' ) || Framework\SV_WC_Helper::str_starts_with( $line['id'], 'shipping_' ) ) {
				unset( $lines[ $key ] );
			}
		}

		return $lines;
	}


	/**
	 * Gets any fee lines.
	 *
	 * @since 1.5.0
	 *
	 * @return array
	 */
	public function get_fee_lines() : array {

		$lines = $this->get_lines();

		foreach ( $lines as $key => $line ) {

			if ( ! Framework\SV_WC_Helper::str_starts_with( $line['id'], 'fee_' ) && ! in_array( $line['id'], array( 'ImportDuties', 'ImportFees' ) ) ) {
				unset( $lines[ $key ] );
			} else {
				$lines[ $key ]['id'] = str_replace( 'fee_', '', $line['id'] );
			}
		}

		return $lines;
	}


	/**
	 * Gets any shipping lines.
	 *
	 * @since 1.5.0
	 *
	 * @return array
	 */
	public function get_shipping_lines() : array {

		$lines = $this->get_lines();

		foreach ( $lines as $key => $line ) {

			if ( Framework\SV_WC_Helper::str_starts_with( $line['id'], 'shipping_' ) ) {
				$lines[ $key ]['id'] = str_replace( 'shipping_', '', $line['id'] );
			} else {
				unset( $lines[ $key ] );
			}
		}

		return $lines;
	}


	/**
	 * Get the calculated line items.
	 *
	 * @since 1.0.0
	 * @return array The calculated line items
	 */
	public function get_lines() : array {

		$lines = array();

		$duplicate_tax_rate_codes = $this->get_duplicate_tax_rate_codes( $this->lines );

		foreach ( $this->lines as $line ) {

			$lines[] = array(
				'id'          => $line->lineNumber,
				'sku'         => $line->itemCode ?? '',
				'amount'      => $line->lineAmount,
				'tax'         => $line->tax,
				'code'        => $line->taxCode,
				'hsCode'      => $line->hsCode ?? '',
				'rates'       => $this->build_rates( $line->details, $duplicate_tax_rate_codes ),
				'origin'      => isset( $line->originAddressId ) ? $this->get_origin_address( $line ) : array(),
				'destination' => isset( $line->destinationAddressId ) ? $this->get_destination_address( $line ) : array(),
			);
		}

		return $lines;
	}


	/**
	 * Get the origin address.
	 *
	 * @since 1.0.0
	 * @return array The origin address
	 */
	public function get_origin_address( $line = null ) : array {

		$item           = $line ?: $this;
		$origin_address = array();

		// Get the origin address
		foreach ( $this->get_addresses() as $address ) {

			if ( $item->originAddressId === $address->id ) {

				// Map the API response to their proper keys
				$origin_address = array(
					'address_1' => $address->line1,
					'address_2' => $address->line2,
					'city'      => $address->city,
					'state'     => $address->region,
					'country'   => $address->country,
					'postcode'  => $address->postalCode,
				);

				break;
			}
		}

		return $origin_address;
	}


	/**
	 * Get the destination address.
	 *
	 * @since 1.1.0
	 * @return array The destination address
	 */
	public function get_destination_address( $line = null ) : array {

		$item                = $line ?: $this;
		$destination_address = array();

		// Get the destination address
		foreach ( $this->get_addresses() as $address ) {

			if ( $item->destinationAddressId === $address->id ) {

				// Map the API response to their proper keys
				$destination_address = array(
					'address_1' => $address->line1,
					'address_2' => $address->line2,
					'city'      => $address->city,
					'state'     => $address->region,
					'country'   => $address->country,
					'postcode'  => $address->postalCode,
				);

				break;
			}
		}

		return $destination_address;
	}


	/**
	 * Get the transaction addresses.
	 *
	 * @since 1.5.0
	 *
	 * @return array
	 */
	public function get_addresses() : array {

		return $this->addresses;
	}


	/**
	 * Get the total tax amount.
	 *
	 * @since 1.0.0
	 * @return float The total tax calculated.
	 */
	public function get_total_tax() : float {

		return $this->totalTax;
	}


	/**
	 * Determines if the line amounts included tax.
	 *
	 * @since 1.5.0
	 *
	 * @return bool
	 */
	public function is_tax_included() : bool {

		$tax_included = false;

		foreach ( $this->lines as $line ) {

			// if one is, they all are
			if ( $line->taxIncluded ) {
				$tax_included = true;
				break;
			}
		}

		return $tax_included;
	}


	/**
	 * Get the effective tax date.
	 *
	 * @since 1.0.0
	 * @return string The effective tax date in YYYY-MM-DD format.
	 */
	public function get_tax_date() : string {

		return date( 'Y-m-d', strtotime( $this->taxDate ) );
	}


	/**
	 * Gets all rates used to calculate tax for the transaction, for display purposes.
	 *
	 * Should not use the `summary` element of the response because it groups the rates differently,
	 * leading to missing codes when we customize the labels later.
	 *
	 * @see WC_AvaTax_Checkout_Handler::set_rate_labels()
	 *
	 * @since 1.5.0
	 *
	 * @return \WC_AvaTax_API_Tax_Rate[][] $rates rate objects per line
	 */
	public function get_rates() : array {

		return array_map( function( $line ) {
			return $line['rates'];
		}, $this->get_lines() );
	}


	/**
	 * Gets an array of tax rates codes that are duplicate in at least one line.
	 *
	 * @since 1.12.0
	 *
	 * @param array $lines tax lines data
	 *
	 * @return array
	 */
	protected function get_duplicate_tax_rate_codes( $lines ) : array {

		return array_reduce( $lines, function( $duplicate_tax_rate_codes, $line ) {

			return array_merge(
				$duplicate_tax_rate_codes,
				array_filter(
					array_reduce( $line->details, function ( $duplicate_line_tax_rate_codes, $rate ) {

						if ( isset( $duplicate_line_tax_rate_codes[ $rate->taxName ] ) ) {
							$duplicate_line_tax_rate_codes[ $rate->taxName ] = true;
						} else {
							$duplicate_line_tax_rate_codes[ $rate->taxName ] = false;
						}

						return $duplicate_line_tax_rate_codes;
					}, [] )
				)
			);
		}, [] );
	}


	/**
	 * Builds the array of rate objects based on the response data.
	 *
	 * @since 1.5.0
	 *
	 * @param array $raw_rates rate data from the AvaTax API
	 * @param array $duplicate_tax_rate_codes array of codes that appear more than once on a single line
	 *
	 * @return array $rates \WC_AvaTax_API_Tax_Rate rate objects
	 */
	protected function build_rates( $raw_rates, $duplicate_tax_rate_codes ) : array {

		$rates              = [];
		$landed_costs_rates = [];

		if ( is_array( $raw_rates ) ) {

			$count = 1;

			foreach ( $raw_rates as $raw_rate ) {

				// include the jurisCode field in the rate code to reduce the chances of getting a duplicate code
				$rate_code = isset( $duplicate_tax_rate_codes[ $raw_rate->taxName ] ) ? "{$raw_rate->taxName} {$raw_rate->jurisCode}" : $raw_rate->taxName;

				$rate = new WC_AvaTax_API_Tax_Rate( [
					'code'  => $rate_code,
					'name'  => $this->truncate_tax_rate_name( $this->get_tax_rate_name( $raw_rate ) ),
					'rate'  => $raw_rate->rate,
					'total' => $raw_rate->taxCalculated,
				] );

				if ( 'LandedCost' === $raw_rate->taxType ) {

					$landed_costs_rates[ $rate->get_code() ] = $rate;

				} elseif ( empty( $rates[ $rate->get_code() ] ) ) {

					$rates[ $rate->get_code() ] = $rate;

				} else {

					$rates[ $rate->get_code() . "_{$count}"  ] = $rate;
				}

				$count++;
			}

			// consolidate all landed cost rates into a single rate
			$consolidated_landed_cost_rate = $this->consolidate_landed_cost_rates( $landed_costs_rates );

			if ( ! empty( $consolidated_landed_cost_rate ) ) {

				//  add it to the beginning of the array because it needs to be displayed before other taxes
				$rates = [ $consolidated_landed_cost_rate->get_code() => $consolidated_landed_cost_rate ] + $rates;
			}
		}

		return $rates;
	}


	/**
	 * Gets the calculation response messages.
	 *
	 * These can be general tax calculation result messages (which we don't
	 * always need) or Landed Cost notes, like the duty to be paid when the
	 * customer is importer of record.
	 *
	 * @since 1.5.0
	 *
	 * @return array
	 */
	public function get_messages() : array {

		$messages = $this->messages;

		if ( ! is_array( $messages ) ) {
			$messages = array();
		}

		return $messages;
	}


	/**
	 * Consolidates all landed cost rates into a single rate.
	 *
	 * @since 1.10.0
	 *
	 * @param WC_AvaTax_API_Tax_Rate[] $landed_costs_rates landed costs rates
	 * @return WC_AvaTax_API_Tax_Rate|null
	 */
	protected function consolidate_landed_cost_rates( $landed_costs_rates ) {

		$consolidated_rate_object = null;
		$consolidated_code        = '';
		$consolidated_rate        = 0;
		$consolidated_total       = 0;

		foreach ( $landed_costs_rates as $landed_costs_rate ) {

			if ( ! empty( $consolidated_code ) ) {

				$consolidated_code .= str_replace( WC_AvaTax_Tax_Handler::RATE_PREFIX, '', $landed_costs_rate->get_code() );

			} else {

				$consolidated_code .= str_replace( WC_AvaTax_Tax_Handler::RATE_PREFIX . '-', '', $landed_costs_rate->get_code() );
			}

			$consolidated_rate  += $landed_costs_rate->get_rate();
			$consolidated_total += $landed_costs_rate->get_total();
		}

		if ( ! empty( $consolidated_total ) ) {

			$consolidated_rate_object = new WC_AvaTax_API_Tax_Rate( [
				'code'  => $consolidated_code,
				'name'  => 'LandedCost',
				'rate'  => $consolidated_rate,
				'total' => $consolidated_total,
			] );
		}

		return $consolidated_rate_object;
	}


	/**
	 * Checks whether the rates include at least one landed cost.
	 *
	 * @since 1.10.0
	 *
	 * @return bool
	 */
	public function has_landed_costs() : bool {

		foreach ( $this->lines as $line ) {

			foreach ( $line->details as $raw_rate ) {

				if ( 'LandedCost' === $raw_rate->taxType ) {

					return true;
				}
			}
		}

		return false;
	}


	/**
	 * Gets the name for a tax rate.
	 *
	 * Avalara tax rates do not seem to follow a uniform naming convention. In order to determine a human-friendly tax
	 * rate name, it seems to make sense to combine the jurisdiction type and tax type for US-based taxes, and just use
	 * the raw tax name for any other countries.
	 *
	 * @since 1.16.0
	 *
	 * @param stdClass $rate the tax rate object from the response
	 * @return string
	 */
	protected function get_tax_rate_name( stdClass $rate ) : string {

		if ( 'LandedCost' === $rate->taxType ) {
			return $rate->taxType;
		}

		if ( 'US' === $rate->country ) {
			return trim( "{$rate->jurisdictionType} {$rate->taxType}" );
		}

		return $rate->taxName;
	}


	/**
	 * Truncates the tax rate name to full words between 30 and 35 characters.
	 *
	 * @since 1.16.0
	 *
	 * @param string $name raw tax rate name
	 * @return string
	 */
	protected function truncate_tax_rate_name( string $name ) : string {

		$max_length = 35;
		$min_length = 30;

		if ( strlen( $name ) > $max_length ) {

			$string         = wordwrap( $name, $max_length );
			$truncated_name = substr( $string, 0, strpos( $string, "\n" ) );

			if ( strlen( $truncated_name ) < $min_length ) {

				// too short, just truncate without caring about full words
				$truncated_name = substr( $name, 0, $max_length );
			}

			return $truncated_name;
		}

		return $name;
	}


}

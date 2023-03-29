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
 * AvaTax Import/Export Handler
 *
 * Adds support for:
 *
 * + Customer / Order CSV Export
 * + Customer / Order XML Export
 *
 * @since 1.3.0
 */
class WC_AvaTax_Import_Export_Handler {


	/**
	 * Setup class
	 *
	 * @since 1.3.0
	 */
	public function __construct() {

		// Customer / Order CSV Export legacy support
		if ( function_exists( 'wc_customer_order_csv_export' ) && version_compare( wc_customer_order_csv_export()->get_version(), '5.0.0', '<' ) ) {

			// column headers/data + line item data
			add_filter( 'wc_customer_order_csv_export_order_headers', array( $this, 'add_vat_id_to_csv_export_column_headers' ), 15, 2 );
			add_filter( 'wc_customer_order_csv_export_order_row',     array( $this, 'add_vat_id_to_csv_export_column_data' ), 10, 3 );

			// customer column headers / data
			add_filter( 'wc_customer_order_csv_export_customer_headers', array( $this, 'add_customer_tax_info_csv_export_column_headers' ), 15, 2 );
			add_filter( 'wc_customer_order_csv_export_customer_row',     array( $this, 'add_customer_tax_info_to_csv_export_data' ), 10, 3 );

			// custom format builder support, v4.0+
			add_filter( 'wc_customer_order_csv_export_format_column_data_options', array( $this, 'add_csv_export_custom_mapping_options' ), 10, 2 );

		// Customer / Order / Coupon Export v5+ CSV support
		} else {

			// CSV column headers/data + line item data
			add_filter( 'wc_customer_order_export_csv_order_headers', array( $this, 'add_vat_id_to_csv_export_column_headers' ), 15, 2 );
			add_filter( 'wc_customer_order_export_csv_order_row',     array( $this, 'add_vat_id_to_csv_export_column_data' ), 10, 3 );

			// CSV customer column headers / data
			add_filter( 'wc_customer_order_export_csv_customer_headers', array( $this, 'add_customer_tax_info_csv_export_column_headers' ), 15, 2 );
			add_filter( 'wc_customer_order_export_csv_customer_row',     array( $this, 'add_customer_tax_info_to_csv_export_data' ), 10, 3 );

			// CSV custom format builder
			add_filter( 'wc_customer_order_export_csv_format_data_sources', array( $this, 'add_csv_export_custom_mapping_options' ), 10, 2 );

			// XML order data
			add_filter( 'wc_customer_order_export_xml_order_data', array( $this, 'add_xml_export_order_vat_id' ), 10, 3 );

			// XML customer data
			add_filter( 'wc_customer_order_export_xml_customer_export_data', array( $this, 'add_xml_export_customer_tax_info' ), 10, 4 );

			// XML custom format builder
			add_filter( 'wc_customer_order_export_xml_format_data_sources', array( $this, 'add_xml_export_custom_mapping_options' ), 10, 2 );
		}

		// Customer / Order XML Export legacy support
		if ( function_exists( 'wc_customer_order_xml_export_suite' ) ) {

			if ( version_compare( wc_customer_order_xml_export_suite()->get_version(), '2.0.0', '<' ) ) {
				add_filter( 'wc_customer_order_xml_export_suite_order_export_order_list_format', array( $this, 'add_xml_export_order_vat_id_legacy' ), 10, 2 );
			} else {
				add_filter( 'wc_customer_order_xml_export_suite_order_data', array( $this, 'add_xml_export_order_vat_id_legacy' ), 10, 2 );
			}

			// customer data
			add_filter( 'wc_customer_order_xml_export_suite_customer_export_data', array( $this, 'add_xml_export_customer_tax_info_legacy' ), 10, 3 );

			// custom format builder support
			add_filter( 'wc_customer_order_xml_export_suite_format_field_data_options', array( $this, 'add_xml_export_custom_mapping_options' ), 10, 2 );
		}
	}


	/** Customer/Order CSV Export compat **************************************/


	/**
	 * Filters the custom format building options to allow adding AvaTax headers.
	 *
	 * @since 1.5.0
	 *
	 * @param string[] $options the custom format building options
	 * @param string $export_type the export type, 'customers' or 'orders'
	 * @return string[] updated custom format options
	 */
	public function add_csv_export_custom_mapping_options( $options, $export_type ) {

		if ( 'orders' === $export_type ) {

			$options[] = 'vat_id';

		} elseif ( 'customers' === $export_type ) {

			$options[] = 'vat_id';
			$options[] = 'tax_exemption';
		}

		return $options;
	}


	/**
	 * Determines the CSV Export format being used, compatible with v3 and v4.
	 *
	 * @since 1.5.0
	 *
	 * @param \WC_Customer_Order_CSV_Export_Generator $csv_generator instance
	 * @param string $export_type the export type, 'orders' or 'customers'
	 * @return string export format
	 */
	private function get_csv_export_format( $csv_generator, $export_type ) {

		// sanity check - bail if CSV Export is not active, or if the provided parameter is not as expected
		if ( ! function_exists( 'wc_customer_order_csv_export' ) || ! $csv_generator instanceof WC_Customer_Order_CSV_Export_Generator ) {
			return '';
		}

		// earlier versions
		if ( version_compare( wc_customer_order_csv_export()->get_version(), '4.0.0', '<' ) ) {

			// customer exports had no format selector in v3
			$format = 'orders' === $export_type ? $csv_generator->order_format : '';

		// v4.0.0+
		} else {

			$format = $csv_generator->export_format;
		}

		return $format;
	}


	/** Customer/Order CSV Export - orders **************************************/


	/**
	 * Adds support for Customer/Order CSV Export by adding a
	 * `vat_id` column header.
	 *
	 * @since 1.3.0
	 *
	 * @param array $headers existing array of header key/names for the CSV export
	 * @param \WC_Customer_Order_CSV_Export_Generator $csv_generator instance
	 * @return array
	 */
	public function add_vat_id_to_csv_export_column_headers( $headers, $csv_generator ) {

		if ( 'custom' === $this->get_csv_export_format( $csv_generator, 'orders' ) ) {
			return $headers;
		}

		$new_headers = array( 'vat_id' => 'vat_id' );

		if ( isset( $headers['billing_company'] ) ) {
			$headers = Framework\SV_WC_Helper::array_insert_after( $headers, 'billing_company', $new_headers );
		} else {
			$headers = array_merge( $headers, $new_headers );
		}

		return $headers;
	}


	/**
	 * Adds support for Customer/Order CSV Export by adding data for the
	 * `vat_id` column header.
	 *
	 * @since 1.3.0
	 *
	 * @param array $order_data generated order data matching the column keys in the header
	 * @param WC_Order $order order being exported
	 * @param \WC_Customer_Order_CSV_Export_Generator $csv_generator instance
	 * @return array
	 */
	public function add_vat_id_to_csv_export_column_data( $order_data, $order, $csv_generator ) {

		$vat_id = [ 'vat_id' => $order->get_meta( '_billing_wc_avatax_vat_id' ) ];

		$new_order_data = array();

		if ( $this->is_one_row_per_item( $csv_generator ) ) {

			foreach ( $order_data as $data ) {
				$new_order_data[] = array_merge( (array) $data, $vat_id );
			}

		} else {

			$new_order_data = array_merge( $order_data, $vat_id );
		}

		return $new_order_data;
	}


	/**
	 * Determine if the CSV Export format/format definition are set to export
	 * one row per item.
	 *
	 * @since 1.3.0
	 *
	 * @param \WC_Customer_Order_CSV_Export_Generator $csv_generator instance
	 * @return bool
	 */
	private function is_one_row_per_item( $csv_generator ) {

		// sanity check - bail if CSV Export is not active, or if the provided parameter is not as expected
		if ( ! function_exists( 'wc_customer_order_csv_export' ) || ! $csv_generator instanceof WC_Customer_Order_CSV_Export_Generator ) {
			return false;
		}

		// determine if the selected format is "one row per item"
		if ( version_compare( wc_customer_order_csv_export()->get_version(), '4.0.0', '<' ) ) {

			$one_row_per_item = ( 'default_one_row_per_item' === $csv_generator->order_format || 'legacy_one_row_per_item' === $csv_generator->order_format );

		// v4.0.0 - 4.0.2
		} elseif ( ! isset( $csv_generator->format_definition ) ) {

			// get the CSV Export format definition
			$format_definition = wc_customer_order_csv_export()->get_formats_instance()->get_format( $csv_generator->export_type, $csv_generator->export_format );

			$one_row_per_item = isset( $format_definition['row_type'] ) && 'item' === $format_definition['row_type'];

		// v4.0.3+
		} else {

			$one_row_per_item = 'item' === $csv_generator->format_definition['row_type'];
		}

		return $one_row_per_item;
	}


	/** Customer/Order CSV Export - customers ***********************************/


	/**
	 * Adds headers for VAT ID and tax exemption status to customer exports.
	 *
	 * @since 1.3.0
	 *
	 * @param array $headers column headers for the CSV file
	 * @param \WC_Customer_Order_CSV_Export_Generator $csv_generator instance
	 * @return array updated headers
	 */
	public function add_customer_tax_info_csv_export_column_headers( $headers, $csv_generator ) {

		if ( 'custom' === $this->get_csv_export_format( $csv_generator, 'customers' ) ) {
			return $headers;
		}

		$new_headers = array(
			'vat_id'        => 'vat_id',
			'tax_exemption' => 'tax_exemption',
		);

		if ( isset( $headers['billing_company'] ) ) {
			$headers = Framework\SV_WC_Helper::array_insert_after( $headers, 'billing_company', $new_headers );
		} else {
			$headers = array_merge( $headers, $new_headers );
		}

		return $headers;
	}


	/**
	 * Adds VAT ID and tax exemption status to customer exports.
	 *
	 * @since 1.3.0
	 *
	 * @param array $customer_data the customer data for the CSV file
	 * @param \WP_User $user the user object for the export
	 * @param int $order_id order ID for the customer, if available
	 * @return array updated customer data
	 */
	public function add_customer_tax_info_to_csv_export_data( $customer_data, $user, $order_id ) {

		// get VAT ID for guest users
		if ( is_numeric( $order_id ) ) {

			$order  = wc_get_order( $order_id );
			$vat_id = $order->get_meta( '_billing_wc_avatax_vat_id' );

		// get VAT ID for registered users
		} else {
			$vat_id = isset( $user->billing_wc_avatax_vat_id ) ? $user->billing_wc_avatax_vat_id : '';
		}

		$new_data = array(
			'vat_id'        => $vat_id,
			'tax_exemption' => isset( $user->wc_avatax_tax_exemption ) ? $user->wc_avatax_tax_exemption : '',
		);

		return array_merge( $customer_data, $new_data );
	}


	/** Customer/Order XML Export compat **************************************/


	/**
	 * Filters the custom format building options to allow adding AvaTax headers.
	 *
	 * @since 1.5.0
	 *
	 * @param string[] $options the custom format building options
	 * @param string $export_type the export type, 'customers' or 'orders'
	 * @return string[] updated custom format options
	 */
	public function add_xml_export_custom_mapping_options( $options, $export_type ) {

		if ( 'orders' === $export_type ) {

			$options[] = 'VATId';

		} elseif ( 'customers' === $export_type ) {

			$options[] = 'VATId';
			$options[] = 'TaxExemption';
		}

		return $options;
	}


	/** Customer/Order XML Export - orders **************************************/


	/**
	 * Add a VATId element to the order XML export file.
	 *
	 * @since 1.3.0
	 *
	 * @param array $data order data
	 * @param \WC_Order $order order instance
	 * @param \SkyVerge\WooCommerce\CSV_Export\XML_Export_Generator|null $generator export generator
	 * @return array
	 */
	public function add_xml_export_order_vat_id( $data, $order, $generator = null ) {

		// sanity check
		if ( ! is_array( $data ) || ! $order instanceof WC_Order ) {
			return $data;
		}

		$vat_id = $order->get_meta( '_billing_wc_avatax_vat_id' );

		// only add tax data to custom formats if set in the format builder
		if ( $generator && 'custom' === $generator->export_format ) {

			// the data here can use a renamed version of our AvaTax data, so we need to get format definition first to find out the new name
			$format_definition = $generator->format_definition;
			$vat_key           = isset( $format_definition['fields']['VATId'] ) ? $format_definition['fields']['VATId'] : null;

			if ( $vat_key && isset( $data[ $vat_key ] ) ) {
				$data[ $vat_key ] = $vat_id;
			}

		// otherwise, automatically add order tax data to the export file
		} else {

			$new_data = array(
				'VATId' => $vat_id,
			);

			if ( isset( $data['BillingPhone'] ) ) {
				$data = Framework\SV_WC_Helper::array_insert_after( $data, 'BillingPhone', $new_data );
			} else {
				$data = array_merge( $data, $new_data );
			}
		}

		return $data;
	}


	/**
	 * Add a VATId element to the order XML export file.
	 *
	 * TODO: remove when dropping support for XML Export Suite {CW 2020-01-02}
	 *
	 * @since 1.9.1
	 *
	 * @param array $data order data
	 * @param \WC_Order $order order instance
	 * @return array
	 */
	public function add_xml_export_order_vat_id_legacy( $data, $order ) {

		// sanity check
		if ( ! is_array( $data ) || ! $order instanceof WC_Order ) {
			return $data;
		}

		$vat_id = $order->get_meta( '_billing_wc_avatax_vat_id' );

		// only add tax data to custom formats if set in the format builder with v2.0+
		if ( 'custom' === get_option( 'wc_customer_order_xml_export_suite_orders_format', 'default' ) ) {

			// the data here can use a renamed version of our AvaTax data, so we need to get format definition first to find out the new name
			$format_definition = wc_customer_order_xml_export_suite()->get_formats_instance()->get_format( 'orders', 'custom' );

			$vat_key = isset( $format_definition['fields']['VATId'] ) ? $format_definition['fields']['VATId'] : null;

			if ( $vat_key && isset( $data[ $vat_key ] ) ) {
				$data[ $vat_key ] = $vat_id;
			}

		// otherwise, automatically add order tax data to the export file
		} else {

			$new_data = array(
				'VATId' => $vat_id,
			);

			if ( isset( $data['BillingPhone'] ) ) {
				$data = Framework\SV_WC_Helper::array_insert_after( $data, 'BillingPhone', $new_data );
			} else {
				$data = array_merge( $data, $new_data );
			}
		}

		return $data;
	}


	/** Customer/Order XML Export - customers **************************************/


	/**
	 * Adds VATId and TaxExemption information to customer XML export file.
	 *
	 * @internal
	 *
	 * @since 1.3.0
	 *
	 * @param array $customer_data customer data in the format for array_to_xml()
	 * @param \WP_User $user user object
	 * @param int|null $order_id order ID for the customer, if available
	 * @param \SkyVerge\WooCommerce\CSV_Export\XML_Export_Generator|null $generator export generator
	 * @return array updated customer data
	 */
	public function add_xml_export_customer_tax_info( $customer_data, $user, $order_id, $generator = null ) {

		// get VAT ID for guest users
		if ( is_numeric( $order_id ) ) {

			$order  = wc_get_order( $order_id );
			$vat_id = $order->get_meta( '_billing_wc_avatax_vat_id' );

			// get VAT ID for registered users
		} else {

			$vat_id = isset( $user->billing_wc_avatax_vat_id ) ? $user->billing_wc_avatax_vat_id : '';
		}

		$tax_exemption = isset( $user->wc_avatax_tax_exemption ) ? $user->wc_avatax_tax_exemption : '';

		// only add tax data to custom formats if set in the format builder
		if ( $generator && 'custom' === $generator->export_format ) {

			// the data here can use a renamed version of our AvaTax data, so we need to get format definition first to find out the new name
			$format_definition = $generator->format_definition;

			$vat_key           = isset( $format_definition['fields']['VATId'] )        ? $format_definition['fields']['VATId']        : null;
			$tax_exemption_key = isset( $format_definition['fields']['TaxExemption'] ) ? $format_definition['fields']['TaxExemption'] : null;

			if ( $vat_key && isset( $customer_data[ $vat_key ] ) ) {
				$customer_data[ $vat_key ] = $vat_id;
			}

			if ( $tax_exemption_key && isset( $customer_data[ $tax_exemption_key ] ) ) {
				$customer_data[ $tax_exemption_key ] = $tax_exemption;
			}

			// otherwise, automatically add customer tax data to the export file
		} else {

			$new_data = array(
				'VATId'        => $vat_id,
				'TaxExemption' => $tax_exemption,
			);

			if ( isset( $customer_data['BillingCompany'] ) ) {
				$customer_data = Framework\SV_WC_Helper::array_insert_after( $customer_data, 'BillingCompany', $new_data );
			} else {
				$customer_data = array_merge( $customer_data, $new_data );
			}
		}

		return $customer_data;
	}


	/**
	 * Adds VATId and TaxExemption information to customer XML export file.
	 *
	 * TODO: remove when dropping support for XML Export Suite {CW 2020-01-02}
	 *
	 * @since 1.9.1
	 *
	 * @param array $customer_data customer data in the format for array_to_xml()
	 * @param \WP_User $user user object
	 * @param int|null $order_id order ID for the customer, if available
	 * @return array updated customer data
	 */
	public function add_xml_export_customer_tax_info_legacy( $customer_data, $user, $order_id ) {

		// get VAT ID for guest users
		if ( is_numeric( $order_id ) ) {

			$order  = wc_get_order( $order_id );
			$vat_id = $order->get_meta( '_billing_wc_avatax_vat_id' );

		// get VAT ID for registered users
		} else {

			$vat_id = isset( $user->billing_wc_avatax_vat_id ) ? $user->billing_wc_avatax_vat_id : '';
		}

		$tax_exemption = isset( $user->wc_avatax_tax_exemption ) ? $user->wc_avatax_tax_exemption : '';

		// only add tax data to custom formats if set in the format builder with v2.0+
		if ( 'custom' === get_option( 'wc_customer_order_xml_export_suite_customers_format', 'default' ) ) {

			// the data here can use a renamed version of our AvaTax data, so we need to get format definition first to find out the new name
			$format_definition = wc_customer_order_xml_export_suite()->get_formats_instance()->get_format( 'customers', 'custom' );

			$vat_key           = isset( $format_definition['fields']['VATId'] )        ? $format_definition['fields']['VATId']        : null;
			$tax_exemption_key = isset( $format_definition['fields']['TaxExemption'] ) ? $format_definition['fields']['TaxExemption'] : null;

			if ( $vat_key && isset( $customer_data[ $vat_key ] ) ) {
				$customer_data[ $vat_key ] = $vat_id;
			}

			if ( $tax_exemption_key && isset( $customer_data[ $tax_exemption_key ] ) ) {
				$customer_data[ $tax_exemption_key ] = $tax_exemption;
			}

		// otherwise, automatically add customer tax data to the export file
		} else {

			$new_data = array(
				'VATId'        => $vat_id,
				'TaxExemption' => $tax_exemption,
			);

			if ( isset( $customer_data['BillingCompany'] ) ) {
				$customer_data = Framework\SV_WC_Helper::array_insert_after( $customer_data, 'BillingCompany', $new_data );
			} else {
				$customer_data = array_merge( $customer_data, $new_data );
			}
		}

		return $customer_data;
	}


}

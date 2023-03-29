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

use SkyVerge\WooCommerce\AvaTax\Landed_Cost_Sync_Handler;
use SkyVerge\WooCommerce\PluginFramework\v5_10_14 as Framework;

defined( 'ABSPATH' ) or exit;

/**
 * Set up the admin settings.
 *
 * @since 1.0.0
 */
class WC_AvaTax_Settings {


	/** @var string $id The settings page ID */
	protected $id = 'avatax';


	/**
	 * Constructs the class.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		$this->add_hooks();
		$this->cache_company_id();
	}


	/**
	 * Adds action and filter hooks.
	 *
	 * @since 1.13.0
	 */
	private function add_hooks() {

		// add the settings section to the WooCommerce Tax tab
		add_filter( 'woocommerce_get_sections_tax', [ $this, 'add_settings_section' ] );

		// output the settings
		add_action( 'woocommerce_get_settings_tax', [ $this, 'add_settings' ] );

		// display a custom address settings field
		add_action( 'woocommerce_admin_field_wc_avatax_address', [ $this, 'display_address_fields' ] );

		// display the product sync fields
		add_action( 'woocommerce_admin_field_wc_avatax_product_sync', [ $this, 'display_product_sync_field' ] );

		// save the settings
		add_action( 'woocommerce_settings_save_tax', [ $this, 'save_settings' ] );

		// clears the company ID cache when the company code changes
		add_action( 'update_option_wc_avatax_company_code', [ $this, 'prune_company_id_cache' ], 10, 2 );

		// trigger a new sync when the cross-border countries list updates
		add_action( 'update_option_wc_avatax_api_product_countries_sync', [ $this, 'handle_countries_sync_update' ], 10, 2 );
	}


	/**
	 * Add the AvaTax section to the Tax tab.
	 *
	 * @since 1.0.0
	 * @param array $sections The existing Tax sections.
	 * @return array $sections The new Tax sections.
	 */
	public function add_settings_section( $sections ) {

		$sections[ $this->id ] = __( 'AvaTax', 'woocommerce-avatax' );

		return $sections;
	}


	/**
	 * Get the API settings.
	 *
	 * @since 1.0.0
	 * @return array $settings The API settings.
	 */
	public function get_api_settings() {

		$connection_status = get_transient( 'wc_avatax_connection_status' );

		$settings = array(

			array(
				'name' => __( 'Connection Settings', 'woocommerce-avatax' ),
				'type' => 'title',
				'desc' => sprintf(
					/* translators: Placeholders: %1$s - <a> tag, %2$s - </a> tag */
					__( 'Log in to your AvaTax %1$sAdmin Console%2$s to find your connection information.', 'woocommerce-avatax' ),
					'<a href="https://admin-avatax.avalara.net" target="_blank">',
					'</a>'
				),
			),

			array(
				'id'                => 'wc_avatax_api_account_number',
				'name'              => __( 'Account Number', 'woocommerce-avatax' ),
				'type'              => 'text',
				'class'             => 'wc-avatax-connection-field',
				'css'               => 'min-width:300px;',
				'custom_attributes' => array(
					'data-wc-avatax-connection-status' => $connection_status,
				),
			),

			array(
				'id'                => 'wc_avatax_api_license_key',
				'name'              => __( 'License Key', 'woocommerce-avatax' ),
				'type'              => 'text',
				'class'             => 'wc-avatax-connection-field',
				'css'               => 'min-width:300px;',
				'custom_attributes' => array(
					'data-wc-avatax-connection-status' => $connection_status,
				),
			),

			array(
				'id'      => 'wc_avatax_api_environment',
				'name'    => __( 'Environment', 'woocommerce-avatax' ),
				'options' => array(
					'production'  => __( 'Production', 'woocommerce-avatax' ),
					'development' => __( 'Development', 'woocommerce-avatax' ),
				),
				'default' => 'production',
				'type'    => 'select',
			),

			array(
				'type' => 'sectionend',
			),
		);

		/**
		 * Filter the API settings.
		 *
		 * @since 1.0.0
		 * @param array $settings The API settings.
		 */
		return (array) apply_filters( 'woocommerce_get_settings_' . $this->id . '_api', $settings );
	}


	/**
	 * Gets the tax calculation settings.
	 *
	 * @since 1.0.0
	 *
	 * @return array $settings the tax calculation settings
	 */
	public function get_tax_settings() {

		$settings = [

			[
				'name' => __( 'Tax Calculation', 'woocommerce-avatax' ),
				'type' => 'title',
			],

			[
				'id'       => 'wc_avatax_enable_tax_calculation',
				'name'     => __( 'Enable/Disable', 'woocommerce-avatax' ),
				'desc'     => __( 'Enable AvaTax tax calculation', 'woocommerce-avatax' ),
				'desc_tip' => __( 'This will override all configured WooCommerce tax rates and replace them with the rates fetched from AvaTax.', 'woocommerce-avatax' ),
				'default'  => 'no',
				'type'     => 'checkbox',
			],

			[
				'id'       => 'wc_avatax_record_calculations',
				'title'    => __( 'Submit to Avalara', 'woocommerce-avatax' ),
				'desc'     => __( 'Submit transactions to your Avalara account when orders are placed', 'woocommerce-avatax' ),
				'desc_tip' => __( '<b>Enabled:</b> Transactions are automatically submitted to Avalara when the order status is payment received,  processing, or complete. <br /> <b>Disabled:</b> Taxes are calculated at checkout, but transactions are not submitted to Avalara automatically. You need to submit transactions to Avalara manually.', 'woocommerce-avatax' ),
				'type'     => 'checkbox',
				'default'  => 'yes',
			],

			[
				'id'      => 'wc_avatax_commit',
				'name'    => __( 'Commit Transactions', 'woocommerce-avatax' ),
				'desc'    => __( 'Set transactions as "committed" when sending to Avalara', 'woocommerce-avatax' ),
				'default' => 'yes',
				'type'    => 'checkbox',
			],

			[
				'id'       => 'wc_avatax_tax_locations',
				'name'     => __( 'Supported Locations', 'woocommerce' ),
				'type'     => 'select',
				'desc_tip' => __( 'Limit tax calculation and only send documents to Avalara for specific countries and jurisdictions.', 'woocommerce-avatax' ),
				'default'  => 'all',
				'class'    => 'wc-enhanced-select',
				'css'      => 'min-width: 350px;',
				'options'  => [
					'all'      => __( 'All locations', 'woocommerce' ),
					'specific' => __( 'Specific locations only', 'woocommerce' )
				]
			],

			[
				'id'      => 'wc_avatax_specific_tax_locations',
				'name'    => __( 'Specific Locations', 'woocommerce-avatax' ),
				'type'    => 'multiselect',
				'class'   => 'wc-enhanced-select',
				'options' => $this->get_plugin()->get_tax_handler()->get_available_locations(),
			],

			[
				'id'       => 'wc_avatax_company_code',
				'name'     => __( 'Company Code', 'woocommerce-avatax' ),
				'desc'     => __( 'Recommended. Company Code is required to automatically post voids and refunds to Avalara.', 'woocommerce-avatax' ),
				'desc_tip' => __( 'The company code in your AvaTax account in which documents should be posted.', 'woocommerce-avatax' ),
				'type'     => 'text',
			],

			[
				'id'       => 'wc_avatax_origin_address',
				'name'     => __( 'Origin Address', 'woocommerce-avatax' ),
				'desc_tip' => __( 'Your warehouse or base of operations. This address will be used for tax calculation.', 'woocommerce-avatax' ),
				'type'     => 'wc_avatax_address',
				'default'  => [
					'country'   => WC()->countries->get_base_country(),
					'address_1' => WC()->countries->get_base_address(),
					'address_2' => WC()->countries->get_base_address_2(),
					'city'      => WC()->countries->get_base_city(),
					'postcode'  => WC()->countries->get_base_postcode(),
					'state'     => WC()->countries->get_base_state(),
				],
			],

			[
				'id'       => 'wc_avatax_default_product_code',
				'name'     => __( 'Default Product Tax Code', 'woocommerce-avatax' ),
				'desc_tip' => __( 'You can set a specific tax code for each product or product category.', 'woocommerce-avatax' ),

				/* translators: Placeholders: %1$s - <a> tag, %2$s - </a> tag */
				'desc'     => sprintf( __( '%1$sLearn more%2$s', 'woocommerce-avatax' ),
					'<a href="https://help.avalara.com/Avalara_AvaTax_Update/Create_and_update_custom_tax_codes" target="_blank">',
					'</a>'
				),
				'type'     => 'text',
				'default'  => 'P0000000',
			],

			[
				'id'      => 'wc_avatax_shipping_code',
				'name'    => __( 'Default Shipping Tax Code', 'woocommerce-avatax' ),
				'type'    => 'text',
				'default' => 'FR',
			],

			[
				'id'      => 'wc_avatax_calculate_on_cart',
				'title'   => __( 'Cart Calculation', 'woocommerce-avatax' ),
				'desc'    => __( '<b>Do not show calculations on the cart page: </b> The cart does not calculate the estimated taxes. The actual taxes are calculated at checkout.<br /> <b>Show estimated tax rates: </b> The cart calculates the tax estimates based on the given shipping address. The actual taxes are calculated at checkout.<br /> <b>Force full tax rate calculation: </b> The actual taxes are calculated in the cart and at checkout.', 'woocommerce-avatax' ),
				'type'    => 'select',
				'class'   => 'wc-enhanced-select',
				'css'     => 'min-width: 350px;',
				'options' => [
					'no'    => __( 'Do not show calculations on the cart page', 'woocommerce-avatax' ),
					'yes'   => __( 'Show estimated tax rates', 'woocommerce-avatax' ),
					'force' => __( 'Force full tax rate calculation', 'woocommerce-avatax' ),
				],
				'default' => 'yes',
			],

			[
				'id'      => 'wc_avatax_calculate_on_cart_international_customers',
				'name'    => __( 'Non-US customers', 'woocommerce-avatax' ),
				'desc'    => __( 'Enable tax calculations on the cart page for international addresses', 'woocommerce-avatax' ),
				'type'    => 'checkbox',
				'default' => 'no',
			],

			[
				'id'      => 'wc_avatax_enable_vat',
				'title'   => __( 'Enable VAT', 'woocommerce-avatax' ),
				'desc'    => __( 'Allow customers to input their VAT ID during checkout', 'woocommerce-avatax' ),
				'type'    => 'checkbox',
				'default' => 'no',
			],

			[
				'type' => 'sectionend',
			],
		];

		/**
		 * Filters the tax calculation settings.
		 *
		 * @since 1.0.0
		 *
		 * @param array $settings the tax calculation settings
		 */
		return (array) apply_filters( 'woocommerce_get_settings_' . $this->id . '_tax_calculation', $settings );
	}


	/**
	 * Get the address validation settings.
	 *
	 * @since 1.0.0
	 * @return array $settings The address validation settings.
	 */
	public function get_address_settings() {

		$settings = array(

			array(
				'type' => 'title',
				'name' => __( 'Address Validation', 'woocommerce-avatax' ),
				'desc' => __( 'Validate shipping addresses at checkout before calculating tax.', 'woocommerce-avatax' ),
			),

			array(
				'id'       => 'wc_avatax_enable_address_validation',
				'name'     => __( 'Enable/Disable', 'woocommerce-avatax' ),
				'desc'     => __( 'Enable AvaTax address validation', 'woocommerce-avatax' ),
				'desc_tip' => __( 'You may incur fees from Avalara for enabling address validation.', 'woocommerce-avatax' ),
				'default'  => 'no',
				'type'     => 'checkbox',
			),

			array(
				'title'   => __( 'Supported Countries', 'woocommerce-avatax' ),
				'id'      => 'wc_avatax_address_validation_countries',
				'class'   => 'wc-enhanced-select',
				'options' => array(
					'US' => __( 'United States (US)', 'woocommerce-avatax' ),
					'CA' => __( 'Canada', 'woocommerce-avatax' ),
				),
				'default' => array(
					'US',
					'CA',
				),
				'type'    => 'multi_select_countries'
			),

			array(
				'id'      => 'wc_avatax_require_address_validation',
				'name'    => __( 'Require for Tax Calculation', 'woocommerce-avatax' ),
				'desc'    => __( 'Require address validation before orders can be placed with calculated tax', 'woocommerce-avatax' ),
				'default' => 'no',
				'type'    => 'checkbox',
			),

			array(
				'type' => 'sectionend',
			),
		);

		/**
		 * Filter the address validation settings.
		 *
		 * @since 1.0.0
		 * @param array $settings The address validation settings.
		 */
		return (array) apply_filters( 'woocommerce_get_settings_' . $this->id . '_address_validation', $settings );
	}


	/**
	 * Get the address verification settings.
	 *
	 * @since 1.0.0
	 * @return array $settings The address verification settings.
	 */
	public function get_misc_settings() {

		$settings = array(

			array(
				'type' => 'title',
			),

			array(
				'id'      => 'wc_avatax_debug',
				'name'    => __( 'Debug Mode', 'woocommerce-avatax' ),
				'desc'    => __( 'Log API requests, responses, and errors for debugging', 'woocommerce-avatax' ),
				'default' => 'no',
				'type'    => 'checkbox',
			),

			array(
				'type' => 'sectionend',
			),
		);

		/**
		 * Filter the misc settings.
		 *
		 * @since 1.0.0
		 * @param array $settings The misc settings.
		 */
		return apply_filters( 'woocommerce_get_settings_' . $this->id . '_misc', $settings );
	}


	/**
	 * Get all of the combined settings.
	 *
	 * @since 1.0.0
	 * @return array $settings The combined settings.
	 */
	public function get_settings() {

		$settings = array_merge(
			$this->get_api_settings(),
			$this->get_product_sync_settings(),
			$this->get_tax_settings(),
			$this->get_address_settings(),
			$this->get_misc_settings()
		);

		/**
		 * Filter the combined settings.
		 *
		 * @since 1.0.0
		 * @param array $settings The combined settings.
		 */
		return apply_filters( 'woocommerce_get_settings_' . $this->id, $settings );
	}


	/**
	 * Replace core Tax settings with our own when the AvaTax section is being viewed.
	 *
	 * @since 1.0.0
	 * @return array
	 */
	public function add_settings( $settings ) {

		global $current_section;

		// Output the general settings
		if ( $this->id == $current_section ) {

			// Always display the API settings
			$settings = array_merge(
				$this->get_api_settings(),
				$this->get_product_sync_settings(),
				$this->get_misc_settings()
			);

			// If the API credentials are good, display the other settings
			if ( $this->get_plugin()->check_api() ) {

				$settings = array_merge(
					$this->get_tax_settings(),
					$this->get_address_settings(),
					$settings
				);
			}

		}

		return $settings;
	}


	/**
	 * Gets the address settings with default fallback values.
	 *
	 * @since 1.7.2
	 *
	 * @param string $setting_id address setting field ID
	 * @return string[] address values
	 */
	private function get_address_values( $setting_id = 'wc_avatax_origin_address' ) {

		$settings         = $this->get_settings();
		$address_defaults = [];
		$address_values   = (array) WC_Admin_Settings::get_option( $setting_id, [] );

		// Loop through each setting to find the address default values.
		foreach ( $settings as $setting ) {

			// The setting ID has to match.
			if ( isset( $setting['id'] ) && $setting_id === $setting['id'] ) {

				$address_defaults = $setting['default'];

				// remove keys that are not present in saved settings
				if ( ! empty( $address_values ) ) {

					// loop through saved address values
					foreach ( $address_defaults as $address_key => $address_value ) {

						if ( ! isset( $address_values[ $address_key ] ) ) {

							unset( $address_defaults[ $address_key ] );
						}
					}
				}

				break;
			}
		}

		return wp_parse_args( $address_values, $address_defaults );
	}


	/**
	 * Display a custom address settings field.
	 *
	 * @since 1.0.0
	 * @param array $options The field options.
	 */
	public function display_address_fields( $options ) {

		$description      = WC_Admin_Settings::get_field_description( $options );
		$tooltip_html     = $description['tooltip_html'];
		$description      = $description['description'];
		$id               = $options['id'];
		$label            = $options['title'];
		$type             = $options['type'];
		$values           = $this->get_address_values( $id );
		$countries        = WC()->countries->countries;
		$selected_country = $values['country'] ?? WC()->countries->get_base_country();

		include( $this->get_plugin()->get_plugin_path() . '/src/admin/views/setting-address.php' );
	}


	/**
	 * Saves the settings.
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 *
	 * @global string $current_section The current settings section.
	 */
	public function save_settings() {
		global $current_section;

		if ( $this->id === $current_section ) {

			// if the API credentials were good at last check, save the settings
			if ( 'connected' === get_transient( 'wc_avatax_connection_status' ) ) {
				$this->save_fields( $this->get_tax_settings() );
				$this->save_fields( $this->get_address_settings() );
			}

			// always save the API, product sync & misc settings
			$this->save_fields( $this->get_api_settings() );
			$this->save_fields( $this->get_misc_settings() );
			$this->save_fields( $this->get_product_sync_settings() );

			$this->get_plugin()->clear_company_id_cache();

			// reset the API status transient
			delete_transient( 'wc_avatax_connection_status' );
			delete_transient( 'wc_avatax_subscribed' );

			// TODO: we should do the same for Item Classification API as well {IT 2022-01-11}
			// check the API again and display an error for bad credentials
			if ( $this->get_plugin()->check_api() ) {
				$this->cache_company_id();
			} else {
				WC_Admin_Settings::add_error( __( 'Your Avalara connection settings are not valid.', 'woocommerce-avatax' ) );
			}

			// stop cross-border product sync in case credentials were removed
			if ( ! $this->get_plugin()->has_hs_api_credentials_set() ) {
				$this->get_plugin()->get_landed_cost_sync_handler()->stop_syncing();
			}
		}
	}


	/**
	 * Save the settings fields.
	 *
	 * This is a simple wrapper for `WC_Admin_Settings::save_fields` to intercept our custom "address"
	 * field type for special handling. All other fields are saved as usual. This is being improved in WC 2.4+
	 * but for now this is easiest for older versions.
	 *
	 * @since 1.0.0
	 *
	 * @param array $fields the settings fields to save
	 */
	private function save_fields( $fields ) {

		// Loop through each setting and look for an address field
		foreach ( $fields as $key => $field ) {

			// If found, save it our way and remove it from the settings to save the WooCommerce way
			if ( isset( $field['id'], $field['type'] ) && 'wc_avatax_address' === $field['type'] ) {
				$this->save_address_field( $field );
				unset( $fields[ $key ] );
			}
		}

		WC_Admin_Settings::save_fields( $fields );
	}


	/**
	 * Save the custom address field.
	 *
	 * @since 1.0.0
	 * @param array $field The field definition.
	 */
	private function save_address_field( $field ) {

		$address = isset( $_POST[ $field['id'] ] ) ? wp_unslash( $_POST[ $field['id'] ] ) : array();

		/**
		 * Filter the address values before the final save.
		 *
		 * @since 1.0.0
		 * @param array $address {
		 *     The address values.
		 *
		 * @type string @address_1 The street address.
		 * @type string @city      The city name.
		 * @type string @state     The state.
		 * @type string @country   The country code.
		 * @type string @postcode  The postal code.
		 * }
		 */
		$address = (array) apply_filters( 'wc_avatax_save_address_field', $address );

		$address = array_map( 'wc_clean', $address );

		if ( ! empty( $address ) ) {
			update_option( $field['id'], $address );
		}
	}


	/**
	 * Gets the product sync settings fields.
	 *
	 * @since 1.13.0
	 *
	 * @return array
	 */
	protected function get_product_sync_settings() : array {

		if ( ! wc_avatax()->has_api_credentials_set() ) {
			return [];
		}

		$settings = [
			[
				'type' => 'title',
				'name' => __( 'Cross-border classification', 'woocommerce-avatax' ),
			],
			[
				'id'       => 'wc_avatax_enable_cross_border_classification',
				'name'     => __( 'Enable/Disable', 'woocommerce-avatax' ),
				'desc'     => __( 'Sync products to classify for cross-border duties', 'woocommerce-avatax' ),
				'default'  => 'no',
				'type'     => 'checkbox',
			],
			[
				'id'       => 'wc_avatax_hs_api_username',
				'name'     => __( 'AvaTax username', 'woocommerce-avatax' ),
				'type'     => 'text',
				'css'      => 'min-width:300px;',
				'desc_tip' => __( 'The username used for your Avalara Login.', 'woocommerce-avatax' ),
			],
			[
				'id'       => 'wc_avatax_hs_api_password',
				'name'     => __( 'AvaTax password', 'woocommerce-avatax' ),
				'type'     => 'password',
				'css'      => 'min-width:300px;',
				'desc_tip' => __( 'The password used for your Avalara Login.', 'woocommerce-avatax' ),
			],
			[
				'id'                => 'wc_avatax_api_product_countries_sync',
				'name'              => __( 'Cross-Border Countries', 'woocommerce-avatax' ),
				'type'              => 'multiselect',
				'class'             => 'wc-enhanced-select',
				'options'           => $this->get_normalized_supported_countries_options(),
				'custom_attributes' => [ 'multiple' => 'multiple' ],
				'desc'              => __( 'Choose countries to calculate Cross-Border duties', 'woocommerce-avatax' ),
				'desc_tip'          => __( 'Items will be classified for tariff codes in all selected countries. This may affect your AvaTax monthly bill.', 'woocommerce-avatax' ),
			],
			[
				'id'       => 'wc_avatax_api_product_sync',
				'name'     => __( 'Cross-Border product sync', 'woocommerce-avatax' ),
				'type'     => 'wc_avatax_product_sync',
				'desc'     => $this->get_cross_border_product_sync_description(),
				'desc_tip' => __( 'Sync your product catalog for cross-border calculations for your Supported Locations. Cross-border must be enabled on your AvaTax account to use this feature.', 'woocommerce-avatax' ),
			],
			[ 'type' => 'sectionend' ],
		];

		/**
		 * Filters the product sync settings fields.
		 *
		 * @since 1.13.0
		 *
		 * @param array $settings product sync fields
		 */
		return (array) apply_filters( 'woocommerce_get_settings_' . $this->id . '_product_sync', $settings );
	}


	/**
	 * Gets a list of countries that’s ready to be used as a setting option.
	 *
	 * @since 1.13.0
	 *
	 * @return array
	 */
	private function get_normalized_supported_countries_options() : array {

		$this->cache_company_id();

		$supported_countries = $this->get_plugin()->get_landed_cost_handler()->get_supported_countries();

		if ( empty( $supported_countries ) ) {
			return [];
		}

		$supported_countries  = array_map( 'mb_strtoupper', $supported_countries );

		$normalized_countries = ( new WC_Countries() )->get_countries();

		foreach ( $normalized_countries as $country_code => $country_name ) {
			if ( ! in_array( $country_code, $supported_countries, true ) ) {
				unset( $normalized_countries[ $country_code ] );
			}
		}

		return $normalized_countries;
	}


	/**
	 * Gets the Cross Border Product Sync field description.
	 *
	 * @since 1.13.0
	 *
	 * @return string
	 */
	private function get_cross_border_product_sync_description() : string {

		if ( $this->get_plugin()->get_landed_cost_sync_handler()->is_syncing_active() ) {
			return __( 'Sending products for classification! It may take some time for classification to complete. You can safely leave this page.', 'woocommerce-avatax' );
		}

		$sync_error_items = '';

		if ( ! $this->get_plugin()->has_hs_api_credentials_set() ) {
			$sync_error_items .= '<li>' . __( 'Please, fill in your Avalara username and password', 'woocommerce-avatax' ) . '</li>';
		}

		if ( ! $this->get_plugin()->get_landed_cost_handler()->has_countries_for_product_sync() ) {
			$sync_error_items .= '<li>' . __( 'Please select at least one supported country.', 'woocommerce-avatax' ) . '</li>';
		}

		return ! empty( $sync_error_items ) ? sprintf(
			/* translators: Placeholders: %1$s - opening <strong> HTML tag, %2$s - closing </strong> HTML tag, %3$s - unordered HTML list of sync errors */
			__( '%1$sUnable to sync. Please correct the following errors:%2$s %3$s', 'woocommerce-avatax' ),
			'<strong>',
			'</strong>',
			'<ul class="wc-avatax-producst-sync-errors ul-disc" style="color: #A94545;">' . $sync_error_items . '<ul>'
		) : '';
	}


	/**
	 * Displays a custom product sync settings field.
	 *
	 * @internal
	 *
	 * @since 1.13.0
	 *
	 * @param array $options
	 */
	public function display_product_sync_field( $options ) {

		$description = WC_Admin_Settings::get_field_description( $options );

		$id           = $options['id'];
		$label        = $options['title'];
		$tooltip_html = $description['tooltip_html'];
		$description  = $description['description'];
		$disabled     = ! $this->is_cross_border_product_sync_connection_allowed();
		$syncing      = $this->get_plugin()->get_landed_cost_sync_handler()->is_syncing_active();

		require_once( $this->get_plugin()->get_plugin_path() . '/src/admin/views/product-sync.php' );
	}


	/**
	 * Handles the sync countries list update.
	 *
	 * This is a callback to be executed when the list of countries to sync is updated.
	 *
	 * @internal
	 *
	 * @since 1.13.0
	 *
	 * @param array $old_countries
	 * @param array $new_countries
	 */
	public function handle_countries_sync_update( $old_countries, $new_countries ) {

		$sync_handler = $this->get_plugin()->get_landed_cost_sync_handler();

		if ( $sync_handler->is_syncing_active() && ! empty( array_diff( $new_countries, $old_countries ) ) ) {
			$sync_handler->enqueue_full_sync();
		}
	}


	/**
	 * Determines whether the API environment is set to production.
	 *
	 * @since 1.13.0
	 * @deprecated 1.16.0
	 *
	 * @return bool
	 */
	public function is_api_environment_production() : bool {

		wc_deprecated_function( __METHOD__, '1.16.0' );

		return 'production' === get_option( 'wc_avatax_api_environment' );
	}


	/**
	 * Determines whether the store may try to connect to Cross-Border product sync.
	 *
	 * @since 1.13.0
	 *
	 * @return bool
	 */
	public function is_cross_border_product_sync_connection_allowed() : bool {

		$connection_allowed =
			wc_avatax()->has_api_credentials_set()
			&& wc_avatax()->has_hs_api_credentials_set()
			&& $this->get_plugin()->get_landed_cost_handler()->has_countries_for_product_sync();

		/**
		 * Filters whether the user is allowed to connect Cross Border product sync.
		 *
		 * @since 1.13.0
		 *
		 * @param bool $connection_allowed whether the user is allowed to connect Cross Border product sync
		 */
		return (bool) apply_filters( 'wc_avatax_cross_border_product_sync_connection_allowed', $connection_allowed );
	}


	/**
	 * Gets an instance of the plugin main class.
	 *
	 * @since 1.13.0
	 *
	 * @return WC_AvaTax
	 */
	protected function get_plugin() : WC_AvaTax {

		return wc_avatax();
	}


	/**
	 * Caches the company ID to prevent race conditions with other requests that depend on it.
	 *
	 * @since 1.13.0
	 */
	protected function cache_company_id() {

		wc_avatax()->get_company_id();
	}

	/**
	 * Clears the company ID cache when updating the company code.
	 *
	 * Also stops sync in progress if the company code changes when saving settings.
	 *
	 * @internal
	 *
	 * @since 1.17.0
	 *
	 * @param string|mixed $old_company_code
	 * @param string|mixed $new_company_code
	 * @return void
	 */
	public function prune_company_id_cache( $old_company_code, $new_company_code ) {

		if ( $old_company_code === $new_company_code ) {
			return;
		}

		wc_avatax()->clear_company_id_cache();
		wc_avatax()->get_landed_cost_sync_handler()->stop_syncing();
	}


}

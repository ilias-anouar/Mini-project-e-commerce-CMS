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

defined( 'ABSPATH' ) or exit;

use SkyVerge\WooCommerce\AvaTax\Api\WC_AvaTax_HS_API;
use SkyVerge\WooCommerce\AvaTax\Landed_Cost_Sync_Handler;
use SkyVerge\WooCommerce\PluginFramework\v5_10_14 as Framework;

/**
 * WooCommerce AvaTax main plugin class.
 *
 * @since 1.0.0
 */
class WC_AvaTax extends Framework\SV_WC_Plugin {


	/** plugin version number */
	const VERSION = '2.2.0';

	/** client version string */
	const CLIENT_STRING = 'a0o5a000007hTYaAAM';

	/** plugin id */
	const PLUGIN_ID = 'avatax';

	/** @var WC_AvaTax single instance of this plugin */
	protected static $instance;

	/** @var WC_AvaTax_API the api class */
	protected $api;

	/** @var WC_AvaTax_HS_API the HS Classification API class */
	protected $hs_api;

	/** @var \WC_AvaTax_REST_API instance */
	protected $rest_api;

	/** @var \WC_AvaTax_Tax_Handler instance */
	protected $tax_handler;

	/** @var \WC_AvaTax_Order_Handler instance */
	protected $order_handler;

	/** @var \WC_AvaTax_Checkout_Handler instance */
	protected $checkout_handler;

	/** @var WC_AvaTax_Landed_Cost_Handler instance */
	protected $landed_cost_handler;

	/** @var \WC_AvaTax_Integrations instance */
	protected $integrations;

	/** @var \WC_AvaTax_Admin instance */
	protected $admin;

	/** @var \WC_AvaTax_Frontend instance */
	protected $frontend;

	/** @var \WC_AvaTax_AJAX instance */
	protected $ajax;

	/** @var \WC_AvaTax_Import_Export_Handler instance, adds support for import/export functionality */
	protected $import_export_handler;

	/** @var bool $logging_enabled Whether debug logging is enabled */
	private $logging_enabled;

	/** @var Landed_Cost_Sync_Handler instance, The handler for the synchronization process */
	protected $landed_cost_sync_handler;


	/**
	 * Plugin constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		parent::__construct( self::PLUGIN_ID, self::VERSION, array(
			'text_domain' => 'woocommerce-avatax',
		) );

		// Turn off API request logging unless specified in the settings
		if ( ! $this->logging_enabled() ) {
			remove_action( 'wc_' . $this->get_id() . '_api_request_performed', array( $this, 'log_api_request' ) );
		}
	}


	/**
	 * Flags TLS 1.2 as required for AvaTax API calls.
	 *
	 * @since 1.16.0
	 *
	 * @return bool
	 */
	public function require_tls_1_2() {

		return true;
	}


	/**
	 * Initializes the lifecycle handler.
	 *
	 * @since 1.7.0
	 */
	protected function init_lifecycle_handler() {

		require_once( $this->get_plugin_path() . '/src/Lifecycle.php' );

		$this->lifecycle_handler = new \SkyVerge\WooCommerce\AvaTax\Lifecycle( $this );
	}


	/**
	 * Initializes the plugin.
	 *
	 * @since 1.12.0
	 */
	public function init_plugin() {

		parent::init_plugin();

		// General includes
		require_once( $this->get_plugin_path() . '/src/Traits/Resolves_Product_Item_Code.php' );

		// Set up the base tax handler
		$this->tax_handler = $this->load_class( '/src/class-wc-avatax-tax-handler.php', 'WC_AvaTax_Tax_Handler' );

		// set up the order handler
		$this->order_handler = $this->load_class( '/src/class-wc-avatax-order-handler.php', 'WC_AvaTax_Order_Handler' );

		// set up the checkout handler
		$this->checkout_handler = $this->load_class( '/src/class-wc-avatax-checkout-handler.php', 'WC_AvaTax_Checkout_Handler' );

		// set up the integrations handler
		$this->integrations = $this->load_class( '/src/integrations/class-wc-avatax-integrations.php', 'WC_AvaTax_Integrations' );

		// Frontend includes
		if ( ! is_admin() ) {
			$this->frontend = $this->load_class( '/src/frontend/class-wc-avatax-frontend.php', 'WC_AvaTax_Frontend' );
		}

		// Admin includes
		if ( is_admin() && ! wp_doing_ajax() ) {
			$this->admin = $this->load_class( '/src/admin/class-wc-avatax-admin.php', 'WC_AvaTax_Admin' );
		}

		// Import / Export handler needs to be available in admin over ajax
		if ( is_admin() ) {
			$this->import_export_handler = $this->load_class( '/src/integrations/class-wc-avatax-import-export-handler.php', 'WC_AvaTax_Import_Export_Handler' );
		}

		// AJAX includes
		if ( wp_doing_ajax() ) {
			$this->ajax = $this->load_class( '/src/class-wc-avatax-ajax.php', 'WC_AvaTax_AJAX' );
		}

		// REST API handler
		$this->rest_api = $this->get_rest_api_instance();

		$this->landed_cost_sync_handler = $this->get_landed_cost_sync_handler();
	}


	/**
	 * Returns the base tax handler.
	 *
	 * @since 1.5.0
	 *
	 * @return \WC_AvaTax_Tax_Handler
	 */
	public function get_tax_handler() {

		return $this->tax_handler;
	}


	/**
	 * Returns the admin class instance.
	 *
	 * @since 1.2.0
	 *
	 * @return \WC_AvaTax_Admin
	 */
	public function get_admin_instance() {

		return $this->admin;
	}


	/**
	 * Returns the frontend class instance.
	 *
	 * @since 1.2.0
	 *
	 * @return \WC_AvaTax_Frontend
	 */
	public function get_frontend_instance() {

		return $this->frontend;
	}


	/**
	 * Returns the ajax handler.
	 *
	 * @since 1.2.0
	 *
	 * @return \WC_AvaTax_AJAX
	 */
	public function get_ajax_handler() {

		return $this->ajax;
	}


	/**
	 * Returns the import/export handler class instance.
	 *
	 * @since 1.3.0
	 *
	 * @return \WC_AvaTax_Import_Export_Handler
	 */
	public function get_import_export_handler_instance() {

		return $this->import_export_handler;
	}


	/**
	 * Returns the order handler.
	 *
	 * @since 1.2.0
	 *
	 * @return \WC_AvaTax_Order_Handler The order handler object
	 */
	public function get_order_handler() {

		return $this->order_handler;
	}


	/**
	 * Returns the checkout handler.
	 *
	 * @since 1.2.0
	 *
	 * @return \WC_AvaTax_Checkout_Handler The checkout handler object
	 */
	public function get_checkout_handler() {

		return $this->checkout_handler;
	}


	/**
	 * Returns the integrations handler.
	 *
	 * @since 1.2.0
	 *
	 * @return \WC_AvaTax_Integrations integrations handler object
	 */
	public function get_integrations() {

		return $this->integrations;
	}


	/**
	 * Returns the landed cost handler.
	 *
	 * @since 1.5.0
	 *
	 * @return WC_AvaTax_Landed_Cost_Handler landed cost handler instance
	 */
	public function get_landed_cost_handler() : WC_AvaTax_Landed_Cost_Handler {

		if ( ! $this->landed_cost_handler instanceof WC_AvaTax_Landed_Cost_Handler ) {

			$this->landed_cost_handler = $this->load_class( '/src/class-wc-avatax-landed-cost-handler.php', 'WC_AvaTax_Landed_Cost_Handler' );

			$this->landed_cost_handler->add_hooks();
		}

		return $this->landed_cost_handler;
	}


	/**
	 * Returns the WP REST API handler instance.
	 *
	 * @since 1.7.0
	 *
	 * @return \WC_AvaTax_REST_API
	 */
	public function get_rest_api_instance() {

		if ( null === $this->rest_api ) {

			require_once( $this->get_plugin_path() . '/src/api/class-wc-avatax-rest-api.php' );

			$this->rest_api = new WC_AvaTax_REST_API( $this );
		}

		return $this->rest_api;
	}


	/**
	 * Returns the deprecated/removed hooks.
	 *
	 * @since 1.5.0
	 *
	 * @see Framework\SV_WC_Plugin::get_deprecated_hooks()
	 * @return array
	 */
	protected function get_deprecated_hooks() {

		$hooks = array(
			'wc_avatax_calculate_taxes' => array(
				'version'     => '1.5.0',
				'removed'     => true,
				'replacement' => 'wc_avatax_is_enabled',
				'map'         => true,
			),
		);

		return $hooks;
	}


	/** Admin methods ******************************************************/


	/**
	 * Renders a notice for the user to read the docs before adding add-ons.
	 *
	 * @since 1.0.0
	 *
	 * @see Framework\SV_WC_Plugin::add_admin_notices()
	 */
	public function add_admin_notices() {

		// show any dependency notices
		parent::add_admin_notices();

		$screen = get_current_screen();

		if ( 'wc-settings' === Framework\SV_WC_Helper::get_requested_value( 'page' ) || 'plugins' === $screen->id ) {

			// if the API is not connected, display a persistent notice throughout WC settings screens
			if ( ! $this->check_api() ) {

				// TODO: consider showing a similar notice for cross-border sync {IT 2022-01-12}
				$this->get_admin_notice_handler()->add_admin_notice( sprintf(
					/* translators: Placeholders: %1$s - <strong> tag, %2$s - </strong> tag, %3$s - <a> tag, %4$s - </a> tag */
					__( '%1$sWooCommerce AvaTax is almost ready!%2$s To get started, please ​%3$sconnect to AvaTax%4$s.', 'woocommerce-avatax' ),
					'<strong>',
					'</strong>',
					'<a href="' . esc_url( $this->get_settings_url() ) . '">',
					'</a>'
				), 'wc-avatax-welcome', array(
					'always_show_on_settings' => false,
					'dismissible'             => false,
				) );

			// otherwise, display various other config notices
			} else {

				// dismissable welcome notice
				$this->get_admin_notice_handler()->add_admin_notice( sprintf(
					/* translators: Placeholders: %1$s - <strong> tag, %2$s - </strong> tag, %3$s - <a> tag, %4$s - </a> tag */
					__( '%1$sThanks for installing WooCommerce AvaTax!%2$s Need help? %3$sRead the documentation%4$s.', 'woocommerce-avatax' ),
					'<strong>',
					'</strong>',
					'<a href="' . esc_url( $this->get_documentation_url() ) . '" target="_blank">',
					'</a>'
				), 'wc-avatax-welcome', array(
					'always_show_on_settings' => false,
					'dismissible'             => true,
					'notice_class'            => 'updated'
				) );

				// AvaTax calculation is enabled but global WC taxes are disabled
				if ( $this->get_tax_handler()->is_enabled() && ! wc_tax_enabled() ) {

					$this->get_admin_notice_handler()->add_admin_notice( sprintf(
						/* translators: Placeholders: %1$s - <strong> tag, %2$s - </strong> tag, , %3$s - <a> tag, %4$s - </a> tag */
						__( '%1$sWooCommerce taxes are disabled.%2$s To see tax rates from AvaTax at checkout, please %3$senable taxes%4$s for your store.', 'woocommerce-avatax' ),
						'<strong>', '</strong>',
						'<a href="' . esc_url( admin_url( 'admin.php?page=wc-settings' ) ) . '">', '</a>'
					), 'wc-taxes-deactivated-notice', array(
						'notice_class' => 'error',
					) );
				}

				// only display these on the plugin settings page
				if ( $this->is_plugin_settings() ) {

					// the origin address is not configured properly
					if ( ! $this->get_tax_handler()->is_origin_address_complete() ) {

						$this->get_admin_notice_handler()->add_admin_notice( sprintf(
							/* translators: Placeholders: %1$s - <strong> tag, %2$s - </strong> tag */
							__( '%1$sWooCommerce AvaTax%2$s calculation is disabled. Please configure your full Origin Address.', 'woocommerce-avatax' ),
							'<strong>', '</strong>'
						), 'wc-avatax-origin-notice', array(
							'notice_class' => 'error',
						) );
					}

					// TODO: since 1.16.0 subscription checks have been soft-deprecated - we no longer check them,
					// 	but we're keeping the methods intact in case we need to revert {IT 2022-01-11}
					// add notices for any missing subscriptions
					// $this->add_missing_subscriptions_notices();
				}

			}
		}

		// display a notice when the legacy extension is deactivated
		if ( 'plugins' === $screen->id && 'yes' === get_option( 'wc_avatax_legacy_deactivated' ) ) {

			$this->get_admin_notice_handler()->add_admin_notice( __( 'The legacy version of the WooCommerce AvaTax exension was deactivated.', 'woocommerce-avatax' ), 'legacy-deactivated-notice', array(
				'always_show_on_settings' => false,
				'notice_class'            => 'updated'
			) );

			delete_option( 'wc_avatax_legacy_deactivated' );
		}

		$this->maybe_add_product_sync_error_notice();
		$this->maybe_add_sync_weight_hint_notice();
	}


	/**
	 * Adds admin notices for any missing Avalara account subscriptions.
	 *
	 * TODO: since 1.16.0 this method is no longer called, as there are no subscriptions to check. Consider removing {IT 2022-01-11}
	 *
	 * @since 1.5.0
	 * @deprecated 1.16.0
	 */
	protected function add_missing_subscriptions_notices() {

		foreach ( $this->get_missing_subscriptions() as $subscription ) {

			$this->get_admin_notice_handler()->add_admin_notice( sprintf(
				__( 'Your Avalara account is not configured for %s. Please contact Avalara support to use this feature.', 'woocommerce-avatax' ),
				$subscription
			), 'legacy-deactivated-notice', array(
				'always_show_on_settings' => true,
				'notice_class'            => 'error',
			) );
		}
	}


	/**
	 * May add an admin notice if the sync process had error responses.
	 *
	 * @since 1.13.0
	 */
	private function maybe_add_product_sync_error_notice() {

		if ( empty( get_option( 'wc_avatax_landed_cost_products_with_sync_errors' ) ) &&
		     empty( get_option( 'wc_avatax_landed_cost_products_with_sync_resolutions' ) ) ) {
			return;
		}

		$resync_link = wp_nonce_url( admin_url( 'admin-ajax.php?action=wc_avatax_resync_error_products' ), 'wc_avatax_resync_error_products', 'nonce' );

		$this->get_admin_notice_handler()->add_admin_notice(
			sprintf(
				/* translators: Placeholders: %1$s - <strong> tag, %2$s - </strong> tag, %3$s - <a> tag, %4$s - </a> tag, %5$s - <a> tag, %6$s - </a> tag */
				__( '<p>%1$sHeads up!%2$s Cross-border product sync encountered an error while syncing your products to AvaTax. Please try again or contact support if you\'re continuing to encounter this message.</p>%3$sResync products%4$s %5$sContact support%6$s', 'woocommerce-avatax' ),
				'<strong>', '</strong>',
				'<a href="' . esc_url( $resync_link ) . '" class="button button-primary">', '</a>',
				'<a href="https://woocommerce.com/my-account/create-a-ticket/" target="_blank" class="button">', '</a>'
			),
			'wc-avatax-sync-error-notice',
			[
				'notice_class'            => 'error',
				'always_show_on_settings' => false,
				'dismissible'             => true,
			] );
	}


	/**
	 * May add a hint admin notice about adding weights to products.
	 *
	 * @since 1.13.0
	 */
	private function maybe_add_sync_weight_hint_notice() {

		if ( ! $this->get_landed_cost_sync_handler()->is_syncing_active() ) {
			return;
		}

		$this->get_admin_notice_handler()->add_admin_notice( sprintf(
			/* translators: Placeholders: %1$s - <a> tag, %2$s - </a> tag */
			__( 'For best results in calculating cross-border duties, ensure weight is entered for your products in Product data > Shipping. %1$sLearn more%2$s.', 'woocommerce-avatax' ),
			'<a href="https://help.avalara.com/Avalara_Item_Classification_and_Cross-border/Understand_customs_duty_calculations_in_AvaTax" target="_blank">', '</a>'
		), 'wc-avatax-sync-weight-hint',
		[
			'always_show_on_settings' => false,
			'dismissible'             => true,
		] );
	}


	/** Helper methods ******************************************************/


	/**
	 * Main WC_AvaTax Instance, ensures only one instance is/can be loaded.
	 *
	 * @since 1.0.0
	 *
	 * @see wc_avatax()
	 * @return WC_AvaTax
	 */
	public static function instance() {

		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}


	/**
	 * Returns the plugin documentation URL.
	 *
	 * @since 1.0.0
	 *
	 * @see Framework\SV_WC_Plugin::get_documentation_url()
	 * @return string
	 */
	public function get_documentation_url() {

		return 'http://docs.woocommerce.com/document/woocommerce-avatax/';
	}


	/**
	 * Gets the plugin sales page URL.
	 *
	 * @since 1.7.1
	 *
	 * @return string
	 */
	public function get_sales_page_url() {

		return 'https://woocommerce.com/products/woocommerce-avatax/';
	}


	/**
	 * Returns the plugin support URL.
	 *
	 * @since 1.0.0
	 *
	 * @see Framework\SV_WC_Plugin::get_support_url()
	 * @return string
	 */
	public function get_support_url() {

		return 'https://woocommerce.com/my-account/tickets/';
	}


	/**
	 * Returns the plugin name, localized.
	 *
	 * @since 1.0.0
	 *
	 * @see Framework\SV_WC_Plugin::get_plugin_name()
	 * @return string the plugin name
	 */
	public function get_plugin_name() {

		return __( 'WooCommerce AvaTax', 'woocommerce-avatax' );
	}


	/**
	 * Returns __FILE__
	 *
	 * @since 1.0.0
	 *
	 * @see Framework\SV_WC_Plugin::get_file()
	 * @return string the full path and filename of the plugin file
	 */
	protected function get_file() {

		return __FILE__;
	}


	/**
	 * Returns true if on the plugin's settings page.
	 *
	 * @since 1.0.0
	 *
	 * @see Framework\SV_WC_Plugin::is_plugin_settings()
	 * @return boolean true if on the settings page
	 */
	public function is_plugin_settings() {

		return isset( $_GET['page'] ) && 'wc-settings' == $_GET['page'] && isset( $_GET['tab'] ) &&
			( ( 'tax' == $_GET['tab'] && isset( $_GET['section'] ) && 'avatax' == $_GET['section'] ) ||
			( 'avatax-landed-cost' == $_GET['tab'] ) );
	}


	/**
	 * Returns the plugin configuration URL.
	 *
	 * @since 1.0.0
	 *
	 * @see Framework\SV_WC_Plugin::get_settings_link()
	 * @param string $plugin_id optional plugin identifier.  Note that this can be a
	 *        sub-identifier for plugins with multiple parallel settings pages
	 *        (ie a gateway that supports both credit cards and echecks)
	 * @return string plugin settings URL
	 */
	public function get_settings_url( $plugin_id = null ) {

		return admin_url( 'admin.php?page=wc-settings&tab=tax&section=avatax' );
	}


	/**
	 * Determines if debug logging is enabled.
	 *
	 * @since 1.0.0
	 *
	 * @return bool $logging_enabled Whether debug logging is enabled.
	 */
	public function logging_enabled() {

		$this->logging_enabled = ( 'yes' === get_option( 'wc_avatax_debug' ) );

		/**
		 * Filter whether debug logging is enabled.
		 *
		 * @since 1.0.0
		 * @param bool $logging_enabled Whether debug logging is enabled.
		 */
		return apply_filters( 'wc_avatax_logging_enabled', $this->logging_enabled );
	}


	/**
	 * Gets the configured API environment.
	 *
	 * @since 1.16.0
	 *
	 * @return string
	 */
	public function get_api_environment() : string {

		return (string) get_option( 'wc_avatax_api_environment', 'production' );
	}


	/**
	 * Returns the API class instance.
	 *
	 * @since 1.0.0
	 *
	 * @return WC_AvaTax_API
	 */
	public function get_api() : WC_AvaTax_API {

		// Return the API object if already instantiated
		if ( is_object( $this->api ) ) {
			return $this->api;
		}

		$this->load_api_classes();

		// Get the API token & secret
		$account_number = get_option( 'wc_avatax_api_account_number' );
		$license_key    = get_option( 'wc_avatax_api_license_key' );
		$company_code   = get_option( 'wc_avatax_company_code' ); // TODO: set this on the request level?
		$environment    = $this->get_api_environment();

		// Instantiate the API
		return $this->api = new WC_AvaTax_API( $account_number, $license_key, $company_code, $environment );
	}


	/**
	 * Returns the HS Classification API class instance.
	 *
	 * @since 1.13.0
	 *
	 * @return WC_AvaTax_HS_API
	 */
	public function get_hs_api() {

		if ( is_object( $this->hs_api ) ) {
			return $this->hs_api;
		}

		$this->load_api_classes();

		$username    = get_option( 'wc_avatax_hs_api_username' );
		$password    = get_option( 'wc_avatax_hs_api_password' );
		$environment = $this->get_api_environment();

		return $this->hs_api = new WC_AvaTax_HS_API( $username, $password, $environment );
	}


	/**
	 * Determines if API credentials exist and are valid.
	 *
	 * @since 1.0.0
	 *
	 * @param bool $check_cache Whether to check the cached result first.
	 * @return bool Whether the API credentials exist and are valid.
	 */
	public function check_api( $check_cache = true ) : bool {

		if ( $check_cache && ( $cache = get_transient( 'wc_avatax_connection_status' ) ) ) {

			if ( 'connected' === $cache ) {
				return true;
			}

			if ( 'not-connected' === $cache ) {
				return false;
			}
		}

		/**
		 * Filter the amount of time to keep the connection status cache.
		 *
		 * @since 1.0.0
		 * @param int $expiration The cache expiration, in seconds.
		 */
		$cache_expiration = apply_filters( 'wc_avatax_connection_status_cache_expiration', MINUTE_IN_SECONDS * 5 );

		try {

			$response = $this->get_api()->test();

			if ( ! $response->is_authenticated() ) {
				throw new Framework\SV_WC_API_Exception( 'Not authenticated' );
			}

			set_transient( 'wc_avatax_connection_status', 'connected', $cache_expiration );

			return true;

		} catch ( Framework\SV_WC_API_Exception $e ) {

			if ( $this->logging_enabled() ) {
				$this->log( $e->getCode() . ' - ' . $e->getMessage() );
			}

			set_transient( 'wc_avatax_connection_status', 'not-connected', $cache_expiration );

			return false;
		}
	}


	/**
	 * Gets any missing subscriptions from the Avalara account configuration.
	 *
	 * TODO: since 1.16.0 this method is no longer called, as there are no subscriptions to check. Consider removing {IT 2022-01-11}
	 *
	 * @since 1.5.0
	 * @deprecated 1.16.0
	 *
	 * @return array $subscriptions missing subscriptions by name
	 */
	protected function get_missing_subscriptions() : array {

		$subscriptions = array();

		// check for the cached result first
		if ( 'yes' === get_transient( 'wc_avatax_subscribed' ) ) {
			return $subscriptions;
		}

		// no cache exists, so test the API
		try {

			$response = $this->get_api()->get_subscriptions();

			// check for landed cost
			if ( $this->get_landed_cost_handler()->is_enabled() && ! $response->has_landed_cost() ) {
				$subscriptions[] = __( 'Landed Cost', 'woocommerce-avatax' );
			}

		} catch ( Framework\SV_WC_API_Exception $e ) {

			if ( $this->logging_enabled() ) {
				$this->log( $e->getCode() . ' - ' . $e->getMessage() );
			}
		}

		if ( empty( $subscriptions ) ) {
			set_transient( 'wc_avatax_subscribed', 'yes', DAY_IN_SECONDS );
		}

		return $subscriptions;
	}


	/**
	 * Gets the AvaTax company ID.
	 *
	 * @since 1.13.0
	 *
	 * @return string
	 */
	public function get_company_id() : string {

		$company_id = get_transient( 'wc_avatax_company_id' );
		$company_code = get_option( 'wc_avatax_company_code' );

		if ( $company_id && $company_code === get_transient( 'wc_avatax_company_code' ) ) {
			return (string) $company_id;
		}

		try {

			if ( $this->has_api_credentials_set() && $api = $this->get_api() ) {

				$company_id = $api->get_companies()->get_company_id( $company_code );

				set_transient( 'wc_avatax_company_id', $company_id, DAY_IN_SECONDS );
				set_transient( 'wc_avatax_company_code', $company_code, DAY_IN_SECONDS );

				return $company_id;
			}

		} catch ( \Exception $e ) {

			if ( $this->logging_enabled() ) {
				$this->log( sprintf( '%1$s: %2$s', $e->getCode() ?? 'Error', $e->getMessage() ) );
			}
		}

		return '';
	}


	/**
	 * Clears the AvaTax company ID from the cache.
	 *
	 * @since 1.17.0
	 *
	 * @return void
	 */
	public function clear_company_id_cache() : void {

		delete_transient( 'wc_avatax_company_id' );
		delete_transient( 'wc_avatax_company_code' );
	}


	/**
	 * Gets the landed cost sync handler.
	 *
	 * @since 1.13.0
	 *
	 * @return Landed_Cost_Sync_Handler
	 */
	public function get_landed_cost_sync_handler() : Landed_Cost_Sync_Handler {

		if ( ! $this->landed_cost_sync_handler instanceof Landed_Cost_Sync_Handler ) {

			require_once( $this->get_plugin_path() . '/src/api/Models/HS_Classification_Model.php' );
			require_once( $this->get_plugin_path() . '/src/api/Models/HS_Item_Model.php' );
			require_once( $this->get_plugin_path() . '/src/Landed_Cost_Sync_Enqueued_Product.php' );

			$this->landed_cost_sync_handler = $this->load_class( '/src/Landed_Cost_Sync_Handler.php', Landed_Cost_Sync_Handler::class );
		}

		return $this->landed_cost_sync_handler;
	}


	/**
	 * Determines whether the account number and license key are set.
	 *
	 * @since 1.13.0
	 *
	 * @return bool
	 */
	public function has_api_credentials_set() : bool {

		return ! empty ( get_option( 'wc_avatax_api_account_number' ) ) && ! empty ( get_option( 'wc_avatax_api_license_key' ) );
	}


    /**
     * Determines whether the HS API login and password are set.
     *
     * @since 1.13.0
     *
     * @return bool
     */
	public function has_hs_api_credentials_set() : bool {

		return ! empty ( get_option( 'wc_avatax_hs_api_username' ) ) && ! empty ( get_option( 'wc_avatax_hs_api_password' ) );
	}


	/**
	 * Loads all the API classes.
	 *
	 * @since 1.13.0
	 */
	private function load_api_classes() {

		// loads the abstract API class
		require_once( $this->get_plugin_path() . '/src/api/WC_AvaTax_Abstract_API.php' );

		// loads the main API classes
		require_once( $this->get_plugin_path() . '/src/api/class-wc-avatax-api.php' );
		require_once( $this->get_plugin_path() . '/src/api/class-wc-avatax-api-tax-rate.php' );
		require_once( $this->get_plugin_path() . '/src/api/requests/class-wc-avatax-api-request.php' );
		require_once( $this->get_plugin_path() . '/src/api/requests/class-wc-avatax-api-utility-request.php' );
		require_once( $this->get_plugin_path() . '/src/api/requests/class-wc-avatax-api-subscriptions-request.php' );
		require_once( $this->get_plugin_path() . '/src/api/requests/class-wc-avatax-api-entity-use-code-request.php' );
		require_once( $this->get_plugin_path() . '/src/api/requests/class-wc-avatax-api-rate-request.php' );
		require_once( $this->get_plugin_path() . '/src/api/requests/class-wc-avatax-api-company-request.php' );
		require_once( $this->get_plugin_path() . '/src/api/requests/class-wc-avatax-api-tax-request.php' );
		require_once( $this->get_plugin_path() . '/src/api/requests/class-wc-avatax-api-void-request.php' );
		require_once( $this->get_plugin_path() . '/src/api/requests/class-wc-avatax-api-address-request.php' );
		require_once( $this->get_plugin_path() . '/src/api/requests/Companies_Request.php' );
		require_once( $this->get_plugin_path() . '/src/api/requests/Nexus_List_Request.php' );
		require_once( $this->get_plugin_path() . '/src/api/requests/Product_Classification_Systems_List_By_Company_Request.php' );
		require_once( $this->get_plugin_path() . '/src/api/requests/Query_Items_Request.php' );
		require_once( $this->get_plugin_path() . '/src/api/responses/class-wc-avatax-api-response.php' );
		require_once( $this->get_plugin_path() . '/src/api/responses/class-wc-avatax-api-utility-response.php' );
		require_once( $this->get_plugin_path() . '/src/api/responses/class-wc-avatax-api-subscriptions-response.php' );
		require_once( $this->get_plugin_path() . '/src/api/responses/class-wc-avatax-api-entity-use-code-response.php' );
		require_once( $this->get_plugin_path() . '/src/api/responses/class-wc-avatax-api-rate-response.php' );
		require_once( $this->get_plugin_path() . '/src/api/responses/class-wc-avatax-api-tax-response.php' );
		require_once( $this->get_plugin_path() . '/src/api/responses/class-wc-avatax-api-address-response.php' );
		require_once( $this->get_plugin_path() . '/src/api/responses/Companies_Response.php' );
		require_once( $this->get_plugin_path() . '/src/api/responses/Nexus_List_Response.php' );
		require_once( $this->get_plugin_path() . '/src/api/responses/Product_Classification_Systems_List_By_Company_Response.php' );
		require_once( $this->get_plugin_path() . '/src/api/responses/Query_Items_Response.php' );

		// loads the HS Classification API classes
		require_once( $this->get_plugin_path() . '/src/api/WC_AvaTax_HS_API.php' );
		require_once( $this->get_plugin_path() . '/src/api/requests/Abstract_HS_Classification_Request.php' );
		require_once( $this->get_plugin_path() . '/src/api/requests/HS_Classification_Create_Request.php' );
		require_once( $this->get_plugin_path() . '/src/api/requests/HS_Classification_Update_Request.php' );
		require_once( $this->get_plugin_path() . '/src/api/requests/HS_Classification_Get_Request.php' );
		require_once( $this->get_plugin_path() . '/src/api/responses/Abstract_HS_Classification_Response.php' );
		require_once( $this->get_plugin_path() . '/src/api/responses/HS_Classification_Create_Response.php' );
		require_once( $this->get_plugin_path() . '/src/api/responses/HS_Classification_Update_Response.php' );
		require_once( $this->get_plugin_path() . '/src/api/responses/HS_Classification_Get_Response.php' );
	}


}


/**
 * Returns the One True Instance of WC_AvaTax.
 *
 * @since 1.0.0
 *
 * @return WC_AvaTax
 */
function wc_avatax() {

	return WC_AvaTax::instance();
}

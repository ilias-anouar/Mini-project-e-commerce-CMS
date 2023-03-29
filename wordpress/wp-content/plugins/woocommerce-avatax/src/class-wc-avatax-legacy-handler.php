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
 * Handle the migration from the legacy plugin.
 *
 * @since 1.0.0
 */
class WC_AvaTax_Legacy_Handler {


	/** @var WC_AvaTax_Legacy The single instance of the class */
	protected static $instance = null;

	/** @var string This plugin's file path */
	protected $plugin_path = 'woocommerce-avatax/woocommerce-avatax.php';

	/** @var string The legacy plugin file path */
	protected $legacy_path = 'woocommerce-avalara/woocommerce-avalara.php';

	/**
	 * Hidden constructor
	 *
	 * @since 1.0.0
	 */
	private function __construct() {

		// Deactivate the legacy AvaTax plugin if it exists
		add_action( 'activate_' . $this->plugin_path, array( $this, 'deactivate_legacy_plugin' ) );

		// Display a persistent notice when the legacy plugin is active alongside ours
		add_action( 'admin_notices', array( $this, 'display_legacy_notice' ) );
	}


	/**
	 * Instantiate the class singleton
	 *
	 * @since 1.0.0
	 * @return WC_AvaTax_Legacy singleton instance
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}


	/**
	 * Deactivate the legacy plugin when this is activated.
	 *
	 * @since 1.0.0
	 */
	public function deactivate_legacy_plugin( $plugin ) {

		if ( in_array( $this->legacy_path, $this->get_active_plugins() ) || array_key_exists( $this->legacy_path, $this->get_active_plugins() ) ) {

			deactivate_plugins( $this->legacy_path );

			update_option( 'wc_avatax_legacy_deactivated', 'yes' );
		}
	}


	/**
	 * Display a persistent notice when the legacy plugin is active alongside ours.
	 *
	 * @since 1.0.0
	 */
	public function display_legacy_notice() {

		echo '<div class="error">';
			echo '<p>';
				/* translators: Placeholders: %1$s - <strong> tag, %2$s - </strong> tag */
				printf(
					__( '%1$sWooCommerce AvaTax%2$s is inactive. Please deactivate %1$sAvaTax Integration for WooCommerce%2$s to continue.', 'woocommerce-avatax' ),
					'<strong>',
					'</strong>'
				);
			echo '</p>';
		echo '</div>';
	}


	/**
	 * Get the currently active plugins.
	 *
	 * @since 1.0.0
	 * @return array The active plugins.
	 */
	private function get_active_plugins() {

		$active_plugins = (array) get_option( 'active_plugins', array() );

		if ( is_multisite() ) {
			$active_plugins = array_merge( $active_plugins, get_site_option( 'active_sitewide_plugins', array() ) );
		}

		return $active_plugins;
	}
}

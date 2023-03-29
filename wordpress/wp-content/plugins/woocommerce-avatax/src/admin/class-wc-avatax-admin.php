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
 * Set up the AvaTax admin.
 *
 * @since 1.0.0
 */
class WC_AvaTax_Admin {


	/** @var \WC_AvaTax_Settings settings handler */
	public $settings;

	/** @var \WC_AvaTax_Product_Admin product handler */
	public $product;


	/**
	 * Construct the class.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		$this->includes();

		// load admin scripts and styles
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts_styles' ) );

		// load modal template
		add_action( 'admin_footer', [ $this, 'output_sync_modal_template' ] );

		// add the product category tax code fields
		add_action( 'product_cat_add_form_fields',  array( $this, 'add_category_code_fields' ) );
		add_action( 'product_cat_edit_form_fields', array( $this, 'edit_category_code_fields' ) );

		// save the product category tax code fields
		// the same is done when creating a new category from WC_AvaTax_AJAX::save_category_tax_code_field
		add_action( 'edit_product_cat', array( $this, 'save_category_code_fields' ) );

		// Add product category tax code column
		add_filter( 'manage_edit-product_cat_columns',  array( $this, 'add_category_code_columns' ) );
		add_filter( 'manage_product_cat_custom_column', array( $this, 'display_category_code_columns' ), 10, 3 );

		// Add the VAT ID information to the order billing information
		add_action( 'woocommerce_admin_billing_fields', array( $this, 'add_admin_order_vat_id' ) );

		// Hide our custom line item meta from the order admin
		add_filter( 'woocommerce_hidden_order_itemmeta', array( $this, 'hide_order_item_meta' ) );

		// Add the item tax rate input to the order admin
		add_action( 'woocommerce_admin_order_item_values', array( $this, 'add_order_item_tax_rate' ), 10, 3 );

		// add a hidden input to the order items form to indicate landed cost for an order
		add_action( 'woocommerce_order_item_add_action_buttons', array( $this, 'add_order_calculated_field' ) );

		// Add a "Send to Avalara" action to the order action options if calculation is enabled
		if ( wc_avatax()->get_tax_handler()->is_available() ) {
			add_action( 'woocommerce_order_actions', array( $this, 'add_order_action' ) );
		}

		// Add and save the customer tax settings fields
		add_action( 'show_user_profile',        array( $this, 'add_tax_meta_fields' ), 15, 1 );
		add_action( 'edit_user_profile',        array( $this, 'add_tax_meta_fields' ), 15, 1 );
		add_action( 'personal_options_update',  array( $this, 'save_tax_meta_fields' ) );
		add_action( 'edit_user_profile_update', array( $this, 'save_tax_meta_fields' ) );
	}


	/**
	 * Include the admin files.
	 *
	 * @since 1.0.0
	 */
	public function includes() {

		// settings handler
		require_once( wc_avatax()->get_plugin_path() . '/src/admin/class-wc-avatax-settings.php' );
		$this->settings = new WC_AvaTax_Settings;

		// product handler
		require_once( wc_avatax()->get_plugin_path() . '/src/admin/class-wc-avatax-product-admin.php' );
		$this->product = new WC_AvaTax_Product_Admin;
	}


	/**
	 * Load the admin scripts and styles.
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 *
	 * @param string $hook_suffix The current screen suffix
	 */
	public function enqueue_scripts_styles( $hook_suffix ) {

		// only enqueue the scripts and styles on the settings screen or edit/new order screens
		if ( wc_avatax()->is_plugin_settings() || ( 'product' === get_post_type() && 'edit.php' === $hook_suffix ) || ( 'shop_order' === get_post_type() && ( 'post.php' === $hook_suffix || 'post-new.php' === $hook_suffix ) ) ) {

			wp_enqueue_script( 'wc-backbone-modal', null, [ 'backbone' ] );
			wp_enqueue_script( 'wc-avatax-admin', wc_avatax()->get_plugin_url() . '/assets/js/admin/wc-avatax-admin.min.js', [ 'jquery', 'wc-backbone-modal' ], \WC_AvaTax::VERSION, true );

			wp_localize_script( 'wc-avatax-admin', 'wc_avatax_admin', [
				'address_nonce'         => wp_create_nonce( 'wc_avatax_validate_origin_address' ),
				'assets_url'            => esc_url( wc_avatax()->get_framework_assets_url() ),
				'ajax_url'              => admin_url( 'admin-ajax.php' ),
				'sync_state'            => wc_avatax()->get_landed_cost_sync_handler()->is_syncing_active() ? 'on' : 'off',
				'sync_nonce'            => wp_create_nonce( 'wc_avatax_toggle_cross_border_sync' ),
				'resync_nonce'          => wp_create_nonce( 'wc_avatax_resync_products_with_errors' ),
				'refund_ays'            => __( 'Heads up! AvaTax does not support tax-rate-based refunds. If taxes are refunded partially, the amount will be distributed across all tax rates.', 'woocommerce-avatax' ),
				'product_sync_modal'    => [
					'disconnect_title'  => __( 'Are you sure?', 'woocommerce-avatax' ),
					'disconnect_body'   => __( 'Disconnecting will immediately end the product sync between your store and AvaTax. Any new or updated products will not be updated in AvaTax, which may impact your cross-border tax calculations. Do you still want to disconnect?', 'woocommerce-avatax' ),
					'disconnect_action' => __( 'Disconnect', 'woocommerce-avatax' ),
					'disconnect_cancel' => __( 'Cancel', 'woocommerce-avatax' ),
					'connect_title'     => __( 'Syncing is in progress', 'woocommerce-avatax' ),
					'connect_body'      => __( 'Sync in progress. You can safely leave this screen.', 'woocommerce-avatax' ),
				],
				'to_disable' => [
					'stop_current_sync' => __( 'Please stop the current sync to disable cross-border classification.', 'woocommerce-avatax' ),
				],
			] );

			wp_enqueue_style( 'wc-avatax-admin', wc_avatax()->get_plugin_url() . '/assets/css/admin/wc-avatax-admin.min.css', WC_AvaTax::VERSION );
		}
	}


	/**
	 * Includes an export modal template in the plugin settings pages.
	 *
	 * @internal
	 *
	 * @since 1.13.0
	 */
	public function output_sync_modal_template() {

		if ( ! wc_avatax()->is_plugin_settings() ) {
			return;
		}

		include_once( wc_avatax()->get_plugin_path() . '/src/admin/views/html-sync-modal.php' );
	}


	/**
	 * Adds landed costs settings.
	 *
	 * @internal
	 *
	 * @since 1.5.0
	 * @deprecated 1.16.0
	 *
	 * @param array|mixed $settings
	 * @return array|mixed
	 */
	public function add_settings_pages( $settings ) {

		wc_deprecated_function( __METHOD__, '1.16.0' );

		return $settings;
	}


	/**
	 * Display the tax code fields on the add product category screen.
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 */
	public function add_category_code_fields() {

		// tax code
		include( wc_avatax()->get_plugin_path() . '/src/admin/views/html-field-add-category-tax-code.php' );
	}


	/**
	 * Display the tax code fields on the edit product category screen.
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 * @param object $term current term object
	 */
	public function edit_category_code_fields( $term ) {

		$tax_code = get_term_meta( $term->term_id, 'wc_avatax_tax_code', true );

		include( wc_avatax()->get_plugin_path() . '/src/admin/views/html-field-edit-category-tax-code.php' );
	}


	/**
	 * Save the category tax code fields.
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 *
	 * @param int $term_id current term ID
	 */
	public function save_category_code_fields( $term_id ) {

		$tax_code = sanitize_text_field( Framework\SV_WC_Helper::get_posted_value( 'wc_avatax_category_tax_code' ) );

		update_term_meta( $term_id, 'wc_avatax_tax_code', $tax_code );
	}


	/**
	 * Add the tax code columns to category admin.
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 *
	 * @param array $columns existing category columns
	 * @return array $columns
	 */
	public function add_category_code_columns( $columns ) {

		$columns['tax_code'] = __( 'Tax Code', 'woocommerce-avatax' );

		return $columns;
	}


	/**
	 * Display the tax code in its column.
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 *
	 * @param string $content column content
	 * @param string $column current column slug
	 * @param int $id category ID
	 * @return string $columns amended column content
	 */
	public function display_category_code_columns( $content, $column, $id ) {

		if ( 'tax_code' === $column ) {
			$content .= get_term_meta( $id, 'wc_avatax_tax_code', true );
		}

		return $content;
	}


	/**
	 * Add the VAT ID information to the order billing information.
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 *
	 * @param array $fields The existing billing fields
	 * @return array
	 */
	public function add_admin_order_vat_id( $fields ) {

		$fields['wc_avatax_vat_id'] = array(
			'label' => __( 'VAT ID', 'woocommerce-avatax' ),
		);

		return $fields;
	}


	/**
	 * Hide our custom line item meta from the order admin.
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 *
	 * @param array $hidden_meta The hidden line item keys.
	 * @return array $hidden_meta
	 */
	public function hide_order_item_meta( $hidden_meta ) {

		$hidden_meta[] = '_wc_avatax_code';
		$hidden_meta[] = '_wc_avatax_rate';
		$hidden_meta[] = '_wc_avatax_hs_code';

		return $hidden_meta;
	}


	/**
	 * Add the item tax rate input to the order admin.
	 *
	 * @since 1.0.0
	 * @param WC_Product $product The product object.
	 * @param array $item The item meta.
	 * @param int $item_id The item ID.
	 */
	public function add_order_item_tax_rate( $product, $item, $item_id ) {

		// Only add this value if a tax rate was set for the item
		if ( ( ! is_array( $item ) && ! $item instanceof WC_Order_Item_Tax ) || empty( $item['wc_avatax_rate'] ) ) {
			return;
		}

		echo '<input
				class="wc_avatax_refund_line_rate"
				name="wc_avatax_refund_line_rate[' . absint( $item_id ) . ']"
				value="' . (float) $item['wc_avatax_rate'] . '"
				type="hidden"
			/>';
	}


	/**
	 * Adds a hidden input to the order items form to indicate AvaTax calculation for an order.
	 *
	 * This primarily used to display a warning to users trying to partially refund AvaTax transactions, as that's currently not supported.
	 *
	 * @internal
	 *
	 * @since 1.6.4
	 *
	 * @param \WC_Order $order order object
	 */
	public function add_order_calculated_field( $order ) {

		?>
		<input name="wc_avatax_calculated" type="hidden" value="<?php echo wc_avatax()->get_order_handler()->is_order_posted( $order ) ? 'yes' : 'no'; ?>"/>
		<?php
	}


	/**
	 * Add a "Send to Avalara" action to the order action options.
	 *
	 * @since 1.0.0
	 * @global WC_Order $theorder The current order object.
	 * @param array $actions The available order actions.
	 * @return array $actions
	 */
	public function add_order_action( $actions ) {
		global $theorder;

		// Only add the action if the order is ready for sending
		if ( wc_avatax()->get_order_handler()->is_order_ready( $theorder ) ) {
			$actions['wc_avatax_send'] = __( 'Send to Avalara', 'woocommerce-avatax' );
		}

		return $actions;
	}


	/**
	 * Adds the customer tax settings fields.
	 *
	 * @since 1.0.0
	 *
	 * @param \WP_User $user user object
	 */
	public function add_tax_meta_fields( $user ) {

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		// base entity/use codes and their descriptions
		// below we try and get the same codes from the API, but this acts as a fallback in case there is a failure
		// note: O is intentionally absent
		$entity_use_codes = array(
			'A' => __( 'Federal government', 'woocommerce-avatax' ),
			'B' => __( 'State government', 'woocommerce-avatax' ),
			'C' => __( 'Tribe / Status Indian / Indian Band', 'woocommerce-avatax' ),
			'D' => __( 'Foreign diplomat', 'woocommerce-avatax' ),
			'E' => __( 'Charitable or benevolent organization', 'woocommerce-avatax' ),
			'F' => __( 'Religious organization', 'woocommerce-avatax' ),
			'G' => __( 'Resale', 'woocommerce-avatax' ),
			'H' => __( 'Commercial agricultural production', 'woocommerce-avatax' ),
			'I' => __( 'Industrial production / manufacturer', 'woocommerce-avatax' ),
			'J' => __( 'Direct pay permit', 'woocommerce-avatax' ),
			'K' => __( 'Direct mail', 'woocommerce-avatax' ),
			'L' => __( 'Other', 'woocommerce-avatax' ),
			'M' => __( 'Educational organization', 'woocommerce-avatax' ),
			'N' => __( 'Local government', 'woocommerce-avatax' ),
			'P' => __( 'Commercial aquaculture', 'woocommerce-avatax' ),
			'Q' => __( 'Commercial Fishery', 'woocommerce-avatax' ),
			'R' => __( 'Non-resident', 'woocommerce-avatax' ),
			'MED1' => __( 'US MDET with exempt sales tax', 'woocommerce-avatax' ),
			'MED2' => __( 'US MDET with taxable sales tax', 'woocommerce-avatax' ),
		);

		try {

			$response = wc_avatax()->get_api()->get_entity_use_codes();

			// append the official code name to the nice label if found, otherwise just add to the list as-is
			foreach ( $response->get_codes() as $code => $name ) {

				$label = isset( $entity_use_codes[ $code ] ) ? "{$entity_use_codes[ $code ]} ({$name})" : $name;

				$entity_use_codes[ $code ] = $label;
			}

		} catch ( Framework\SV_WC_Plugin_Exception $exception ) {

			if ( wc_avatax()->logging_enabled() ) {
				wc_avatax()->log( $exception->getMessage() );
			}
		}

		/**
		 * Filters the customer usage types.
		 *
		 * @since 1.0.0
		 *
		 * @param array $entity_use_codes entity/use codes, formatted as $code => $description
		 */
		$entity_use_codes = apply_filters( 'wc_avatax_customer_usage_types', $entity_use_codes );

		$selected_code = get_user_meta( $user->ID, 'wc_avatax_tax_exemption', true );

		include( wc_avatax()->get_plugin_path() . '/src/admin/views/html-edit-user-tax-fields.php' );
	}


	/**
	 * Save the customer tax settings.
	 *
	 * @since 1.0.0
	 * @param int $user_id The user ID.
	 */
	public function save_tax_meta_fields( $user_id ) {

		// Save the tax exemption code
		update_user_meta( $user_id, 'wc_avatax_tax_exemption', wc_clean( $_POST['wc_avatax_user_exemption'] ) );
	}


}

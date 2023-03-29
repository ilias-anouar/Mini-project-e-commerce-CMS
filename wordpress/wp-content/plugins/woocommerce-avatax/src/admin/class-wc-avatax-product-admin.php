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
class WC_AvaTax_Product_Admin {


	/**
	 * Construct the class.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		// display the tax code field
		add_action( 'woocommerce_product_options_tax', array( $this, 'display_tax_code_field' ) );

		// save the product field values
		add_action( 'woocommerce_process_product_meta', array( $this, 'save_meta' ), 10, 2 );

		// display the quick edit fields
		add_action( 'manage_product_posts_custom_column', array( $this, 'add_quick_edit_inline_values' ), 10 );
		add_action( 'woocommerce_product_quick_edit_end',  array( $this, 'display_quick_edit_fields' ) );

		// display and save the bulk edit fields
		add_action( 'woocommerce_product_bulk_edit_end', array( $this, 'display_bulk_edit_fields' ) );
		add_action( 'woocommerce_product_bulk_edit_save', array( $this, 'save_bulk_edit_fields' ) );

		// filter the product table query when a specific HTS code is desired
		add_filter( 'parse_query', array( $this, 'filter_by_hts_code' ) );
	}


	/** Tax Code Methods ******************************************************/


	/**
	 * Displays the tax code field.
	 *
	 * @internal
	 *
	 * @since 1.5.0
	 */
	public function display_tax_code_field() {

		woocommerce_wp_text_input(
			array(
				'id'            => '_wc_avatax_code',
				'wrapper_class' => 'hide_if_external',
				'label'         => __( 'Tax Code', 'woocommerce-avatax' ),
				'placeholder'   => wc_avatax()->get_tax_handler()->get_default_product_tax_code(),
			)
		);
	}


	/** HTS Code Methods ******************************************************/


	/**
	 * Displays the HTS code field.
	 *
	 * @internal
	 *
	 * @since 1.5.0
	 * @deprecated 1.16.0
	 */
	public function display_hts_code_field() {

		wc_deprecated_function( __METHOD__, '1.16.0' );
	}


	/** General Methods *******************************************************/


	/**
	 * Saves the product field values.
	 *
	 * @internal
	 *
	 * @since 1.5.0
	 *
	 * @param int $post_id product ID
	 */
	public function save_meta( $post_id ) {

		update_post_meta( $post_id, '_wc_avatax_code', sanitize_text_field( Framework\SV_WC_Helper::get_posted_value( '_wc_avatax_code' ) ) );
	}


	/**
	 * Adds markup for the custom meta values so Quick Edit can fill the inputs.
	 *
	 * @internal
	 *
	 * @since 1.5.0
	 *
	 * @param string $column the current column slug
	 */
	public function add_quick_edit_inline_values( $column ) {
		global $post;

		$product = is_object( $post ) ? wc_get_product( $post->ID ) : null;

		if ( $product && 'name' === $column ) : ?>

			<div id="wc_avatax_inline_<?php echo esc_attr( $product->get_id() ); ?>" class="hidden">
				<div class="tax_code"><?php echo esc_html( $product->get_meta( '_wc_avatax_code' ) ); ?></div>
			</div>

		<?php endif;
	}


	/**
	 * Displays the quick edit fields.
	 *
	 * @internal
	 *
	 * @since 1.5.0
	 */
	public function display_quick_edit_fields() {

		include( wc_avatax()->get_plugin_path() . '/src/admin/views/html-field-product-tax-code-quick-edit.php' );
	}


	/**
	 * Displays the bulk edit fields.
	 *
	 * @internal
	 *
	 * @since 1.5.0
	 */
	public function display_bulk_edit_fields() {

		include( wc_avatax()->get_plugin_path() . '/src/admin/views/html-field-product-tax-code-bulk-edit.php' );
	}


	/**
	 * Saves the tax code bulk edit field.
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 *
	 * @param \WC_Product $product product object
	 */
	public function save_bulk_edit_fields( $product ) {

		if ( ! empty( $_REQUEST['change_wc_avatax_code'] ) ) {

			$new_code     = sanitize_text_field( $_REQUEST['_wc_avatax_code'] );
			$current_code = $product->get_meta( '_wc_avatax_code' );

			// update to new tax code if different than current tax code
			if ( isset( $new_code ) && $new_code !== $current_code ) {
				update_post_meta( $product->get_id(), '_wc_avatax_code', $new_code );
			}
		}
	}


	/**
	 * Filters the product table query when a specific HTS code is desired.
	 *
	 * @internal
	 *
	 * @since 1.5.0
	 *
	 * @param \WP_Query $query query object
	 */
	public function filter_by_hts_code( $query ) {
		global $typenow;

		if ( 'product' === $typenow && Framework\SV_WC_Helper::get_requested_value( 'wc_avatax_hts_code' ) ) {
			$query->query_vars['meta_value'] = Framework\SV_WC_Helper::get_requested_value( 'wc_avatax_hts_code' );
			$query->query_vars['meta_key']   = '_wc_avatax_hts_code';
		}
	}


}

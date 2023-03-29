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

/**
 * Displays the address settings fields.
 *
 * @type string $id input ID
 * @type string $label input label
 * @type string $tooltip_html helpful tooltip HTML
 * @type string $description helpful tooltip HTML
 * @type bool $disabled whether this component must be disabled or not
 * @type bool $syncing whether the landed cost syncing is active or not
 */

if ( $syncing ) {
	$button_label = __( 'Disconnect', 'woocommerce-avatax' );
	$toggle_label = __( 'Connect', 'woocommerce-avatax' );
} else {
	$button_label = __( 'Connect', 'woocommerce-avatax' );
	$toggle_label = __( 'Disconnect', 'woocommerce-avatax' );
}

?>
<tr style="vertical-align: top;">

	<th scope="row" class="titledesc">
		<label for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $label ); ?></label>
		<?php echo $tooltip_html; ?>
	</th>

	<td class="forminp forminp-product-sync" data-address-id="<?php echo esc_attr( $id ); ?>">

		<p class="wc-avatax-product-sync validate">
			<span class="syncing" style="<?php echo $syncing ? '' : 'display: none;'; ?>"><?php esc_html_e( 'Your catalog is syncing to AvaTax.', 'woocommerce-avatax' ); ?></span>
			<span class="connection-failed" style="display: none;"><?php esc_html_e( 'Cannot connect to Item Classification API. Please check your credentials above & ensure you have a valid subscription. Contact your Customer Account Manager if you need to setup Item Classification.', 'woocommerce-avatax' ); ?></span>
			<button type="button" id="wc-avatax-product-sync-button" class="button-secondary<?php echo esc_attr( $disabled ? ' disabled' : '' ); ?>" <?php echo disabled( $disabled ); ?> style="display: block; margin-top: 4px;" data-toggle-label="<?php echo esc_attr( $toggle_label ); ?>"><?php echo esc_html( $button_label ); ?></button>
			<?php echo $description; ?>
		</p>

	</td>
</tr>

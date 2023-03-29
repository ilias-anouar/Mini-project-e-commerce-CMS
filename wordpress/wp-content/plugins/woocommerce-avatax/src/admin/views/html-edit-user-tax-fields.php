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
 * Display the user tax settings fields.
 *
 * @type array $entity_use_codes usage types for exemption, as `$code => $label`
 * @type string $selected_code saved code for the current user
 */
?>

<h3><?php esc_html_e( 'Customer Tax Settings', 'woocommerce-avatax' ); ?></h3>

<table class="form-table">
	<tr>
		<th><label for="wc_avatax_user_exemption"><?php esc_html_e( 'Tax Exemption', 'woocommerce-avatax' ); ?></label></th>
		<td>
			<select name="wc_avatax_user_exemption" id="wc_avatax_user_exemption" style="width: 25em;">
				<option value=""><?php esc_attr_e( 'No exemption', 'woocommerce-avatax' ); ?></option>
				<?php foreach ( $entity_use_codes as $value => $label ) : ?>
					<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $selected_code, $value, true ); ?>>
						<?php echo esc_attr( $label ); ?>
					</option>
				<?php endforeach; ?>
			</select>
		</td>
	</tr>
</table>

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
 * @type array  $values address setting values
 * @type array  $countries available countries
 * @type string $selected_country stored country
 */
?>
<tr valign="top">

	<th scope="row" class="titledesc">
		<label for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $label ); ?></label>
		<?php echo $tooltip_html; ?>
	</th>

	<td class="forminp forminp-address" data-address-id="<?php echo esc_attr( $id ); ?>">

		<p class="wc-avatax-address-field">
			<label for="<?php echo esc_attr( $id ); ?>_address_1"><?php esc_html_e( 'Street Address', 'woocommerce-avatax' ); ?></label>
			<input name="<?php echo esc_attr( $id ); ?>[address_1]" id="<?php echo esc_attr( $id ); ?>_address_1" type="text" value="<?php echo ( isset( $values['address_1'] ) ) ? esc_attr( $values['address_1'] ) : ''; ?>" style="min-width:300px;" />
		</p>

		<p class="wc-avatax-address-field">
			<label for="<?php echo esc_attr( $id ); ?>_city"><?php esc_html_e( 'City/Town', 'woocommerce-avatax' ); ?></label>
			<input name="<?php echo esc_attr( $id ); ?>[city]" id="<?php echo esc_attr( $id ); ?>_city" value="<?php echo ( isset( $values['city'] ) ) ? esc_attr( $values['city'] ) : ''; ?>" type="text" />
		</p>

		<p class="wc-avatax-address-field">
			<label for="<?php echo esc_attr( $id ); ?>_state"><?php esc_html_e( 'State/Region', 'woocommerce-avatax' ); ?></label>
			<input name="<?php echo esc_attr( $id ); ?>[state]" id="<?php echo esc_attr( $id ); ?>_state" value="<?php echo ( isset( $values['state'] ) ) ? esc_attr( $values['state'] ) : ''; ?>" type="text" />
		</p>

		<p class="wc-avatax-address-field">
			<label for="<?php echo esc_attr( $id ); ?>_postcode"><?php esc_html_e( 'Zip/Postcode', 'woocommerce-avatax' ); ?></label>
			<input name="<?php echo esc_attr( $id ); ?>[postcode]" id="<?php echo esc_attr( $id ); ?>_postcode" value="<?php echo ( isset( $values['postcode'] ) ) ? esc_attr( $values['postcode'] ) : ''; ?>" type="text" />
		</p>

		<p class="wc-avatax-address-field">
			<label for="<?php echo esc_attr( $id ); ?>_country"><?php esc_html_e( 'Country', 'woocommerce-avatax' ); ?></label>
			<select id="<?php echo esc_attr( $id ); ?>_country" class="wc-enhanced-select" name="<?php echo esc_attr( $id ); ?>[country]">
				<?php foreach ( $countries as $code => $label ) : ?>
					<option value="<?php echo esc_attr( $code ); ?>" <?php selected( $code, $selected_country ); ?>><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</select>
		</p>

		<p class="wc-avatax-address-field validate">
			<button class="wc-avatax-address-validate button-secondary"><?php esc_attr_e( 'Validate Address', 'woocommerce-avatax' ); ?></button>
			<span class="indicator"></span>
		</p>

	</td>
</tr>

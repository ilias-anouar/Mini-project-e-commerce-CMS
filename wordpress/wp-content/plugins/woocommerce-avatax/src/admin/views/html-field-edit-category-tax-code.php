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
 * Display the Edit Category tax code field.
 *
 * @type string $tax_code stored tax code for the category
 */
?>
<tr class="form-field">
	<th scope="row" valign="top">
		<label for="wc_avatax_category_tax_code"><?php esc_html_e( 'Tax code', 'woocommerce-avatax' ); ?></label>
	</th>
	<td>
		<input id="wc_avatax_category_tax_code" name="wc_avatax_category_tax_code" value="<?php echo esc_attr( $tax_code ); ?>" type="text" />
	</td>
</tr>

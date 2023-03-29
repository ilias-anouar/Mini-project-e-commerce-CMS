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
 * Display the Bulk Edit Tax Code field.
 */
?>

<div class="inline-edit-group">
	<label class="alignleft">
		<span class="title"><?php esc_html_e( 'Tax Code', 'woocommerce-avatax' ); ?></span>
			<span class="input-text-wrap">
				<select class="change_wc_avatax_code change_to" name="change_wc_avatax_code">
					<option value=""><?php esc_html_e( '— No Change —', 'woocommerce-avatax' ); ?></option>
					<option value="1"><?php esc_html_e( 'Change to:', 'woocommerce-avatax' ); ?></option>
				</select>
			</span>
	</label>
	<label class="change-input">
		<input type="text" name="_wc_avatax_code" class="text wc_avatax_code" placeholder="<?php esc_attr_e( 'Tax Code', 'woocommerce-avatax' ); ?>" value="" />
	</label>
</div>

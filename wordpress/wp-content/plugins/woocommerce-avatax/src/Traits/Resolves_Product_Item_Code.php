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

namespace SkyVerge\WooCommerce\AvaTax\Traits;

defined('ABSPATH') or exit;

use WC_Product;

/**
 * A trait that helps resolve the AvaTax item code for a WooCommerce Product.
 *
 * @since 1.16.0
 */
trait Resolves_Product_Item_Code {


	/**
	 * Determines the item code to use for this product.
	 *
	 * TODO: refactor this to support product SKU in the future, consider filtering the result {IT 2021-12-18}
	 *
	 * @since 1.16.0
	 *
	 * @param WC_Product $product
	 * @return int the item code
	 */
	public function resolve_product_item_code( WC_Product $product ) : int {

		return $product->get_id() ?? 0;
	}


}

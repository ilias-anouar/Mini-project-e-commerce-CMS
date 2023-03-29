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

namespace SkyVerge\WooCommerce\AvaTax\API\Requests;

defined( 'ABSPATH' ) or exit;

use SkyVerge\WooCommerce\AvaTax\API\Models\HS_Classification_Model;
use SkyVerge\WooCommerce\AvaTax\API\Requests\Abstract_HS_Classification_Request;

/**
 * The UPDATE HS classification API request.
 *
 * @since 1.13.0
 */
class HS_Classification_Update_Request extends Abstract_HS_Classification_Request {


	/**
	 * Initializes the request to update the HS classification of an item.
	 *
	 * @since 1.13.0
	 *
	 * @param HS_Classification_Model $classification
	 */
	public function __construct( HS_Classification_Model $classification ) {

		parent::__construct( $classification );

		$this->path .= '/' . $classification->get_id();
		$this->method = 'PUT';
		$this->data = $this->get_classification_params();
	}


}

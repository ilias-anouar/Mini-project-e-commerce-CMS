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

namespace SkyVerge\WooCommerce\AvaTax;

defined( 'ABSPATH' ) or exit;

use SkyVerge\WooCommerce\PluginFramework\v5_10_14 as Framework;

/**
 * This class represents a product in the sync queue.
 *
 * @since 1.13.0
 */
class Landed_Cost_Sync_Enqueued_Product {


	/** @var int the WooCommerce product ID */
	private $product_id;

	/** @var string destination country the product classification is being requested for */
	private $country_of_destination;

	/** @var int timestamp to delay product syncing */
	private $timestamp;

	/** @var string sync action to be executed for the product */
	private $action;

	/** @var string error message after an API request */
	private $error_message;

	/** @var string $resolution resolution message with an explanation provided by Avalara when the product cannot be classified */
	private $resolution;


	/**
	 * Product constructor.
	 *
	 * @since 1.13.0
	 *
	 * @param array $data optional: builds the properties from an array of data
	 */
	public function __construct( array $data = [] ) {

		foreach ( $data as $property => $value ) {

			if ( property_exists( $this, $property ) )  {

				$this->$property = $value;
			}
		}
	}


	/**
	 * Gets the product ID.
	 *
	 * @since 1.13.0
	 *
	 * @return int
	 */
	public function get_product_id() : int {

		return $this->product_id;
	}


	/**
	 * Sets the product ID.
	 *
	 * @since 1.13.0
	 *
	 * @param int $value
	 * @return self
	 */
	public function set_product_id( int $value ) : Landed_Cost_Sync_Enqueued_Product {

		$this->product_id = $value;

		return $this;
	}


	/**
	 * Gets the code for the country of destination.
	 *
	 * @since 1.13.0
	 *
	 * @return string
	 */
	public function get_country_of_destination() : string {

		return $this->country_of_destination;
	}


	/**
	 * Sets the country of destination.
	 *
	 * @since 1.13.0
	 *
	 * @param string $value
	 * @return self
	 */
	public function set_country_of_destination( string $value ) : Landed_Cost_Sync_Enqueued_Product {

		$this->country_of_destination = $value;

		return $this;
	}


	/**
	 * Gets the delay timestamp.
	 *
	 * @since 1.13.0
	 *
	 * @return int|null
	 */
	public function get_timestamp() {

		return is_numeric( $this->timestamp ) ? (int) $this->timestamp : null;
	}


	/**
	 * Sets the delay timestamp.
	 *
	 * @since 1.13.0
	 *
	 * @param int $value
	 * @return self
	 */
	public function set_timestamp( int $value ) : Landed_Cost_Sync_Enqueued_Product {

		$this->timestamp = $value;

		return $this;
	}


	/**
	 * Gets the sync action for the product.
	 *
	 * @since 1.13.0
	 *
	 * @return string
	 */
	public function get_action() : string {

		return $this->action;
	}


	/**
	 * Sets the sync action.
	 *
	 * @since 1.13.0
	 *
	 * @param string $value
	 * @return self
	 */
	public function set_action( string $value ) : Landed_Cost_Sync_Enqueued_Product {

		$this->action = $value;

		return $this;
	}


	/**
	 * Gets the error message.
	 *
	 * @since 1.13.0
	 *
	 * @return string
	 */
	public function get_error_message() : string {

		return $this->error_message;
	}


	/**
	 * Sets the error message.
	 *
	 * @since 1.13.0
	 *
	 * @param string $value
	 * @return self
	 */
	public function set_error_message( string $value ) : Landed_Cost_Sync_Enqueued_Product {

		$this->error_message = $value;

		return $this;
	}


	/**
	 * Gets the resolution.
	 *
	 * @since 1.13.0
	 *
	 * @return string
	 */
	public function get_resolution() : string {

		return $this->resolution;
	}


	/**
	 * Sets the resolution.
	 *
	 * @since 1.13.0
	 *
	 * @param string $value
	 * @return self
	 */
	public function set_resolution( string $value ) : Landed_Cost_Sync_Enqueued_Product {

		$this->resolution = $value;

		return $this;
	}


	/**
	 * Gets an array representation of the class.
	 *
	 * @since 1.13.0
	 *
	 * @return array
	 */
	public function to_array() : array {

		$array = [];

		foreach ( $this as $property => $value ) {
			$array[ $property ] = $value;
		}

		return $array;
	}


}

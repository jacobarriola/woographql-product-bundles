<?php


namespace WPGraphQL\WooCommerce\ProductBundles;

use GraphQL\Error\UserError;
use WPGraphQL\WooCommerce\Data\Mutation\Cart_Mutation;

class ProductBundleAddToCart {
	/**
	 * Registers mutation
	 */
	public static function register_mutation() {
		register_graphql_mutation(
			'addToCartProductBundle',
			[
				'inputFields'         => self::get_input_fields(),
				'outputFields'        => self::get_output_fields(),
				'mutateAndGetPayload' => self::mutate_and_get_payload(),
			]
		);
	}

	/**
	 * Defines the mutation input field configuration
	 *
	 * @return array
	 */
	public static function get_input_fields() {
		return [
			'productId' => [
				'type'        => [ 'non_null' => 'Int' ],
				'description' => 'Product ID of the bundle to add to the cart',
			],
			'quantity'  => [
				'type'        => 'Int',
				'description' => 'Quantity of the bundle',
			],
			'extraData' => [
				'type'        => 'String',
				'description' => 'JSON string representation of bundle item data',
			],
		];
	}

	/**
	 * Defines the mutation output field configuration
	 *
	 * @return array
	 */
	public static function get_output_fields() {
		return [
			'cartItem' => [
				'type'    => 'CartItem',
				'resolve' => function ( $payload ) {
					$item = \WC()->cart->get_cart_item( $payload['key'] );

					return $item;
				},
			],
			'cart'     => Cart_Mutation::get_cart_field( true ),
		];
	}

	/**
	 * Defines the mutation data modification closure.
	 *
	 * @return callable
	 */
	public static function mutate_and_get_payload() {
		return function ( $input ) {
			Cart_Mutation::check_session_token();

			// Retrieve product database ID if relay ID provided.
			if ( empty( $input['productId'] ) ) {
				throw new UserError( 'No product ID provided' );
			}

			// Guard against empty bundle items.
			if ( empty( $input['extraData'] ) ) {
				throw new UserError( 'No bundle items provided' );
			}

			if ( ! \wc_get_product( $input['productId'] ) ) {
				throw new UserError( 'No product found matching the ID provided' );
			}

			if ( ! function_exists( 'WC_PB' ) ) {
				throw new UserError( 'Class WC_PB does not exist. Ensure that the Product Bundle plugin is active.' );
			}

			// Add item to cart and get item key.
			$cart_item_key = WC_PB()->cart->add_bundle_to_cart(
				$input['productId'],
				$input['quantity'] ? $input['quantity'] : 1,
				json_decode( $input['extraData'], true )
			);

			if ( empty( $cart_item_key ) ) {
				throw new UserError( 'Failed to add cart item. Please check input.' );
			}

			if ( is_wp_error( $cart_item_key ) ) {
				if ( $cart_item_key->error_data['woocommerce_bundle_configuration_invalid']['notices'] ) {
					$notice = end( $cart_item_key->error_data['woocommerce_bundle_configuration_invalid']['notices'] );

					// Bail if notice is not available
					if ( empty( $notice['notice'] ) ) {
						throw new UserError( $cart_item_key->get_error_message() );
					}

					// There is not filterable way to alter the error message. Let's hack this instead.
					$message_offset = strpos( $notice['notice'], "There is not enough stock " );

					// Remove the wc_notice <a>cart</a> text. All we want is the product and stock values
					if ( ! empty( $message_offset ) ) {
						throw new UserError( html_entity_decode( substr( $notice['notice'], $message_offset ) ) );
					}
				}

				throw new UserError( $cart_item_key->get_error_message() );
			}

			// Return payload.
			return [ 'key' => $cart_item_key ];
		};
	}
}

add_action( 'graphql_register_types', function () {
	ProductBundleAddToCart::register_mutation();
} );
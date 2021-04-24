<?php

namespace WPGraphQL\WooCommerce\ProductBundles;

use WPGraphQL\WooCommerce\Type\WPObject\Product_Types;
use WPGraphQL\WooCommerce\Type\WPInterface\Product;

const TYPE_BUNDLE_PRODUCT = 'BundleProduct';

/**
 * Register BundleProduct Type
 */
add_action( 'graphql_register_types', function () {
	
	/**
	 * Register the bundle product fields
	 *
	 * @return array[]
	 */
	function get_product_bundle_fields(): array {
		return [
			'bundlePriceMin'        => [
				'type'        => 'String',
				'description' => __( 'Minimum bundle price', 'woographql-product-bundles' ),
				'resolve'     => function ( $source ) {
					$bundle_price_min = $source->get_bundle_price( 'min' );
					
					return ! empty( $bundle_price_min ) ? $bundle_price_min : null;
				},
			],
			'bundlePriceMax'        => [
				'type'        => 'String',
				'description' => __( 'Maximum bundle price', 'woographql-product-bundles' ),
				'resolve'     => function ( $source ) {
					$bundle_price_max = $source->get_bundle_price( 'max' );

					return ! empty( $bundle_price_max ) ? $bundle_price_max : null;
				},
			],
			'layout'                => [
				'type'        => 'String',
				'description' => __( 'Layout option state', 'woographql-product-bundles' ),
				'resolve'     => function ( $source ) {
					$layout = $source->get_layout();

					return ! empty( $layout ) ? $layout : null;
				},
			],
			'groupMode'             => [
				'type'        => 'String',
				'description' => __( 'Item grouping option state', 'woographql-product-bundles' ),
				'resolve' => function ( $source ) {
					$group_mode = $source->get_group_mode();

					return ! empty( $group_mode ) ? $group_mode : null;
				},
			],
			'addToCartFormLocation' => [
				'type'        => 'String',
				'description' => __( 'Form location option state', 'woographql-product-bundles' ),
				'resolve' => function ( $source ) {
					$add_to_cart_form_location = $source->get_add_to_cart_form_location();

					return ! empty( $add_to_cart_form_location ) ? $add_to_cart_form_location : null;
				},
			],
			'editableInCart' => [
				'type'        => 'Boolean',
				'description' => __( 'Whether the bundle is editable in the cart.', 'woographql-product-bundles' ),
				'resolve' => function ( $source ) {
					return $source->get_editable_in_cart();
				},
			],
		];
	}
	
	/**
	 * Register the Object Type
	 */
	register_graphql_object_type(
		TYPE_BUNDLE_PRODUCT,
		[
			'description' => __( 'A product bundle object', 'woographql-product-bundles' ),
			'interfaces'  => Product_Types::get_product_interfaces(),
			'fields'      =>
				array_merge(
					Product::get_fields(),
					Product_Types::get_pricing_and_tax_fields(),
					Product_Types::get_shipping_fields(),
					Product_Types::get_inventory_fields(),
					get_product_bundle_fields(),
				),
		]
	);
	
	add_filter( 'graphql_bundle_product_model_use_pricing_and_tax_fields', '__return_true' );
	add_filter( 'graphql_bundle_product_model_use_inventory_fields', '__return_true' );
	add_filter( 'graphql_bundle_product_model_use_virtual_data_fields', '__return_true' );
	add_filter( 'graphql_bundle_product_model_use_variation_pricing_fields', '__return_false' );
	add_filter( 'graphql_bundle_product_model_use_external_fields', '__return_false' );
	add_filter( 'graphql_bundle_product_model_use_grouped_fields', '__return_false' );
	

} );

/**
 * Register BUNDLE enum so that input filters work
 */
add_filter( 'graphql_product_types_enum_values', function ( $values ) {
	$values['BUNDLE'] = [
		'value'       => 'bundle',
		'description' => __( 'A bundle product', 'woographql-product-bundles' ),
	];
	
	return $values;
} );

/**
 * Register our Product Bundle to WooGraphQL
 */
add_filter( 'graphql_woocommerce_product_types', function ( $product_types ) {
	$product_types['bundle'] = TYPE_BUNDLE_PRODUCT;
	
	return $product_types;
} );

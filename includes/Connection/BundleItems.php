<?php

namespace WPGraphQL\WooCommerce\ProductBundles\Connection\BundleItems;

use WC_Bundled_Item_Data;
use WC_PB_DB;
use WPGraphQL\AppContext;
use GraphQL\Type\Definition\ResolveInfo;
use WPGraphQL\Data\Connection\AbstractConnectionResolver;
use WPGraphQL\WooCommerce\Data\Connection\Product_Connection_Resolver;
use const WPGraphQL\WooCommerce\ProductBundles\TYPE_BUNDLE_PRODUCT;

/**
 * Register a GraphQL Connection between Product and BundleProduct in order to return the
 * bundle items for a given Product Bundle
 *
 * @TODO - stockStatus + create types for allowedVariations and defaultVariationAttributes so we
 * don't send JSON data.
 */
add_action( 'graphql_register_types', function () {
	/**
	 * Get the bundle item ids for a given product
	 *
	 * @TODO - this feels hacky. Should we add some level of caching or see if there's
	 * an existing method to use?
	 *
	 * @param mixed       $source  The source that's passed down the GraphQL queries.
	 * @param array       $args    The inputArgs on the field.
	 * @param AppContext  $context The AppContext passed down the GraphQL tree.
	 * @param ResolveInfo $info    The ResolveInfo passed down the GraphQL tree.
	 *
	 * @return array
	 */
	function get_bundle_item_ids( $source, $args, $context, $info ): array {
		// Bail if we don't have a source
		if ( empty( $source ) ) {
			return [];
		}
		
		// Bail if we don't have a type to check against
		if ( empty( $source->type ) ) {
			return [];
		}
		
		// Bail if this isn't a bundle
		if ( 'bundle' !== $source->type ) {
			return [];
		}
		
		// Get the product id of the Product Bundle parent
		$productId = $source->ID;
		
		// Bail if we don't have a product id to run our query
		if ( empty( $productId ) ) {
			return [];
		}
		
		global $wpdb;
		
		/**
		 * Allow for LIMIT overwrites if greater than n is needed for a use-case
		 *
		 * @param mixed       $source  The source that's passed down the GraphQL queries.
		 * @param array       $args    The inputArgs on the field.
		 * @param AppContext  $context The AppContext passed down the GraphQL tree.
		 * @param ResolveInfo $info    The ResolveInfo passed down the GraphQL tree.
		 */
		$bundle_item_limit = apply_filters( 'woographql_product_bundles_item_connector_limit',
			10, $source, $args, $context, $info );
		
		// Run our query: get me all of the post ids of the bundle items
		$bundle_item_ids = $wpdb->get_results( $wpdb->prepare( "
		SELECT
			product_id
		FROM
			{$wpdb->prefix}woocommerce_bundled_items
		WHERE
			bundle_id = %d
		LIMIT %d
		",
			intval( $productId ),
			intval( $bundle_item_limit )
		) );
		
		return wp_list_pluck( $bundle_item_ids, 'product_id' );
	}
	
	/**
	 * Get the edge fields for the bundle item
	 *
	 * @return array[]
	 */
	function get_edge_fields(): array {
		return [
			'quantityMin'                        => [
				'type'        => 'Int',
				'description' => __( 'The quantity minimum', 'woographql-product-bundles' ),
				'resolve'     => function ( $source ) {
					/* @var $bundledItem WC_Bundled_Item_Data */
					$bundledItem = $source['bundledItem'];
					
					if ( empty( is_a( $bundledItem, \WC_Bundled_Item_Data::class ) ) ) {
						return null;
					}
					
					$minimum = $bundledItem->get_meta( 'quantity_min' );
					
					return ! empty( $minimum ) ? $minimum : null;
				},
			],
			'quantityMax'                        => [
				'type'        => 'Int',
				'description' => __( 'The quantity maximum', 'woographql-product-bundles' ),
				'resolve'     => function ( $source ) {
					/* @var $bundledItem WC_Bundled_Item_Data */
					$bundledItem = $source['bundledItem'];
					
					if ( empty( is_a( $bundledItem, \WC_Bundled_Item_Data::class ) ) ) {
						return null;
					}
					
					$maximum = $bundledItem->get_meta( 'quantity_max' );
					
					return ! empty( $maximum ) ? $maximum : null;
				},
			],
			'bundledItemId'                      => [
				'type'        => 'Int',
				'description' => __( 'The bundled item ID', 'woographql-product-bundles' ),
				'resolve'     => function ( $source ) {
					/* @var $bundledItem WC_Bundled_Item_Data */
					$bundledItem = $source['bundledItem'];
					
					if ( empty( is_a( $bundledItem, \WC_Bundled_Item_Data::class ) ) ) {
						return null;
					}
					
					$bundled_item_id = $bundledItem->get_bundled_item_id();
					
					return  ! empty( $bundled_item_id ) ? $bundled_item_id : null;
				},
			],
			'menuOrder'                          => [
				'type'        => 'Int',
				'description' => __( 'Bundled item menu order', 'woographql-product-bundles' ),
				'resolve'     => function ( $source ) {
					/* @var $bundledItem WC_Bundled_Item_Data */
					$bundledItem = $source['bundledItem'];
					
					if ( empty( is_a( $bundledItem, \WC_Bundled_Item_Data::class ) ) ) {
						return null;
					}
					
					$menu_order = $bundledItem->get_menu_order();
					
					return ! empty( $menu_order ) ? $menu_order : null;
				},
			],
			'priceIndividually'                  => [
				'type'        => 'Boolean',
				'description' => __( 'Whether the price of the bundled item is added to the price of the parent bundle product.',
					'woographql-product-bundles' ),
				'resolve'     => function ( $source ) {
					/* @var $bundledItem WC_Bundled_Item_Data */
					$bundledItem = $source['bundledItem'];
					
					if ( empty( is_a( $bundledItem, \WC_Bundled_Item_Data::class ) ) ) {
						return null;
					}
					
					$result = $bundledItem->get_meta( 'priced_individually' );
					
					return empty( $result ) ? null : $result === 'yes';
				},
			],
			'shippedIndividually'                => [
				'type'        => 'Boolean',
				'description' => __( 'Whether the bundled item is shipped individually.',
					'woographql-product-bundles' ),
				'resolve'     => function ( $source ) {
					/* @var $bundledItem WC_Bundled_Item_Data */
					$bundledItem = $source['bundledItem'];
					
					if ( empty( is_a( $bundledItem, \WC_Bundled_Item_Data::class ) ) ) {
						return null;
					}
					
					$result = $bundledItem->get_meta( 'shipped_individually' );
					
					return empty( $result ) ? null : $result === 'yes';
				},
			],
			'overrideTitle'                      => [
				'type'        => 'Boolean',
				'description' => __( 'Whether to override the bundled item title.',
					'woographql-product-bundles' ),
				'resolve'     => function ( $source ) {
					/* @var $bundledItem WC_Bundled_Item_Data */
					$bundledItem = $source['bundledItem'];
					
					if ( empty( is_a( $bundledItem, \WC_Bundled_Item_Data::class ) ) ) {
						return null;
					}
					
					$result = $bundledItem->get_meta( 'override_title' );
					
					return empty( $result ) ? null : $result === 'yes';
				},
			],
			'title'                              => [
				'type'        => 'String',
				'description' => __( 'The overwritten title.', 'woographql-product-bundles' ),
				'resolve'     => function ( $source ) {
					/* @var $bundledItem WC_Bundled_Item_Data */
					$bundledItem = $source['bundledItem'];
					
					if ( empty( is_a( $bundledItem, \WC_Bundled_Item_Data::class ) ) ) {
						return null;
					}
					
					$title = $bundledItem->get_meta( 'title' );
					
					return ! empty( $title ) ? $title : null;
				},
			],
			'overrideDescription'                => [
				'type'        => 'Boolean',
				'description' => __( 'Whether to override the bundled item description.',
					'woographql-product-bundles' ),
				'resolve'     => function ( $source ) {
					/* @var $bundledItem WC_Bundled_Item_Data */
					$bundledItem = $source['bundledItem'];
					
					if ( empty( is_a( $bundledItem, \WC_Bundled_Item_Data::class ) ) ) {
						return null;
					}
					
					$result = $bundledItem->get_meta( 'override_description' );
					
					return empty( $result ) ? null : $result === 'yes';
				},
			],
			'description'                        => [
				'type'        => 'String',
				'description' => __( 'The overwritten description.', 'woographql-product-bundles' ),
				'resolve'     => function ( $source ) {
					/* @var $bundledItem WC_Bundled_Item_Data */
					$bundledItem = $source['bundledItem'];
					
					if ( empty( is_a( $bundledItem, \WC_Bundled_Item_Data::class ) ) ) {
						return null;
					}
					
					$description = $bundledItem->get_meta( 'description' );
					
					return ! empty( $description ) ? $description : null;
				},
			],
			'optional'                           => [
				'type'        => 'Boolean',
				'description' => __( 'Whether the bundled item is optional.',
					'woographql-product-bundles' ),
				'resolve'     => function ( $source ) {
					/* @var $bundledItem WC_Bundled_Item_Data */
					$bundledItem = $source['bundledItem'];
					
					if ( empty( is_a( $bundledItem, \WC_Bundled_Item_Data::class ) ) ) {
						return null;
					}
					
					$result = $bundledItem->get_meta( 'optional' );
					
					return empty( $result ) ? null : $result === 'yes';
				},
			],
			'hideThumbnail'                      => [
				'type'        => 'Boolean',
				'description' => __( 'Whether to hide the thumbnail.',
					'woographql-product-bundles' ),
				'resolve'     => function ( $source ) {
					/* @var $bundledItem WC_Bundled_Item_Data */
					$bundledItem = $source['bundledItem'];
					
					if ( empty( is_a( $bundledItem, \WC_Bundled_Item_Data::class ) ) ) {
						return null;
					}
					
					$result = $bundledItem->get_meta( 'hide_thumbnail' );
					
					return empty( $result ) ? null : $result === 'yes';
				},
			],
			'discount'                           => [
				'type'        => 'String',
				'description' => __( 'Bundle item discount if priced individually.',
					'woographql-product-bundles' ),
				'resolve'     => function ( $source ) {
					/* @var $bundledItem WC_Bundled_Item_Data */
					$bundledItem = $source['bundledItem'];
					
					if ( empty( is_a( $bundledItem, \WC_Bundled_Item_Data::class ) ) ) {
						return null;
					}
					
					$discount = $bundledItem->get_meta( 'discount' );
					
					return ! empty( $discount ) ? $discount : null;
				},
			],
			'overrideVariations'                 => [
				'type'        => 'Boolean',
				'description' => __( 'Whether the bundle item overrides the variations.',
					'woographql-product-bundles' ),
				'resolve'     => function ( $source ) {
					/* @var $bundledItem WC_Bundled_Item_Data */
					$bundledItem = $source['bundledItem'];
					
					if ( empty( is_a( $bundledItem, \WC_Bundled_Item_Data::class ) ) ) {
						return null;
					}
					
					$result = $bundledItem->get_meta( 'override_variations' );
					
					return empty( $result ) ? null : $result === 'yes';
				},
			],
			'allowedVariations'                  => [
				'type'        => 'String',
				'description' => __( 'Curated list of variations', 'woographql-product-bundles' ),
				'resolve'     => function ( $source ) {
					/* @var $bundledItem WC_Bundled_Item_Data */
					$bundledItem = $source['bundledItem'];
					
					if ( empty( is_a( $bundledItem, \WC_Bundled_Item_Data::class ) ) ) {
						return null;
					}
					
					return wp_json_encode( $bundledItem->get_meta( 'allowed_variations'
					) );
				},
			],
			'overrideDefaultVariationAttributes' => [
				'type'        => 'Boolean',
				'description' => __( 'Whether the bundled item overrides the default attributes
				.', 'woographql-product-bundles' ),
				'resolve'     => function ( $source ) {
					/* @var $bundledItem WC_Bundled_Item_Data */
					$bundledItem = $source['bundledItem'];
					
					if ( empty( is_a( $bundledItem, \WC_Bundled_Item_Data::class ) ) ) {
						return null;
					}
					
					$result = $bundledItem->get_meta( 'override_default_variation_attributes' );
					
					return empty( $result ) ? null : $result === 'yes';
				},
			],
			'defaultVariationAttributes'         => [
				'type'        => 'String',
				'description' => __( 'The default variation attributes.',
					'woographql-product-bundles' ),
				'resolve'     => function ( $source ) {
					/* @var $bundledItem WC_Bundled_Item_Data */
					$bundledItem = $source['bundledItem'];
					
					if ( empty( is_a( $bundledItem, \WC_Bundled_Item_Data::class ) ) ) {
						return null;
					}
					
					return wp_json_encode( $bundledItem->get_meta( 'default_variation_attributes' ) );
				},
			],
			'singleProductVisibility'            => [
				'type'        => 'String',
				'description' => __( 'Whether the bundle item is visible on the product page',
					'woographql-product-bundles' ),
				'resolve'     => function ( $source ) {
					/* @var $bundledItem WC_Bundled_Item_Data */
					$bundledItem = $source['bundledItem'];
					
					if ( empty( is_a( $bundledItem, \WC_Bundled_Item_Data::class ) ) ) {
						return null;
					}
					
					$single_product_visibility = $bundledItem->get_meta( 'single_product_visibility' );
					
					return ! empty( $single_product_visibility ) ? $single_product_visibility : null;
				},
			],
			'cartVisibility'                     => [
				'type'        => 'String',
				'description' => __( 'Whether the bundle item is visible on the cart page',
					'woographql-product-bundles' ),
				'resolve'     => function ( $source ) {
					/* @var $bundledItem WC_Bundled_Item_Data */
					$bundledItem = $source['bundledItem'];
					
					if ( empty( is_a( $bundledItem, \WC_Bundled_Item_Data::class ) ) ) {
						return null;
					}
					
					$cart_visibility = $bundledItem->get_meta( 'cart_visibility' );
					
					return ! empty( $cart_visibility ) ? $cart_visibility : null;
				},
			],
			'orderVisibility'                    => [
				'type'        => 'String',
				'description' => __( 'Whether the bundle item is visible on the order page',
					'woographql-product-bundles' ),
				'resolve'     => function ( $source ) {
					/* @var $bundledItem WC_Bundled_Item_Data */
					$bundledItem = $source['bundledItem'];
					
					if ( empty( is_a( $bundledItem, \WC_Bundled_Item_Data::class ) ) ) {
						return null;
					}
					
					$order_visibility = $bundledItem->get_meta( 'order_visibility' );
					
					return ! empty( $order_visibility ) ? $order_visibility : null;
				},
			],
			'singleProductPriceVisibility'       => [
				'type'        => 'String',
				'description' => __( 'Whether the bundle item price is visible on the product page',
					'woographql-product-bundles' ),
				'resolve'     => function ( $source ) {
					/* @var $bundledItem WC_Bundled_Item_Data */
					$bundledItem = $source['bundledItem'];
					
					if ( empty( is_a( $bundledItem, \WC_Bundled_Item_Data::class ) ) ) {
						return null;
					}
					
					$product_price_visibility = $bundledItem->get_meta( 'single_product_price_visibility' );
					
					return ! empty( $product_price_visibility ) ? $product_price_visibility : null;
				},
			],
			'cartPriceVisibility'                => [
				'type'        => 'String',
				'description' => __( 'Whether the bundle item price is visible on the cart page',
					'woographql-product-bundles' ),
				'resolve'     => function ( $source ) {
					/* @var $bundledItem WC_Bundled_Item_Data */
					$bundledItem = $source['bundledItem'];
					
					if ( empty( is_a( $bundledItem, \WC_Bundled_Item_Data::class ) ) ) {
						return null;
					}
					
					$cart_price_visibility = $bundledItem->get_meta( 'cart_price_visibility' );
					
					return ! empty( $cart_price_visibility ) ? $cart_price_visibility : null;
				},
			],
			'orderPriceVisibility'               => [
				'type'        => 'String',
				'description' => __( 'Whether the bundle item price is visible on the order
				page', 'woographql-product-bundles' ),
				'resolve'     => function ( $source ) {
					/* @var $bundledItem WC_Bundled_Item_Data */
					$bundledItem = $source['bundledItem'];
					
					if ( empty( is_a( $bundledItem, \WC_Bundled_Item_Data::class ) ) ) {
						return null;
					}
					
					$order_price_visibility = $bundledItem->get_meta( 'order_price_visibility' );
					
					return ! empty( $order_price_visibility ) ? $order_price_visibility : null;
				},
			],
		];
	}
	
	/**
	 * Register the bundle item connection to the product Type
	 */
	register_graphql_connection( [
		'fromType'      => TYPE_BUNDLE_PRODUCT,
		'toType'        => 'Product',
		'fromFieldName' => 'bundleItems',
		'resolve'       => function ( $source, $args, $context, $info ) {
			$resolver = new Product_Connection_Resolver( $source, $args, $context, $info );
			
			// Only get the bundle items that belong to this product
			$resolver->set_query_arg(
				'post__in',
				get_bundle_item_ids( $source, $args, $context, $info )
			);
			
			return $resolver->get_connection();
		},
		'edgeFields'    => get_edge_fields(),
	] );
} );


/**
 * Inject the WC_Bundled_Item_Data Class into the resolver so that fields can access
 * edge data
 */
add_filter( 'graphql_connection_edges', function ( $edges, AbstractConnectionResolver $resolver ) {
	/* @var $source \WC_Product_Bundle */
	$source = $resolver->getSource();
	
	// Bail if we don't have a source
	if ( empty( $source ) ) {
		return $edges;
	}
	
	// Bail if we don't have a type to check against
	if ( empty( $source->type ) ) {
		return $edges;
	}
	
	// Bail if our $source isn't a bundle
	if ( 'bundle' !== $source->type ) {
		return $edges;
	}
	
	// Get some info about the $resolver
	$resolverInfo = $resolver->getInfo();
	
	// Bail if there isn't any info
	if ( empty( $resolverInfo ) ) {
		return $edges;
	}
	
	// Get the name of the field we are resolving
	$fieldName = $resolverInfo->fieldName;
	
	// Bail if there's nothing to check against
	if ( empty( $fieldName ) ) {
		return $edges;
	}
	
	// Bail if our field isn't bundleItems
	if ( 'bundleItems' !== $fieldName ) {
		return $edges;
	}
	
	$bundled_data_items = $source->get_bundled_data_items();
	
	// Go through each edge, find the bundle item id, and set the bundleItem
	foreach ( $edges as $key => $edge ) {
		
		$raw_bundled_data_item = array_filter( $bundled_data_items,
			function ( WC_Bundled_Item_Data $item ) use ( $edge ) {
				return $item->get_product_id() === $edge['node']->ID;
			} );
		
		if ( empty( $raw_bundled_data_item ) ) {
			$edges[ $key ]['bundledItem'] = [];
			continue;
		}
		
		$bundled_data_item = array_values( $raw_bundled_data_item );
		
		// Expose the bundleItem so that the resolver can get edge data
		$edges[ $key ]['bundledItem'] = WC_PB_DB::get_bundled_item( $bundled_data_item[0]->get_bundled_item_id() );
	}
	
	return $edges;
}, 10, 2 );

<?php

namespace WPGraphQL\WooCommerce\ProductBundles;

/**
 * Plugin Name:       WooGraphQL Product Bundles
 * Plugin URI:        https://github.com/jacobarriola/woographql-product-bundles
 * Description:       Adds support for WooCommerce Product Bundles to the GraphQL schema.
 * Version:           0.0.4
 * Author:            Jacob Arriola
 * Author URI:        https://jacobarriola.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       woographql-product-bundles
 */

// If this file is called directly, abort.
defined( 'ABSPATH' ) || exit;

/**
 * Define Constants
 */
if ( false === defined( 'WOOGRAPHQL_PRODUCT_BUNDLES_DIR' ) ) {
	define( 'WOOGRAPHQL_PRODUCT_BUNDLES_DIR', plugin_dir_path( __FILE__ ) );
}

if ( false === defined( 'WOOGRAPHQL_PRODUCT_BUNDLES_VERSION' ) ) {
	define( 'WOOGRAPHQL_PRODUCT_BUNDLES_VERSION', '0.0.1' );
}

if ( false === defined( 'WPGRAPHQL_WOOCOMMERCE_REQUIRED_MIN_VERSION' ) ) {
	define( 'WPGRAPHQL_WOOCOMMERCE_REQUIRED_MIN_VERSION', '0.6.1' );
}

if ( false === defined( 'WPGRAPHQL_REQUIRED_MIN_VERSION' ) ) {
	define( 'WPGRAPHQL_REQUIRED_MIN_VERSION', '0.13.2' );
}

function get_inactive_dependencies(): array {
	$deps = [];
	
	if ( ! class_exists( '\WPGraphQL' ) ) {
		$deps[] = 'WPGraphQL';
	}
	
	if ( ! class_exists( '\WooCommerce' ) ) {
		$deps[] = 'WooCommerce';
	}
	
	if ( ! class_exists( '\WC_Product_Bundle' ) ) {
		$deps[] = 'WooCommerce Product Bundles';
	}
	
	if ( ! class_exists( '\WP_GraphQL_WooCommerce' ) ) {
		$deps[] = 'WooGraphQL';
	}
	
	return $deps;
}

function get_minimum_version_dependencies(): array {
	$versions = [];
	
	if ( true === version_compare( WPGRAPHQL_WOOCOMMERCE_VERSION,
			WPGRAPHQL_WOOCOMMERCE_REQUIRED_MIN_VERSION, 'lt' ) ) {
		$versions['WooGraphQL'] = WPGRAPHQL_WOOCOMMERCE_REQUIRED_MIN_VERSION;
	}
	
	if ( true === version_compare( WPGRAPHQL_VERSION, WPGRAPHQL_REQUIRED_MIN_VERSION, 'lt' ) ) {
		$versions['WPGraphQL'] = WPGRAPHQL_REQUIRED_MIN_VERSION;
	}
	
	return $versions;
}

function load(): void {
	require_once WOOGRAPHQL_PRODUCT_BUNDLES_DIR . 'includes/Type/Object/BundleProduct.php';
	require_once WOOGRAPHQL_PRODUCT_BUNDLES_DIR . 'includes/Connection/BundleItems.php';
	require_once WOOGRAPHQL_PRODUCT_BUNDLES_DIR . 'includes/Mutation/ProductBundleAddToCart.php';
}

function render_inactive_notices( array $inactive ): void {
	foreach ( $inactive as $plugin ) {
		add_action(
			'admin_notices',
			function () use ( $plugin ) { ?>
				<div class="error notice">
					<p>
						<?php
						esc_html_e(
							sprintf(
								'%s is not found. Check to ensure the plugin is installed and activated',
								$plugin
							),
							'woographql-product-bundles'
						); ?>
					</p>
				</div>
				<?php
			}
		);
	}
}

function render_minimum_version_notices( array $dependencies ): void {
	foreach ( $dependencies as $plugin => $version ) {
		add_action(
			'admin_notices',
			function () use ( $plugin, $version ) { ?>
				<div class="error notice">
					<p>
						<?php
						esc_html_e(
							sprintf(
								'%s minimum version not met. WooGraphQL Product Bundles requires at least version %s',
								$plugin,
								$version
							),
							'woographql-product-bundles'
						); ?>
					</p>
				</div>
				<?php
			}
		);
	}
}

/**
 * Initialize the plugin
 */
add_action( 'graphql_woocommerce_init', function () {
	
	$inactive_dependencies = get_inactive_dependencies();

	// Render inactive notice and bail
	if ( ! empty( $inactive_dependencies ) ) {
		render_inactive_notices( $inactive_dependencies );

		return;
	}

	$minimum_versions = get_minimum_version_dependencies();

	// Render minimum version notice and bail
	if ( ! empty( $minimum_versions ) ) {
		render_minimum_version_notices( $minimum_versions );

		return;
	}
	
	// Load up the plugin files
	load();
} );

<?php
/*
 Plugin Name: CDN Integration
 Description: Run your entire site through a CDN.
 Author: kingkool68
 Version: 0.1.4
 Author URI: http://www.russellheimlich.com/
 */

include 'class-separate-admin-url.php';
if( is_admin() ) {
	include 'class-cdn-integration.php';
	include 'cdn-flush-dashboard-widget.php';
} else {
	include 'class-buffering.php';
}

$options = get_cdn_integration_options();
$cdn_integration_plugin_dir_path = plugin_dir_path( __FILE__ );
$cdn_provider = $options['cdn-provider'];
$cdn_provider_include_file = $cdn_integration_plugin_dir_path . 'class-' . $cdn_provider . '.php';
if( $cdn_provider && file_exists( $cdn_provider_include_file ) ) {
	include $cdn_provider_include_file;
}

/**
 * Adds a Last-Modified header to the request based on the post modified timestamp
 */
function cdn_integration_add_last_modified_header() {
	$post = get_post();
	if( isset($post) && is_singular() ) {
		$timestamp = $post->post_date_gmt;
		if( isset( $post->post_modified ) ) {
			$timestamp = $post->post_modified;
		}
		$timestamp = strtotime( $timestamp );
		if( $timestamp ) {
			$date = date( 'D, d M Y H:i:s', $timestamp );  // format podle RFC 2822
			header( 'Last-Modified: ' . $date . ' GMT' );
		}
	}

	// Clean up pre-existing headers
	$current_headers = headers_list();
	$supported_headers = array( 'Cache-Control', );
	foreach( $supported_headers as $current_header ) {
		header_remove( $current_header );
	}
}
add_action( 'wp', 'cdn_integration_add_last_modified_header' );

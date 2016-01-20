<?php
/*
 Plugin Name: CDN Integration
 Description: Run your entire site through a CDN.
 Author: kingkool68
 Version: 0.1
 Author URI: http://www.russellheimlich.com/
 */

include 'class-separate-admin-url.php';
if( is_admin() ) {
    include 'class-cdn-integration.php';
}

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

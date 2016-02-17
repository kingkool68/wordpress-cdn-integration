<?php
function keycdn_flush_tags( $tags = array() ) {
    if( !$tags ) {
        throw new Exception( '$tags argument passed to keycdn_flush_tags() is FALSE!' );
        return;
    }

    if( !is_array( $tags ) ) {
        $tags = array( $tags );
    }

    $options = get_cdn_integration_options();
    $api_secret = $options['keycdn-api-secret'];
    $zone_id = $options['keycdn-zone-id'];
    $zone_url = $options['keycdn-zone-url'];

    if( !$api_secret || !$zone_id || !$zone_url || !$public_protocol || !$public_domain ) {
        throw new Exception( 'Invalid KeyCDN settings!' );
        return;
    }

    $payload= json_encode( array( 'tags' => $tags, ) );

    $request_url = 'https://api.keycdn.com/zones/purgetag/' . $zone_id . '.json';
    $args = array(
        'method' => 'DELETE',
        'headers' => array(
            'Authorization' => 'Basic ' . base64_encode( $api_secret . ':' ),
            'Accept' => 'application/json',
			'Content-Type' => 'application/json',
            'Content-length' => strlen( $payload ),
        ),
        'body' => $payload,
		'cookies' => array(),
    );

    $response = wp_remote_post( $request_url, $args );

    if( is_wp_error( $response ) ) {
        error_log( 'KeyCDN Cache Tag Flush Error: ' . $response->get_error_message() );
    }

}
function keycdn_flush_urls( $urls = array() ) {
    // error_log( '--- KeyCDN Flush URL Hook ---' );
    if( !$urls ) {
        return;
    }

    $options = get_cdn_integration_options();
    $api_secret = $options['keycdn-api-secret'];
    $zone_id = $options['keycdn-zone-id'];
    $zone_url = $options['keycdn-zone-url'];

    $public_protocol = $options['public-protocol'];
    $public_domain = $options['public-domain'];

    if( !$api_secret || !$zone_id || !$zone_url || !$public_protocol || !$public_domain ) {
        throw new Exception( 'Invalid KeyCDN settings!' );
        return;
    }

    $public_url = $public_protocol . '://' . $public_domain;

    foreach( $urls as $i => $url ) {
        $urls[ $i ] = str_replace( $public_url, $zone_url, $url );
    }
    $payload= json_encode( array( 'urls' => $urls, ) );

    $request_url = 'https://api.keycdn.com/zones/purgeurl/' . $zone_id . '.json';
    $args = array(
        'method' => 'DELETE',
        'headers' => array(
            'Authorization' => 'Basic ' . base64_encode( $api_secret . ':' ),
            'Accept' => 'application/json',
			'Content-Type' => 'application/json',
            'Content-length' => strlen( $payload ),
        ),
        'body' => $payload,
		'cookies' => array(),
    );

    $response = wp_remote_post( $request_url, $args );

    if( is_wp_error( $response ) ) {
        error_log( 'KeyCDN URL Flush Error: ' . $response->get_error_message() );
    }

}
add_action( 'cdn_integration_flush_urls', 'keycdn_flush_urls' );

function keycdn_wp() {
    if( isset( $_SERVER['HTTP_X_PULL'] ) && $_SERVER['HTTP_X_PULL'] == 'KeyCDN' ) {
        remove_action( 'template_redirect', 'redirect_canonical' );
    }
}
add_action( 'wp', 'keycdn_wp' );

function add_keycdn_cache_tags() {
    if( is_admin() ) {
        return;
    }

    $cache_tags = array( 'html' );
    if( is_home() ) {
        $cache_tags[] = 'home';
    }
    if( is_archive() ) {
        $cache_tags[] = 'archive';
    }
    if( is_singular() ) {
        $post = get_post();
        $cache_tags[] = 'singlular';
        $cache_tags[] = 'single-' . $post->post_type;
    }
    $cache_tags = apply_filters( 'keycdn_cache_tags', $cache_tags );

    if( !headers_sent() ) {
        header( 'Cache-Tag: ' . implode( ' ', $cache_tags ) );
    }
}
add_action( 'wp', 'add_keycdn_cache_tags' );

<?php
/**
 * A catch-all helper function for flushing URLs from the KeyCDN cache. Determines if the items are URLs, cache tags, or the keyword 'all' to flush the entire cache.
 * @param  array  $items List of items to group by URL or cache tags or the keyword 'all'
 */
function keycdn_flush( $items = array() ) {
	// This function plays traffic cop for different types of flush requests.
	if( is_string( $items ) ) {
		$items = array( $items );
	}

	if( !$items ) {
		return;
	}
	$items = array_map( 'trim', $items );
	$tags = array();
	$urls = array();
	$flush_whole_cache = false;
	foreach( $items as $item ) {
		$lowercase_item = strtolower( $item );
		if( substr( $lowercase_item, 0, 4 ) == 'http' || $lowercase_item[0] == '/' ) {
			$urls[] = $item;
		} else if( $lowercase_item == 'all' ) {
			$flush_whole_cache = true;
		} else {
			$tags[] = $item;
		}
	}

	if( $flush_whole_cache ) {
		keycdn_flush_all();
		return;
	}

	if( !empty( $tags ) ) {
		keycdn_flush_tags( $tags );
	}

	if( !empty( $urls ) ) {
		keycdn_flush_urls( $urls );
	}
}
add_action( 'cdn_integration_flush_urls', 'keycdn_flush' );

/**
 * Flushes the entire KeyCDN cache (zone purge)
 */
function keycdn_flush_all() {
	$options = get_cdn_integration_options();
	$api_secret = $options['keycdn-api-secret'];
	$zone_id = $options['keycdn-zone-id'];
	$zone_url = $options['keycdn-zone-url'];

	if( !$api_secret || !$zone_id || !$zone_url ) {
		throw new Exception( 'Invalid KeyCDN settings!' );
		return;
	}

	$request_url = 'https://api.keycdn.com/zones/purge/' . $zone_id . '.json';
	$args = array(
		'method' => 'GET',
		'headers' => array(
			'Authorization' => 'Basic ' . base64_encode( $api_secret . ':' ),
		),
		'body' => '',
		'timeout' => 30,
	);
	$response = wp_remote_post( $request_url, $args );

	if( is_wp_error( $response ) ) {
		error_log( 'KeyCDN Flush ALL Error: ' . $response->get_error_message() );
	}
}

/**
 * Flushes all URLs from the KeyCDN cache where the "Cache Tag" header contains one or more of the given $tags
 * @param  array  $tags Cache tags that should be flushed
 */
function keycdn_flush_tags( $tags = array() ) {
	if( !$tags ) {
		throw new Exception( '$tags argument passed to keycdn_flush_tags() is FALSE!' );
		return;
	}

	if( !is_string( $tags ) ) {
		$tags = array( $tags );
	}

	$options = get_cdn_integration_options();
	$api_secret = $options['keycdn-api-secret'];
	$zone_id = $options['keycdn-zone-id'];
	$zone_url = $options['keycdn-zone-url'];

	if( !$api_secret || !$zone_id || !$zone_url ) {
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
		),
		'body' => $payload,
	);

	$response = wp_remote_post( $request_url, $args );

	if( is_wp_error( $response ) ) {
		error_log( 'KeyCDN Cache Tag Flush Error: ' . $response->get_error_message() );
	}
}

/**
 * Flushes a list of URLs from the KeyCDN cache
 * @param  array  $urls List of URLs to be flushed
 */
function keycdn_flush_urls( $urls = array() ) {
	if( !$urls ) {
		return;
	}

	$options = get_cdn_integration_options();
	$api_secret = $options['keycdn-api-secret'];
	$zone_id = $options['keycdn-zone-id'];
	$zone_url = $options['keycdn-zone-url'];

	$public_protocol = $options['public-protocol'];
	$public_domain = $options['public-domain'];

	if( !$api_secret || !$zone_id || !$zone_url ) {
		throw new Exception( 'Invalid KeyCDN settings!' );
		return;
	}

	$public_url = $public_protocol . '://' . $public_domain;
	if( !$public_domain ) {
		$public_url = get_site_url();
	}

	foreach( $urls as $i => $url ) {
		$urls[ $i ] = str_replace( $public_url, $zone_url, $url );
	}
	$payload = json_encode( array( 'urls' => $urls, ) );

	$request_url = 'https://api.keycdn.com/zones/purgeurl/' . $zone_id . '.json';
	$args = array(
		'method' => 'DELETE',
		'headers' => array(
			'Authorization' => 'Basic ' . base64_encode( $api_secret . ':' ),
			'Accept' => 'application/json',
			'Content-Type' => 'application/json',
		),
		'body' => $payload,
		'sslverify' => false,
	);
	$response = wp_remote_post( $request_url, $args );

	if( is_wp_error( $response ) ) {
		error_log( 'KeyCDN URL Flush Error: ' . $response->get_error_message() );
	}
}

/**
 * If the request is coming from a KeyCDN edge server, remove the canonical redirect otherwise we'll have an endless redirect loop.
 */
function keycdn_maybe_remove_canonical_redirect() {
	if( isset( $_SERVER['HTTP_X_PULL'] ) && $_SERVER['HTTP_X_PULL'] == 'KeyCDN' ) {
		remove_action( 'template_redirect', 'redirect_canonical' );
	}
}
add_action( 'wp', 'keycdn_maybe_remove_canonical_redirect' );

/**
 * Adds the "Cache Tag" header to the request based on what kind of page is being served. This enables the flushing of specific subsets of pages in one go.
 */
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

<?php
function keycdn_flush_urls( $urls = array() ) {
    error_log( '--- KeyCDN Flush URL Hook ---' );
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


    error_log( print_r( $urls, TRUE ) );
    return;

    $response = wp_remote_post( $request_url, $args );

    if( is_wp_error( $response ) ) {
        error_log( 'KeyCDN URL Flush Error: ' . $response->get_error_message() );
    }

}
add_action( 'cdn_integration_flush_urls', 'keycdn_flush_urls' );

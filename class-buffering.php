<?php
class CDN_Integration_Buffering {
    public $ob_started = false;

    public function __construct() {
        if( !is_admin() ) {
            add_action( 'template_redirect', array( $this, 'template_redirect' ), 0 );
        }
    }

    public function template_redirect() {
        if( $this->ob_started ) {
            return;
        }

        $continue = apply_filters( 'continue_cdn_integration_buffering', false );
        if( !$continue ) {
            return;
        }

        $this->ob_started = true;
        ob_start( array( $this, 'ob_callback' ) );
    }

    public function ob_callback( $buffer ) {
        $buffer = trim( $buffer );
        if( strlen( $buffer ) == 0 ) {
            return '';
        }

        date_default_timezone_set( get_option('timezone_string') );
        $date = date( get_option('date_format') . ' g:i:s a T' );
        $buffer = str_replace( '</body>', "<!-- Generated $date -->\n</body>", $buffer );
        $buffer = apply_filters( 'cdn_integration_buffer', $buffer );
        header( 'Content-Length: ' . strlen( $buffer )   );

        return $buffer;
    }
}

new CDN_Integration_Buffering();

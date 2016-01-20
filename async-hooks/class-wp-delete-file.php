<?php
class CDN_Integration_WP_Delete_File extends WP_Async_Task {
    protected $action = 'wp_delete_file';

    public $urls = array();

    protected function prepare_data( $data ) {
        error_log( print_r( $data, TRUE ) );
        $url = $data[0];
        $this->urls[] = $url;
        $output = base64_encode( serialize( $this->urls ) );
        return array(
            'urls' => $output,
        );
    }

     protected function run_action() {
         $urls = unserialize( base64_decode( $_POST['urls'] ) );
         foreach( $urls as $url ) {
            do_action( 'wp_async_' . $this->action, $url );
        }
     }
}
new CDN_Integration_WP_Delete_File;

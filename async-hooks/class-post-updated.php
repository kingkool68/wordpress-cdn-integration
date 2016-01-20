<?php
class CDN_Integration_Post_Updated extends WP_Async_Task {
    protected $action = 'post_updated';

    protected function prepare_data( $data ) {
        $post_id = intval( $data[0] );
        $post_after = $data[1];
        $new_status = $post_after->post_status;
        $post_before = $data[2];
        $old_status = $post_before->post_status;
        // error_log( 'Old Status: ' . $old_status . ', New Status: ' . $new_status );

        if( $new_status === $old_status && $new_status !== 'publish' ) {
            throw new Exception( 'Post Status didn\'t actually change!' );
	    }

    	// Don't need to flush scheduled posts
    	if( $old_status !== 'publish' && $new_status !== 'publish' ) {
    		throw new Exception( 'Nothing published.' );
    	}

        if( $new_status === 'publish' && !in_array( $old_status, array( 'publish' ) ) ) {
            throw new Exception( 'Nothing to flush.' );
        }

        return array(
            'post_id' => $post_id,
        );
    }

     protected function run_action() {
         $post_id = intval( $_POST['post_id'] );
         do_action( 'wp_async_' . $this->action, $post_id );
     }
}
new CDN_Integration_Post_Updated;

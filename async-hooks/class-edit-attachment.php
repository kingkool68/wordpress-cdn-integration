<?php
class CDN_Integration_Edit_Attachment extends WP_Async_Task {
    protected $action = 'edit_attachment';

    protected function prepare_data( $data ) {
        $post_id = intval( $data[0] );
        return array(
            'post_id' => $post_id,
        );
    }

     protected function run_action() {
        $post_id = intval( $_POST['post_id'] );
        do_action( 'wp_async_' . $this->action, $post_id );
     }
}
new CDN_Integration_Edit_Attachment;

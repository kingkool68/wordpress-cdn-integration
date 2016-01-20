<?php
class CDN_Integration_Transition_Comment_Status extends WP_Async_Task {
    protected $action = 'transition_comment_status';

    protected function prepare_data( $data ) {
        $new_status = $data[0];
        $old_status = $data[1];
        $comment = $data[2];

    	if( $old_status !== 'approved' && $new_status !== 'approved' ) {
    		throw new Exception( 'No change to the post.' );
    	}

        return array(
            'post_id' => intval( $comment->comment_post_ID ),
        );
    }

     protected function run_action() {
         $post_id = intval( $_POST['post_id'] );
         do_action( 'wp_async_' . $this->action, $post_id );
     }
}
new CDN_Integration_Transition_Comment_Status;

<?php
class CDN_Integration_Pre_Delete_Term extends WP_Async_Task {
    protected $action = 'pre_delete_term';

    protected function prepare_data( $data ) {
        $term_id = intval( $data[0] );
        $taxonomy = $data[1];
        $url = get_term_link( $term_id, $taxonomy );

        return array(
            'url' => $url,
        );
    }

     protected function run_action() {
        $url = $_POST['url'];
        do_action( 'wp_async_' . $this->action, $url );
     }
}
new CDN_Integration_Pre_Delete_Term;

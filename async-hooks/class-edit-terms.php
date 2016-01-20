<?php
class CDN_Integration_Edit_Terms extends WP_Async_Task {
    protected $action = 'edit_terms';

    protected function prepare_data( $data ) {
        $term_id = intval( $data[0] );
        $taxonomy = $data[1];

        return array(
            'term_id' => $term_id,
            'taxonomy' => $taxonomy,
        );
    }

     protected function run_action() {
        $term_id = intval( $_POST['term_id'] );
        $taxonomy = $_POST['taxonomy'];
        do_action( 'wp_async_' . $this->action, $term_id, $taxonomy );
     }
}
new CDN_Integration_Edit_Terms;
